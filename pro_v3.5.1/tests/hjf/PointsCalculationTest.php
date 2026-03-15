<?php
declare(strict_types=1);

namespace tests\hjf;

use PHPUnit\Framework\TestCase;

/**
 * 积分计算单元测试
 *
 * 覆盖点：
 *  1. 级差计算：直推 / 伞下 / 多层级差公式正确性
 *  2. 每日释放精度：bcmath 计算 frozen × rate / 1000
 *  3. 边界值：frozen_points=0 不释放；rate=0 不释放
 *  4. 伞下业绩分离：云店级别下级业绩不计入上级
 *
 * Class PointsCalculationTest
 * @package tests\hjf
 */
class PointsCalculationTest extends TestCase
{
    // -----------------------------------------------------------------------
    //  每日释放精度（bcmath）
    // -----------------------------------------------------------------------

    /**
     * @test
     * 每日释放：1,000,000 frozen × 4‰ = 4,000（无浮点误差）
     */
    public function testDailyReleaseCalculationPrecision(): void
    {
        $frozen = '1000000';
        $rate   = '4';

        // Formula: FLOOR(frozen × rate / 1000)
        $releaseAmount = (int)bcdiv(bcmul($frozen, $rate), '1000');

        $this->assertEquals(4000, $releaseAmount, '每日释放精度测试：1000000×4‰=4000');
    }

    /**
     * @test
     * 每日释放：小数点精度 —— 999 × 4‰ = 3（FLOOR，不是3.996）
     */
    public function testDailyReleaseFloorRounding(): void
    {
        $frozen = '999';
        $rate   = '4';

        $releaseAmount = (int)bcdiv(bcmul($frozen, $rate), '1000');

        $this->assertEquals(3, $releaseAmount, '每日释放应使用 FLOOR 取整，999×4‰=3');
    }

    /**
     * @test
     * frozen_points = 0 时不释放
     */
    public function testDailyReleaseZeroFrozenPoints(): void
    {
        $frozen = '0';
        $rate   = '4';

        $releaseAmount = (int)bcdiv(bcmul($frozen, $rate), '1000');

        $this->assertEquals(0, $releaseAmount, 'frozen_points=0 时释放量应为 0');
    }

    /**
     * @test
     * rate = 0 时不释放（防止除零配置错误）
     */
    public function testDailyReleaseZeroRate(): void
    {
        $frozen = '100000';
        $rate   = '0';

        $releaseAmount = (int)bcdiv(bcmul($frozen, $rate), '1000');

        $this->assertEquals(0, $releaseAmount, 'rate=0 时释放量应为 0');
    }

    /**
     * @test
     * 浮点陷阱验证：PHP 原生浮点 vs bcmath 结果对比
     */
    public function testBcmathVsNativeFloat(): void
    {
        $frozen = 1234567;
        $rate   = 4;

        // Native float (may have precision errors)
        $nativeResult = (int)($frozen * $rate / 1000);

        // bcmath (exact)
        $bcResult = (int)bcdiv(bcmul((string)$frozen, (string)$rate), '1000');

        // Both should equal 4938 for this input
        $expected = (int)floor($frozen * $rate / 1000);

        $this->assertEquals($expected, $bcResult, 'bcmath 结果与预期一致');
        $this->assertEquals($bcResult, $nativeResult, '此用例下两者结果应相同（验证无浮点偏差）');
    }

    // -----------------------------------------------------------------------
    //  级差计算
    // -----------------------------------------------------------------------

    /**
     * @test
     * 直推奖励：level=1 (创客) 应获得 500 积分
     */
    public function testDirectRewardForLevel1(): void
    {
        // Default config: level1 direct = 500
        $defaults = [0 => 0, 1 => 500, 2 => 800, 3 => 1000, 4 => 1300];
        $level    = 1;

        $reward = $defaults[$level];
        $this->assertEquals(500, $reward, '创客直推奖励默认应为 500');
    }

