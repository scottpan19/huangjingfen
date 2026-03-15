<?php
declare(strict_types=1);

namespace app\jobs\hjf;

use app\services\hjf\MemberLevelServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 会员等级异步检查 Job
 *
 * 每次订单支付回调完成后，对推荐链上的上级异步派发此 Job 检查是否达到升级条件。
 * 调用方式：MemberLevelCheckJob::dispatch($uid)
 *
 * Class MemberLevelCheckJob
 * @package app\jobs\hjf
 */
class MemberLevelCheckJob extends BaseJobs
{
    use QueueTrait;

    /**
     * @param int $uid  需要检查升级的用户 ID
     * @return bool
     */
    public function doJob(int $uid): bool
    {
        try {
            /** @var MemberLevelServices $levelServices */
            $levelServices = app()->make(MemberLevelServices::class);
            $levelServices->checkUpgrade($uid);
        } catch (\Throwable $e) {
            response_log_write([
                'message' => "会员等级检查失败 uid={$uid}: " . $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return false;
        }

        return true;
    }
}
