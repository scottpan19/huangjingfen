<?php
declare(strict_types=1);

namespace app\jobs\hjf;

use app\dao\hjf\QueuePoolDao;
use app\dao\user\UserBillDao;
use app\dao\user\UserDao;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Db;
use think\facade\Log;

/**
 * 公排退款异步 Job
 *
 * 由 QueuePoolServices::checkAndTriggerRefund() 派发。
 * 执行流程：
 *  1. 二次检查记录状态（防止重复退款）
 *  2. 在数据库事务中：标记记录已退款 + 写入用户余额 + 写 user_bill 流水
 *
 * Class QueueRefundJob
 * @package app\jobs\hjf
 */
class QueueRefundJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 执行退款
     *
     * @param int   $queueId  eb_queue_pool.id
     * @param int   $uid      用户 ID
     * @param float $amount   退款金额
     * @param int   $batchNo  批次号
     * @return bool
     */
    public function doJob(int $queueId, int $uid, float $amount, int $batchNo): bool
    {
        try {
            /** @var QueuePoolDao $queueDao */
            $queueDao = app()->make(QueuePoolDao::class);

            // 二次检查：防止重复退款
            $record = $queueDao->get($queueId);
            if (!$record || (int)$record['status'] === 1) {
                Log::info("[QueueRefund] 记录 {$queueId} 已退款或不存在，跳过");
                return true;
            }

            Db::transaction(function () use ($queueId, $uid, $amount, $batchNo, $queueDao) {
                // 1. 标记公排记录为已退款
                $queueDao->markRefunded($queueId, $batchNo);

                // 2. 写入用户余额（使用 bcadd 避免浮点误差）
                /** @var UserDao $userDao */
                $userDao = app()->make(UserDao::class);
                $user    = $userDao->get($uid);
                if (!$user) {
                    throw new \RuntimeException("用户 {$uid} 不存在");
                }
                $newMoney = bcadd((string)$user['now_money'], (string)$amount, 2);
                $userDao->update($uid, ['now_money' => $newMoney], 'uid');

                // 3. 写 user_bill 流水记录
                /** @var UserBillDao $billDao */
                $billDao = app()->make(UserBillDao::class);
                $billDao->save([
                    'uid'      => $uid,
                    'link_id'  => $queueId,
                    'pm'       => 1,
                    'title'    => '公排退款',
                    'type'     => 'queue_refund',
                    'category' => 'now_money',
                    'number'   => $amount,
                    'balance'  => $newMoney,
                    'mark'     => "公排触发退款，批次#{$batchNo}",
                    'status'   => 1,
                    'add_time' => time(),
                ]);
            });

            Log::info("[QueueRefund] 退款成功 uid={$uid} amount={$amount} batch={$batchNo}");
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '公排退款失败: ' . $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return false;
        }

        return true;
    }
}
