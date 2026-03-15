<?php
declare(strict_types=1);

namespace app\services\hjf;

use app\dao\user\UserDao;
use app\services\BaseServices;
use crmeb\services\SystemConfigService;
use think\annotation\Inject;
use think\facade\Db;
use think\facade\Log;

/**
 * 会员等级升级服务
 *
 * 升级条件（PRD 3.2.1）：
 *  - 普通会员 → 创客：直推3单（hjf_level_direct_require_1，默认3）
 *  - 创客 → 云店：伞下业绩30单 + 至少3个直推（hjf_level_umbrella_require_2，默认30）
 *  - 云店 → 服务商：伞下业绩100单 + 至少3个直推（hjf_level_umbrella_require_3，默认100）
 *  - 服务商 → 分公司：伞下业绩1000单 + 至少3个直推（hjf_level_umbrella_require_4，默认1000）
 *
 * 伞下业绩分离：当某直推下级已升级到云店（level≥2）后，
 *   该下级及其整个团队的业绩不再计入本级的伞下业绩。
 *
 * Class MemberLevelServices
 * @package app\services\hjf
 */
class MemberLevelServices extends BaseServices
{
    #[Inject]
    protected UserDao $userDao;

    /**
     * 各等级升级所需直推单数（0→1升级条件）
     */
    const DIRECT_REQUIRE_KEYS = [
        1 => 'hjf_level_direct_require_1',   // 普通→创客：直推N单
    ];

    /**
     * 各等级升级所需伞下单数（n-1→n升级条件，n≥2）
     */
    const UMBRELLA_REQUIRE_KEYS = [
        2 => 'hjf_level_umbrella_require_2',  // 创客→云店
        3 => 'hjf_level_umbrella_require_3',  // 云店→服务商
        4 => 'hjf_level_umbrella_require_4',  // 服务商→分公司
    ];

    /**
     * 默认升级门槛
     */
    const DEFAULT_DIRECT_REQUIRE  = [1 => 3];
    const DEFAULT_UMBRELLA_REQUIRE = [2 => 30, 3 => 100, 4 => 1000];

    /**
     * 最低直推人数要求（云店及以上需要至少3个直推）
     */
    const MIN_DIRECT_SPREAD_COUNT = 3;

    /**
     * 检查并执行升级（异步触发入口）
     *
     * @param int $uid  被检查的用户 ID
     */
    public function checkUpgrade(int $uid): void
    {
        try {
            $user = $this->userDao->get($uid);
            if (!$user) {
                return;
            }

            $currentLevel = (int)($user['member_level'] ?? 0);
            $nextLevel    = $currentLevel + 1;

            if ($nextLevel > 4) {
                return; // 已是最高等级
            }

            $qualified = $this->checkLevelCondition($uid, $currentLevel, $nextLevel);
            if ($qualified) {
                $this->upgrade($uid, $nextLevel);

                // 升级后继续检查是否可连续升级
                $this->checkUpgrade($uid);
            }
        } catch (\Throwable $e) {
            Log::error("[MemberLevel] checkUpgrade uid={$uid}: " . $e->getMessage());
        }
    }

    /**
     * 检查用户是否满足从 currentLevel 升到 nextLevel 的条件
     */
    private function checkLevelCondition(int $uid, int $currentLevel, int $nextLevel): bool
    {
        if ($nextLevel === 1) {
            // 普通→创客：统计直推报单数
            $require = $this->getDirectRequire(1);
            $count   = $this->getDirectQueueOrderCount($uid);
            return $count >= $require;
        }

        // 创客/云店/服务商→更高等级：伞下业绩 + 至少3个直推
        $umbrellaRequire = $this->getUmbrellaRequire($nextLevel);
        $umbrellaCount   = $this->getUmbrellaQueueOrderCount($uid);

        if ($umbrellaCount < $umbrellaRequire) {
            return false;
        }

        // 需要至少3个直推（对 level≥2 的升级）
        $directCount = $this->getDirectSpreadCount($uid);
        return $directCount >= self::MIN_DIRECT_SPREAD_COUNT;
    }

