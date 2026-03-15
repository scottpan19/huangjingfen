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
 * 积分奖励服务（级差计算）
 *
 * 触发时机：报单商品订单支付回调成功后调用 reward($orderUid, $orderId)。
 *
 * 奖励规则（PRD 3.2）：
 *  - 推荐人（直推上级）获得 直推奖励积分（按推荐人等级）
 *  - 更上级获得 级差积分（上级积分 - 直接下级已获得的积分）
 *  - 所有奖励积分写入 frozen_points（待释放状态）
 *  - 同时写 points_release_log 记录明细
 *
 * Class PointsRewardServices
 * @package app\services\hjf
 */
class PointsRewardServices extends BaseServices
{
    #[Inject]
    protected PointsReleaseLogDao $logDao;

    #[Inject]
    protected UserDao $userDao;

    /**
     * 各等级直推奖励积分配置键
     */
    const DIRECT_REWARD_KEYS = [
        0 => 0,      // 普通会员：无直推奖励
        1 => 'hjf_reward_direct_1',  // 创客
        2 => 'hjf_reward_direct_2',  // 云店
        3 => 'hjf_reward_direct_3',  // 服务商
        4 => 'hjf_reward_direct_4',  // 分公司
    ];

    /**
     * 各等级伞下奖励积分配置键
     */
    const UMBRELLA_REWARD_KEYS = [
        0 => 0,
        1 => 'hjf_reward_umbrella_1',
        2 => 'hjf_reward_umbrella_2',
        3 => 'hjf_reward_umbrella_3',
        4 => 'hjf_reward_umbrella_4',
    ];

    /**
     * 默认积分奖励（当系统配置未初始化时使用）
     */
    const DEFAULT_DIRECT  = [0 => 0, 1 => 500, 2 => 800, 3 => 1000, 4 => 1300];
    const DEFAULT_UMBRELLA = [0 => 0, 1 => 0,   2 => 300, 3 => 200,  4 => 300];

    /**
     * 对一笔报单订单发放积分奖励
     *
     * @param int    $orderUid  下单用户 ID
     * @param string $orderId   订单号
     */
    public function reward(int $orderUid, string $orderId): void
    {
        try {
            // 获取下单用户信息
            $buyer = $this->userDao->get($orderUid);
            if (!$buyer || !$buyer['spread_uid']) {
                return; // 无推荐人，不发奖励
            }

            // 沿推荐链向上遍历，计算级差奖励
            $this->propagateReward($buyer['spread_uid'], $orderUid, $orderId, 0);
        } catch (\Throwable $e) {
            Log::error("[PointsReward] 积分奖励失败 orderUid={$orderUid} orderId={$orderId}: " . $e->getMessage());
        }
    }

    /**
     * 向上递归发放级差积分
     *
     * @param int    $uid            当前被奖励用户
     * @param int    $fromUid        触发方（下级）用户 ID
     * @param string $orderId        来源订单号
     * @param int    $lowerReward    下级已获得的直推/伞下奖励积分（用于级差扣减）
     * @param int    $depth          递归深度（最多遍历10层）
     */
    private function propagateReward(
        int    $uid,
        int    $fromUid,
        string $orderId,
        int    $lowerReward,
        int    $depth = 0
    ): void {
        if ($depth >= 10 || $uid <= 0) {
            return;
        }

        $user = $this->userDao->get($uid);
        if (!$user) {
            return;
        }

        $level = (int)($user['member_level'] ?? 0);
        if ($level === 0) {
            // 普通会员不获得奖励，但继续向上传递
            if ($user['spread_uid']) {
                $this->propagateReward((int)$user['spread_uid'], $uid, $orderId, 0, $depth + 1);
            }
            return;
        }

        // 判断是直推还是伞下（depth=0 说明是第一个上级，即直推）
        $isDirect = ($depth === 0);
        $reward   = $isDirect
            ? $this->getDirectReward($level)
            : $this->getUmbrellaReward($level);

        // 级差：本次实发 = 本等级应得 - 下级已获得
        $actual = max(0, $reward - $lowerReward);

        if ($actual > 0) {
            $this->grantFrozenPoints(
                $uid,
                $actual,
                $orderId,
                $isDirect ? 'reward_direct' : 'reward_umbrella',
                ($isDirect ? '直推奖励' : '伞下奖励(级差)') . " - 来源订单 {$orderId}"
            );
        }

        // 继续向上传递（使用本级应得的 reward 作为下一级的 lowerReward）
        if ($user['spread_uid']) {
            $this->propagateReward(
                (int)$user['spread_uid'],
                $uid,
                $orderId,
                $reward,  // 传递本级"应得"（而非实发）给上级做级差
                $depth + 1
            );
        }
    }

    /**
     * 写入待释放积分（frozen_points）并记录明细
     */
    private function grantFrozenPoints(int $uid, int $points, string $orderId, string $type, string $mark): void
    {
        Db::transaction(function () use ($uid, $points, $orderId, $type, $mark) {
            // 增加 frozen_points
            $this->userDao->bcInc($uid, 'frozen_points', $points, 'uid');

            // 写明细日志
            $this->logDao->save([
                'uid'      => $uid,
                'points'   => $points,
                'pm'       => 1,
                'type'     => $type,
                'title'    => ($type === 'reward_direct') ? '直推奖励' : '伞下奖励',
                'mark'     => $mark,
                'status'   => 'frozen',
                'order_id' => $orderId,
            ]);
        });
    }

    private function getDirectReward(int $level): int
    {
        $key = self::DIRECT_REWARD_KEYS[$level] ?? 0;
        if (!$key) {
            return self::DEFAULT_DIRECT[$level] ?? 0;
        }
        return (int)SystemConfigService::get($key, self::DEFAULT_DIRECT[$level] ?? 0);
    }

    private function getUmbrellaReward(int $level): int
    {
        $key = self::UMBRELLA_REWARD_KEYS[$level] ?? 0;
        if (!$key) {
            return self::DEFAULT_UMBRELLA[$level] ?? 0;
        }
        return (int)SystemConfigService::get($key, self::DEFAULT_UMBRELLA[$level] ?? 0);
    }
}