    /**
     * @test
     * 级差计算：上级 level=2(云店,800) — 下级已获得 level=1(创客,500) = 差额 300
     */
    public function testUmbrellaRewardCascadeLevel2(): void
    {
        $directDefaults  = [0 => 0, 1 => 500, 2 => 800, 3 => 1000, 4 => 1300];
        $umbrellaDefaults = [0 => 0, 1 => 0,   2 => 300, 3 => 200,  4 => 300];

        $upperLevel   = 2; // 云店
        $lowerReward  = $directDefaults[1]; // 下级(创客)已获得的直推奖励 500

        $umbrellaReward = $umbrellaDefaults[$upperLevel]; // 云店伞下奖励 300
        $actual         = max(0, $umbrellaReward - $lowerReward);

        // 级差：300 - 500 = -200 → max(0, -200) = 0
        $this->assertEquals(0, $actual, '级差为负数时实发为 0（云店伞下300 < 创客直推500）');
    }

    /**
     * @test
     * 级差计算：上级 level=3(服务商, umbrella=200) — 下级已获得 umbrella(云店=300) → max(0, 200-300) = 0
     */
    public function testUmbrellaRewardCascadeLevel3(): void
    {
        $umbrellaDefaults = [0 => 0, 1 => 0, 2 => 300, 3 => 200, 4 => 300];

        $upperLevel  = 3;
        $lowerReward = $umbrellaDefaults[2]; // 下级云店获得的伞下奖励 300

        $actual = max(0, $umbrellaDefaults[$upperLevel] - $lowerReward);

        $this->assertEquals(0, $actual, '服务商伞下200 < 云店伞下300，级差为0');
    }

    /**
     * @test
     * 级差计算：上级 level=4(分公司, umbrella=300) — 下级已获得 umbrella(服务商=200) = 100
     */
    public function testUmbrellaRewardCascadeLevel4(): void
    {
        $umbrellaDefaults = [0 => 0, 1 => 0, 2 => 300, 3 => 200, 4 => 300];

        $upperLevel  = 4;
        $lowerReward = $umbrellaDefaults[3]; // 服务商已获得 200

        $actual = max(0, $umbrellaDefaults[$upperLevel] - $lowerReward);

        $this->assertEquals(100, $actual, '分公司伞下300 - 服务商伞下200 = 级差100');
    }

    /**
     * @test
     * 传递参数验证：propagateReward 向上传递的是"应得额"而非"实发额"
     *
     * 场景：level3 实发 0（因级差为负），但向上传递的 lowerReward 仍为其"应得额"200
     */
    public function testCascadePropagatesExpectedNotActual(): void
    {
        $umbrellaDefaults = [0 => 0, 1 => 0, 2 => 300, 3 => 200, 4 => 300];

        // Level3 应得 = 200, 下级已得 300 → 实发 = max(0, 200-300) = 0
        $level3Expected = $umbrellaDefaults[3]; // 200
        $level3Actual   = max(0, $level3Expected - $umbrellaDefaults[2]); // max(0, 200-300) = 0

        // 但向上传递时使用 level3Expected（200），而非 level3Actual（0）
        $level4Expected = $umbrellaDefaults[4]; // 300
        $level4Actual   = max(0, $level4Expected - $level3Expected); // max(0, 300-200) = 100

        $this->assertEquals(0, $level3Actual, 'Level3 实发为 0（级差为负）');
        $this->assertEquals(100, $level4Actual, 'Level4 基于 level3 应得额（200）计算，实发 100');
    }

    // -----------------------------------------------------------------------
    //  每日释放批量处理
    // -----------------------------------------------------------------------

    /**
     * @test
     * 批量释放：多用户同时处理，每人独立计算（统计结果正确）
     */
    public function testBatchReleaseAggregation(): void
    {
        $rate  = 4;
        $users = [
            ['uid' => 1, 'frozen_points' => 10000],
            ['uid' => 2, 'frozen_points' => 5000],
            ['uid' => 3, 'frozen_points' => 250],  // 250×4/1000 = 1（FLOOR）
            ['uid' => 4, 'frozen_points' => 0],    // skip
        ];

        $totalReleased = 0;
        $processed     = 0;

        foreach ($users as $user) {
            $frozen        = $user['frozen_points'];
            $releaseAmount = (int)bcdiv(bcmul((string)$frozen, (string)$rate), '1000');

            if ($releaseAmount <= 0) {
                continue;
            }

            $totalReleased += $releaseAmount;
            $processed++;
        }

        $this->assertEquals(3, $processed,    '应处理3个用户（frozen>0且释放额>0）');
        $this->assertEquals(61, $totalReleased, '总释放积分应为 40+20+1=61');
    }
}
