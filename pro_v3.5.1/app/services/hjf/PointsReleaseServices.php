<?php
declare(strict_types=1);

namespace app\services\hjf;

use app\dao\hjf\PointsReleaseLogDao;
use app\dao\user\UserDao;
use app\services\BaseServices;
use crmeb\services\SystemConfigService;
use think\annotation\Inject;
use think\facade\Db;
use think\facade\Log;

/**
 * 积分每日释放服务
 *
 * 由定时任务（每天凌晨00:01）或 Command 触发。
 * 计算公式：release_amount = FLOOR(frozen_points × rate / 1000)
 * 其中 rate = hjf_release_rate（默认 4，即 4‰）
 *
 * Class PointsReleaseServices
 * @package app\services\hjf
 */
class PointsReleaseServices extends BaseServices
{
    #[Inject]
    protected PointsReleaseLogDao $logDao;

    #[Inject]
    protected UserDao $userDao;

    /**
     * 执行今日积分释放（批量）
     *
     * @return array  统计：['processed' => int, 'total_released' => int]
     */
    public function executeRelease(): array
    {
        $rate      = (int)SystemConfigService::get('hjf_release_rate', 4);
        $releaseDate = date('Y-m-d');
        $processed   = 0;
        $totalReleased = 0;

        // 分批处理，每批 200 条，避免内存溢合
        $page = 1;
        $limit = 200;

        do {
            $users = $this->userDao->selectList(
                ['frozen_points' => ['>', 0]],
                'uid,frozen_points,available_points',
                $page,
                $limit,
                'uid',
                'asc'
            );

            if (empty($users)) {
                break;
            }

            foreach ($users as $user) {
                $frozenBefore  = (int)$user['frozen_points'];
                // 使用 bcmath 确保精度
                $releaseAmount = (int)bcdiv(bcmul((string)$frozenBefore, (string)$rate), '1000');

                if ($releaseAmount <= 0) {
                    continue;
                }

                $frozenAfter = $frozenBefore - $releaseAmount;

                try {
                    Db::transaction(function () use ($user, $releaseAmount, $frozenBefore, $frozenAfter, $releaseDate) {
                        // 更新用户积分字段
                        $this->userDao->update($user['uid'], [
                            'frozen_points'    => $frozenAfter,
                            'available_points' => Db::raw('available_points + ' . $releaseAmount),
                        ], 'uid');

                        // 写 points_release_log（本次每日释放记录）
                        $this->logDao->save([
                            'uid'          => $user['uid'],
                            'points'       => $releaseAmount,
                            'pm'           => 1,
                            'type'         => 'release',
                            'title'        => '每日释放',
                            'mark'         => "积分每日自动解冻，释放日期 {$releaseDate}",
                            'status'       => 'released',
                            'release_date' => $releaseDate,
                        ]);
                    });

                    $totalReleased += $releaseAmount;
                    $processed++;
                } catch (\Throwable $e) {
                    Log::error("[PointsRelease] uid={$user['uid']} 释放失败: " . $e->getMessage());
                }
            }

            $page++;
        } while (count($users) === $limit);

        Log::info("[PointsRelease] 完成，processed={$processed} total_released={$totalReleased}");

        return [
            'processed'     => $processed,
            'total_released' => $totalReleased,
            'release_date'  => $releaseDate,
        ];
    }
}
