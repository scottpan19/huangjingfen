<?php
declare(strict_types=1);

namespace app\services\hjf;

use app\dao\hjf\QueuePoolDao;
use app\jobs\hjf\QueueRefundJob;
use app\services\BaseServices;
use app\services\user\UserServices;
use crmeb\services\CacheService;
use crmeb\services\SystemConfigService;
use think\annotation\Inject;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Log;

/**
 * 公排池服务
 *
 * 负责：入队（enqueue）+ 退款触发条件判断 + 统计信息查询。
 * 退款的实际执行委托给 QueueRefundJob（异步）以避免支付回调阻塞。
 *
 * Class QueuePoolServices
 * @package app\services\hjf
 * @mixin QueuePoolDao
 */
class QueuePoolServices extends BaseServices
{
    #[Inject]
    protected QueuePoolDao $dao;

    /** Redis 分布式锁 Key */
    const LOCK_KEY = 'hjf:queue:enqueue_lock';

    /** 锁超时（秒） */
    const LOCK_TTL = 10;

    /**
     * 报单商品订单入队
     *
     * 使用 Redis SET NX EX 分布式锁保证同一时刻只有一个入队+触发检测操作执行。
     *
     * @param int    $uid      用户 ID
     * @param string $orderId  原始订单号
     * @param float  $amount   金额（默认 3600.00）
     * @return array  新入队记录数组
     * @throws ValidateException
     */
    public function enqueue(int $uid, string $orderId, float $amount = 3600.00): array
    {
        $lockKey   = self::LOCK_KEY;
        $lockValue = uniqid('', true);

        // 获取 Redis 实例
        /** @var \Redis $redis */
        $redis = CacheService::getRedis();

        // SET NX EX 原子锁
        $acquired = $redis->set($lockKey, $lockValue, ['NX', 'EX' => self::LOCK_TTL]);
        if (!$acquired) {
            throw new ValidateException('公排入队繁忙，请稍后重试');
        }

        try {
            return Db::transaction(function () use ($uid, $orderId, $amount, $redis, $lockKey, $lockValue) {
                $queueNo = $this->dao->nextQueueNo();

                $record = $this->dao->save([
                    'uid'           => $uid,
                    'order_id'      => $orderId,
                    'amount'        => $amount,
                    'queue_no'      => $queueNo,
                    'status'        => 0,
                    'refund_time'   => 0,
                    'trigger_batch' => 0,
                ]);

                $data = $record->toArray();

                // 检查是否触发退款条件
                $this->checkAndTriggerRefund();

                return $data;
            });
        } finally {
            // 释放锁（Lua 原子删除，防止误删他人的锁）
            $script = <<<'LUA'
if redis.call("GET", KEYS[1]) == ARGV[1] then
    return redis.call("DEL", KEYS[1])
else
    return 0
end
LUA;
            $redis->eval($script, [$lockKey, $lockValue], 1);
        }
    }

    /**
     * 检查是否达到退款触发条件，若是则派发异步退款 Job
     *
     * 触发条件：当前排队中总单数 ≥ triggerMultiple（默认4），
     * 即每进入4单就对最早的1单触发退款。
     */
    public function checkAndTriggerRefund(): void
    {
        $multiple = (int)SystemConfigService::get('hjf_trigger_multiple', 4);
        $pending  = $this->dao->countPending();

        if ($pending < $multiple) {
            return;
        }

        $earliest = $this->dao->getEarliestPending();
        if (!$earliest) {
            return;
        }

        // 批次号 = 历史已退款总数 + 1
        $batchNo = $this->dao->count(['status' => 1]) + 1;

        // 派发异步退款 Job
        QueueRefundJob::dispatch($earliest['id'], $earliest['uid'], $earliest['amount'], $batchNo);
    }

    /**
     * 获取用户的公排状态摘要（用于状态页）
     */
    public function getUserStatus(int $uid): array
    {
        $multiple = (int)SystemConfigService::get('hjf_trigger_multiple', 4);
        $pending  = $this->dao->countPending();
        $total    = $this->dao->countTotal();

        // 当前批次已入队单数（本批次进度）
        $batchCount = $pending % $multiple;

        // 用户自己的订单
        $myOrders = $this->dao->getModel()
            ->where('uid', $uid)
            ->order('add_time', 'desc')
            ->select()
            ->toArray();

        foreach ($myOrders as &$item) {
            $item['estimated_wait'] = $item['status'] === 1
                ? '已退款'
                : $this->estimateWait((int)$item['queue_no'], $pending, $multiple);
        }
        unset($item);

        return [
            'total_orders' => $total,
            'my_orders'    => $myOrders,
            'progress'     => [
                'current_batch_count' => $batchCount,
                'trigger_multiple'    => $multiple,
                'next_refund_queue_no' => $this->dao->getEarliestPending()['queue_no'] ?? 0,
            ],
        ];
    }

    /**
     * 获取用户公排历史（分页，支持按状态筛选）
     */
    public function getUserHistory(int $uid, int $status, int $page, int $limit): array
    {
        $result = $this->dao->getUserList($uid, $status, $page, $limit);

        foreach ($result['list'] as &$item) {
            $item['time_key'] = date('Y-m-d', (int)$item['add_time']);
        }
        unset($item);

        return $result;
    }

    /**
     * 简单估算等待时间（基于队列位置）
     */
    private function estimateWait(int $queueNo, int $pending, int $multiple): string
    {
        $earliest = $this->dao->getEarliestPending();
        if (!$earliest) {
            return '--';
        }
        $positionFromFront = $queueNo - (int)$earliest['queue_no'];
        if ($positionFromFront <= 0) {
            return '即将退款';
        }
        $waitCycles = (int)ceil($positionFromFront / $multiple);
        return "约等待 {$waitCycles} 轮";
    }
}
