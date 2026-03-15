<?php
declare(strict_types=1);

namespace app\jobs\hjf;

use app\services\hjf\PointsReleaseServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 积分每日释放 Job
 *
 * 由定时任务（crontab 或 Swoole Timer）在每天凌晨 00:01 触发。
 * 调用方式：PointsReleaseJob::dispatch()
 *
 * Class PointsReleaseJob
 * @package app\jobs\hjf
 */
class PointsReleaseJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 执行积分释放
     * @return bool
     */
    public function doJob(): bool
    {
        try {
            /** @var PointsReleaseServices $releaseServices */
            $releaseServices = app()->make(PointsReleaseServices::class);
            $result = $releaseServices->executeRelease();

            Log::info('[PointsReleaseJob] 执行完成', $result);
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '积分每日释放任务失败: ' . $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return false;
        }

        return true;
    }
}
