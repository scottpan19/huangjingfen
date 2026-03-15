<?php
declare(strict_types=1);

namespace app\jobs\hjf;

use app\services\hjf\MemberLevelServices;
use app\services\hjf\PointsRewardServices;
use app\services\hjf\QueuePoolServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\exception\ValidateException;
use think\facade\Log;

/**
 * 报单商品支付成功异步处理 Job
 *
 * 触发时机：Pay 监听器检测到 is_queue_goods=1 时派发。
 *
 * 执行流程：
 *  1. 调用 QueuePoolServices::enqueue() 将订单写入公排池
 *     （内部含 Redis 分布式锁 + 退款触发检测）
 *  2. 调用 PointsRewardServices::reward() 沿推荐链发放级差积分
 *  3. 调用 MemberLevelServices::checkUpgrade() 检查下单用户上级链是否触发等级升级
 *
 * Class HjfOrderPayJob
 * @package app\jobs\hjf
 */
class HjfOrderPayJob extends BaseJobs
{
    use QueueTrait;

    /**
     * @param int    $uid      下单用户 ID
     * @param string $orderId  订单号（eb_store_order.order_id）
     * @param float  $amount   报单金额（默认 3600.00）
     * @return bool
     */
    public function doJob(int $uid, string $orderId, float $amount = 3600.00): bool
    {
        try {
            // 1. 公排入队
            /** @var QueuePoolServices $queueServices */
            $queueServices = app()->make(QueuePoolServices::class);
            $queueServices->enqueue($uid, $orderId, $amount);
            Log::info("[HjfOrderPay] 公排入队成功 uid={$uid} orderId={$orderId}");
        } catch (ValidateException $e) {
            // 锁竞争导致入队失败，重新投递到队列（延迟5秒）
            Log::warning("[HjfOrderPay] 入队被锁，延迟重试 uid={$uid} orderId={$orderId}: " . $e->getMessage());
            static::dispatchSece(5, [$uid, $orderId, $amount]);
            return true;
        } catch (\Throwable $e) {
            Log::error("[HjfOrderPay] 公排入队异常 uid={$uid} orderId={$orderId}: " . $e->getMessage());
            return false;
        }

        try {
            // 2. 积分奖励（级差发放）
            /** @var PointsRewardServices $pointsServices */
            $pointsServices = app()->make(PointsRewardServices::class);
            $pointsServices->reward($uid, $orderId);
            Log::info("[HjfOrderPay] 积分奖励发放完成 uid={$uid} orderId={$orderId}");
        } catch (\Throwable $e) {
            // 积分发放失败不阻塞主流程，记录错误即可
            Log::error("[HjfOrderPay] 积分奖励失败 uid={$uid} orderId={$orderId}: " . $e->getMessage());
        }

        try {
            // 3. 触发推荐链等级升级检查（对买家本人及其直推上级）
            /** @var MemberLevelServices $levelServices */
            $levelServices = app()->make(MemberLevelServices::class);
            $levelServices->checkUpgrade($uid);

            // 同时检查直推上级（支付行为可能满足上级的伞下业绩门槛）
            $spreadUid = (int)\think\facade\Db::name('user')
                ->where('uid', $uid)
                ->value('spread_uid');
            if ($spreadUid > 0) {
                $levelServices->checkUpgrade($spreadUid);
            }
            Log::info("[HjfOrderPay] 等级升级检查完成 uid={$uid}");
        } catch (\Throwable $e) {
            Log::error("[HjfOrderPay] 等级升级检查失败 uid={$uid}: " . $e->getMessage());
        }

        return true;
    }
}