    /**
     * 获取直推用户的报单订单数（直推层级 = 1 层）
     *
     * 报单商品标记：`is_queue_goods = 1`（eb_store_order 中的字段）
     */
    public function getDirectQueueOrderCount(int $uid): int
    {
        // 查询直推用户 uid 列表
        $directUids = $this->userDao->getColumn(['spread_uid' => $uid], 'uid');
        if (empty($directUids)) {
            return 0;
        }

        return (int)Db::name('store_order')
            ->whereIn('uid', $directUids)
            ->where('is_queue_goods', 1)
            ->where('paid', 1)
            ->where('is_del', 0)
            ->count();
    }

    /**
     * 获取直推人数
     */
    public function getDirectSpreadCount(int $uid): int
    {
        return (int)$this->userDao->count(['spread_uid' => $uid]);
    }

    /**
     * 获取伞下总报单订单数（含业绩分离逻辑）
     *
     * 业绩分离：若某直推下级已升级为云店（level≥2），
     * 则该下级及其团队的订单不计入本用户的伞下业绩。
     *
     * @param int $uid          统计对象用户 ID
     * @param int $maxDepth     递归最大深度，防止死循环
     */
    public function getUmbrellaQueueOrderCount(int $uid, int $maxDepth = 8): int
    {
        return $this->recursiveUmbrellaCount($uid, $maxDepth);
    }

    /**
     * 递归统计伞下业绩（DFS）
     */
    private function recursiveUmbrellaCount(int $uid, int $remainDepth): int
    {
        if ($remainDepth <= 0) {
            return 0;
        }

        $directChildren = $this->userDao->selectList(
            ['spread_uid' => $uid],
            'uid,member_level',
            0, 0, 'uid', 'asc'
        );

        if (empty($directChildren)) {
            return 0;
        }

        $total = 0;
        foreach ($directChildren as $child) {
            $childLevel = (int)($child['member_level'] ?? 0);

            // 业绩分离：直推下级已是云店或以上（level≥2），其团队业绩不计入本级
            if ($childLevel >= 2) {
                continue;
            }

            // 统计该下级自身的报单订单数
            $total += (int)Db::name('store_order')
                ->where('uid', $child['uid'])
                ->where('is_queue_goods', 1)
                ->where('paid', 1)
                ->where('is_del', 0)
                ->count();

            // 递归统计该下级的伞下
            $total += $this->recursiveUmbrellaCount((int)$child['uid'], $remainDepth - 1);
        }

        return $total;
    }

    /**
     * 执行升级
     *
     * @param int $uid       用户 ID
     * @param int $newLevel  新等级
     */
    public function upgrade(int $uid, int $newLevel): void
    {
        Db::transaction(function () use ($uid, $newLevel) {
            $this->userDao->update($uid, ['member_level' => $newLevel], 'uid');

            Log::info("[MemberLevel] uid={$uid} 升级到 level={$newLevel}");
        });

        // 升级后通知推荐链上级重新检查
        $user = $this->userDao->get($uid);
        if ($user && $user['spread_uid']) {
            // 异步检查上级升级（防止递归过深直接调用）
            try {
                app(\app\jobs\hjf\MemberLevelCheckJob::class)::dispatch($user['spread_uid']);
            } catch (\Throwable $e) {
                Log::warning("[MemberLevel] 无法派发上级检查 Job: " . $e->getMessage());
            }
        }
    }

    private function getDirectRequire(int $level): int
    {
        $key = self::DIRECT_REQUIRE_KEYS[$level] ?? '';
        if (!$key) {
            return self::DEFAULT_DIRECT_REQUIRE[$level] ?? 3;
        }
        return (int)SystemConfigService::get($key, self::DEFAULT_DIRECT_REQUIRE[$level] ?? 3);
    }

    private function getUmbrellaRequire(int $level): int
    {
        $key = self::UMBRELLA_REQUIRE_KEYS[$level] ?? '';
        if (!$key) {
            return self::DEFAULT_UMBRELLA_REQUIRE[$level] ?? 9999;
        }
        return (int)SystemConfigService::get($key, self::DEFAULT_UMBRELLA_REQUIRE[$level] ?? 9999);
    }
}
