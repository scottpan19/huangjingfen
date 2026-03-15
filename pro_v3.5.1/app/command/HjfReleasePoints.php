<?php
declare(strict_types=1);

namespace app\command;

use app\services\hjf\PointsReleaseServices;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 积分每日释放命令
 *
 * 用法：
 *   php think hjf:release-points
 *
 * 触发时机：
 *   - 每天凌晨 00:01 由 crontab 或 Swoole Timer 调用
 *   - P4-05 联调验证时手动执行
 *
 * Class HjfReleasePoints
 * @package app\command
 */
class HjfReleasePoints extends Command
{
    protected function configure(): void
    {
        $this->setName('hjf:release-points')
            ->setDescription('执行黄精粉健康商城每日积分释放（frozen_points × 4‰ → available_points）');
    }

    protected function execute(Input $input, Output $output): int
    {
        $output->writeln('[HjfReleasePoints] 开始执行积分释放...');

        /** @var PointsReleaseServices $service */
        $service = app()->make(PointsReleaseServices::class);
        $result  = $service->executeRelease();

        $output->writeln(sprintf(
            '[HjfReleasePoints] 完成：处理 %d 人，共释放 %d 积分，日期 %s',
            $result['processed'],
            $result['total_released'],
            $result['release_date']
        ));

        return 0;
    }
}
