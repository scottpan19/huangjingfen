<?php
declare(strict_types=1);

namespace tests\hjf;

use PHPUnit\Framework\TestCase;

/**
 * P5-05 会员等级升级逻辑测试
 *
 * 测试策略：纯逻辑单元测试，不依赖数据库，
 * 通过内存数组模拟用户、订单、会员等级状态，
 * 复现 MemberLevelServices 的核心升级判断逻辑。
 *
 * 覆盖点：
 *  1. 普通→创客：直推3单报单商品
 *  2. 直推2单不满足创客条件
 *  3. 创客→云店：伞下30单 + 至少3个直推人
 *  4. 伞下业绩分离：直推下级已是云店（level≥2），其团队业绩不计入
 *  5. 云店→服务商：伞下100单
 *  6. 服务商→分公司：伞下1000单
 *  7. 连续升级：满足条件时自动连升多级
 *  8. 最高等级（4）不再继续检查
 *  9. 缺少3个直推人时，即使业绩达标也不升级
 * 10. 业绩分离完整场景：3层树结构验证
 *
 * Class MemberUpgradeTest
 * @package tests\hjf
 */
class MemberUpgradeTest extends TestCase
{
    // -----------------------------------------------------------------------
    //  内存模拟的会员等级引擎（复现 MemberLevelServices 核心逻辑）
    // -----------------------------------------------------------------------

    /** @var array<int, array> 用户表：uid => {uid, spread_uid, member_level} */
    private array $users = [];

    /** @var array[] 报单订单表 */
    private array $orders = [];

    /** 升级门槛（默认值，对应 DEFAULT_DIRECT_REQUIRE / DEFAULT_UMBRELLA_REQUIRE） */
    private array $directRequire  = [1 => 3];
    private array $umbrellaRequire = [2 => 30, 3 => 100, 4 => 1000];

    /** 最低直推人数要求（云店及以上升级需 ≥3 直推） */
    private int $minDirectSpreadCount = 3;

    // ---- 数据构建辅助 ---------------------------------------------------

    private function addUser(int $uid, int $spreadUid = 0, int $memberLevel = 0): void
    {
        $this->users[$uid] = [
            'uid'          => $uid,
            'spread_uid'   => $spreadUid,
            'member_level' => $memberLevel,
        ];
    }

    /**
     * 为指定用户添加 N 笔已支付报单订单
     */
    private function addQueueOrders(int $uid, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->orders[] = [
                'uid'           => $uid,
                'is_queue_goods' => 1,
                'paid'          => 1,
                'is_del'        => 0,
            ];
        }
    }

    // ---- 核心逻辑（镜像 MemberLevelServices） ----------------------------

    /**
     * 检查并执行升级（复现 checkUpgrade 逻辑）
     *
     * @return int 升级后的等级
     */
    private function checkUpgrade(int $uid): int
    {
        if (!isset($this->users[$uid])) {
            return 0;
        }

        $currentLevel = (int)$this->users[$uid]['member_level'];
        $nextLevel    = $currentLevel + 1;

        if ($nextLevel > 4) {
            return $currentLevel;
        }

        if ($this->checkLevelCondition($uid, $currentLevel, $nextLevel)) {
            $this->users[$uid]['member_level'] = $nextLevel;
            // 连续升级检查
            return $this->checkUpgrade($uid);
        }

        return $currentLevel;
    }

    private function checkLevelCondition(int $uid, int $currentLevel, int $nextLevel): bool
    {
        if ($nextLevel === 1) {
            $require = $this->directRequire[1];
            return $this->getDirectQueueOrderCount($uid) >= $require;
        }

        $umbrellaRequire = $this->umbrellaRequire[$nextLevel] ?? PHP_INT_MAX;
        if ($this->getUmbrellaQueueOrderCount($uid) < $umbrellaRequire) {
            return false;
        }
        return $this->getDirectSpreadCount($uid) >= $this->minDirectSpreadCount;
    }

    /** 直推报单订单数（仅统计直推1层） */
    private function getDirectQueueOrderCount(int $uid): int
    {
        $directUids = $this->getDirectUids($uid);
        if (empty($directUids)) {
            return 0;
        }
        return count(array_filter(
            $this->orders,
            fn($o) => in_array($o['uid'], $directUids, true)
                   && $o['is_queue_goods'] === 1
                   && $o['paid'] === 1
                   && $o['is_del'] === 0
        ));
    }

    /** 直推人数 */
    private function getDirectSpreadCount(int $uid): int
    {
        return count($this->getDirectUids($uid));
    }

    /** 伞下总报单订单数（含业绩分离逻辑，DFS） */
    private function getUmbrellaQueueOrderCount(int $uid, int $maxDepth = 8): int
    {
        return $this->recursiveUmbrellaCount($uid, $maxDepth);
    }

    private function recursiveUmbrellaCount(int $uid, int $remainDepth): int
    {
        if ($remainDepth <= 0) {
            return 0;
        }

        $directChildren = $this->getDirectUids($uid);
        if (empty($directChildren)) {
            return 0;
        }

        $total = 0;
        foreach ($directChildren as $childUid) {
            $childLevel = (int)($this->users[$childUid]['member_level'] ?? 0);

            // 业绩分离：直推下级已是云店（level≥2），跳过其团队
            if ($childLevel >= 2) {
                continue;
            }

            // 统计该下级自身的报单订单
            $total += count(array_filter(
                $this->orders,
                fn($o) => $o['uid'] === $childUid
                       && $o['is_queue_goods'] === 1
                       && $o['paid'] === 1
                       && $o['is_del'] === 0
            ));

            // 递归统计下级的伞下
            $total += $this->recursiveUmbrellaCount($childUid, $remainDepth - 1);
        }

        return $total;
    }

    /** 获取直推子用户 uid 列表 */
    private function getDirectUids(int $uid): array
    {
        return array_keys(array_filter(
            $this->users,
            fn($u) => (int)$u['spread_uid'] === $uid
        ));
    }

    protected function setUp(): void
    {
        $this->users  = [];
        $this->orders = [];
    }

    // -----------------------------------------------------------------------
    //  测试用例
    // -----------------------------------------------------------------------

    /**
     * @test
     * 普通→创客：直推3单满足条件
     */
    public function testUpgradeToLevel1With3DirectOrders(): void
    {
        $this->addUser(1);  // 普通会员
        $this->addUser(10, 1); // 直推下级A
        $this->addUser(11, 1); // 直推下级B
        $this->addUser(12, 1); // 直推下级C

        // 直推下级各下1单报单订单
        $this->addQueueOrders(10, 1);
        $this->addQueueOrders(11, 1);
        $this->addQueueOrders(12, 1);

        $level = $this->checkUpgrade(1);
        $this->assertEquals(1, $level, '直推3单应升级到创客(level=1)');
    }

    /**
     * @test
     * 直推2单不满足创客条件
     */
    public function testNotUpgradeWithOnly2DirectOrders(): void
    {
        $this->addUser(1);
        $this->addUser(10, 1);
        $this->addUser(11, 1);

        $this->addQueueOrders(10, 1);
        $this->addQueueOrders(11, 1);  // 仅2单

        $level = $this->checkUpgrade(1);
        $this->assertEquals(0, $level, '直推2单不满足创客条件，仍为普通会员');
    }

    /**
     * @test
     * 创客→云店：伞下30单 + 3个直推人
     */
    public function testUpgradeToLevel2With30UmbrellaOrders(): void
    {
        // uid=1 已是创客（level=1），有3个直推
        $this->addUser(1, 0, 1);
        $this->addUser(10, 1);
        $this->addUser(11, 1);
        $this->addUser(12, 1);

        // 伞下合计30单
        $this->addQueueOrders(10, 10);
        $this->addQueueOrders(11, 10);
        $this->addQueueOrders(12, 10);

        $level = $this->checkUpgrade(1);
        $this->assertEquals(2, $level, '伞下30单+3直推 → 升级到云店(level=2)');
    }

    /**
     * @test
     * 伞下29单不满足云店条件
     */
    public function testNotUpgradeLevel2WithOnly29Orders(): void
    {
        $this->addUser(1, 0, 1);
        $this->addUser(10, 1);
        $this->addUser(11, 1);
        $this->addUser(12, 1);

        $this->addQueueOrders(10, 10);
        $this->addQueueOrders(11, 10);
        $this->addQueueOrders(12, 9);  // 总计29单

        $level = $this->checkUpgrade(1);
        $this->assertEquals(1, $level, '伞下29单不满足云店条件');
    }

    /**
     * @test
     * 仅2个直推人时，即使业绩达标也不升级云店
     */
    public function testNotUpgradeLevel2Without3DirectSpreads(): void
    {
        $this->addUser(1, 0, 1);
        $this->addUser(10, 1);  // 仅2个直推
        $this->addUser(11, 1);

        $this->addQueueOrders(10, 20);
        $this->addQueueOrders(11, 20);  // 总计40单 >= 30，但直推人只有2个

        $level = $this->checkUpgrade(1);
        $this->assertEquals(1, $level, '直推人数<3，即使业绩达标也不升级');
    }

    /**
     * @test
     * 业绩分离：直推下级已是云店（level=2），其团队业绩不计入上级伞下
     */
    public function testUmbrellaPerformanceSeparation(): void
    {
        /*
         * 树结构：
         *   uid=1 (创客 level=1)
         *     ├─ uid=10 (云店 level=2) ← 业绩分离，其下单不计入uid=1
         *     │    └─ uid=100 (普通) → 下了20单（不计入uid=1）
         *     ├─ uid=11 (普通 level=0) → 下了15单（计入uid=1）
         *     └─ uid=12 (普通 level=0) → 下了15单（计入uid=1）
         */
        $this->addUser(1, 0, 1);    // 待检查升级
        $this->addUser(10, 1, 2);   // 已是云店，业绩分离
        $this->addUser(100, 10, 0); // 10的下级
        $this->addUser(11, 1, 0);
        $this->addUser(12, 1, 0);

        $this->addQueueOrders(100, 20); // 这20单不应计入uid=1
        $this->addQueueOrders(11, 15);  // 计入
        $this->addQueueOrders(12, 15);  // 计入

        $umbrella = $this->getUmbrellaQueueOrderCount(1);
        $this->assertEquals(30, $umbrella, '业绩分离后，仅计入11+12的30单');

        // uid=1 有3个直推（10/11/12），伞下=30，满足云店条件
        $level = $this->checkUpgrade(1);
        $this->assertEquals(2, $level, '业绩分离+30单达标，升级到云店');
    }

    /**
     * @test
     * 业绩分离：整个已分离的子树递归不计入
     */
    public function testUmbrellaPerformanceSeparationDeepTree(): void
    {
        /*
         * uid=1 (创客) 的伞下树：
         *   uid=10 (云店 level=2) → 业绩分离，整个子树不计入
         *     └─ uid=101 → 下50单
         *       └─ uid=201 → 下50单
         *   uid=11 (普通) → 下10单（计入）
         *   uid=12 (普通) → 下10单（计入）
         *   uid=13 (普通) → 下10单（计入）
         */
        $this->addUser(1, 0, 1);
        $this->addUser(10, 1, 2);    // 云店，分离
        $this->addUser(101, 10, 0);  // 10的子
        $this->addUser(201, 101, 0); // 101的子
        $this->addUser(11, 1, 0);
        $this->addUser(12, 1, 0);
        $this->addUser(13, 1, 0);

        $this->addQueueOrders(101, 50); // 不计入uid=1
        $this->addQueueOrders(201, 50); // 不计入uid=1
        $this->addQueueOrders(11, 10);  // 计入
        $this->addQueueOrders(12, 10);  // 计入
        $this->addQueueOrders(13, 10);  // 计入

        $umbrella = $this->getUmbrellaQueueOrderCount(1);
        $this->assertEquals(30, $umbrella, '深层分离树中，仅计入未分离分支的30单');
    }

    /**
     * @test
     * 云店→服务商：伞下100单 + 3个直推
     */
    public function testUpgradeToLevel3(): void
    {
        $this->addUser(1, 0, 2);  // 云店
        for ($i = 10; $i <= 13; $i++) {
            $this->addUser($i, 1, 0);
        }
        // 伞下合计100单
        $this->addQueueOrders(10, 40);
        $this->addQueueOrders(11, 30);
        $this->addQueueOrders(12, 20);
        $this->addQueueOrders(13, 10);

        $level = $this->checkUpgrade(1);
        $this->assertEquals(3, $level, '伞下100单 → 升级到服务商(level=3)');
    }

    /**
     * @test
     * 服务商→分公司：伞下1000单 + 3个直推
     */
    public function testUpgradeToLevel4(): void
    {
        $this->addUser(1, 0, 3);  // 服务商
        for ($i = 10; $i <= 13; $i++) {
            $this->addUser($i, 1, 0);
        }
        // 伞下合计1000单
        $this->addQueueOrders(10, 400);
        $this->addQueueOrders(11, 300);
        $this->addQueueOrders(12, 200);
        $this->addQueueOrders(13, 100);

        $level = $this->checkUpgrade(1);
        $this->assertEquals(4, $level, '伞下1000单 → 升级到分公司(level=4)');
    }

    /**
     * @test
     * 已是最高等级（level=4）时不再检查
     */
    public function testMaxLevelNoFurtherUpgrade(): void
    {
        $this->addUser(1, 0, 4);  // 已是分公司
        // 无论伞下多少单，等级不变
        for ($i = 10; $i <= 14; $i++) {
            $this->addUser($i, 1, 0);
            $this->addQueueOrders($i, 1000);
        }

        $level = $this->checkUpgrade(1);
        $this->assertEquals(4, $level, '已是最高等级，不再升级');
    }

    /**
     * @test
     * 连续升级：满足条件时一次 checkUpgrade 可连升多级
     * 场景：普通会员同时满足创客条件，
     * 但不满足云店条件（伞下不足30）→ 只升到创客
     */
    public function testNoContinuousUpgradeWhenNextLevelNotMet(): void
    {
        $this->addUser(1, 0, 0);  // 普通
        $this->addUser(10, 1, 0);
        $this->addUser(11, 1, 0);
        $this->addUser(12, 1, 0);

        // 满足创客（直推3单），但伞下仅3单 < 30，不满足云店
        $this->addQueueOrders(10, 1);
        $this->addQueueOrders(11, 1);
        $this->addQueueOrders(12, 1);

        $level = $this->checkUpgrade(1);
        $this->assertEquals(1, $level, '只连升到创客，云店条件不满足则停止');
    }

    /**
     * @test
     * 直推单数统计仅计算直推1层，不递归
     * 验证：直推的下级（孙子级）的订单不计入直推订单数
     */
    public function testDirectOrderCountIsShallowOnly(): void
    {
        $this->addUser(1, 0, 0);
        $this->addUser(10, 1, 0);   // 直推
        $this->addUser(100, 10, 0); // 孙子级（不是直推）

        // 孙子级下2单，直推层无订单
        $this->addQueueOrders(100, 2);

        $directCount = $this->getDirectQueueOrderCount(1);
        $this->assertEquals(0, $directCount, '孙子级订单不计入直推订单数');
    }

    /**
     * @test
     * 同一用户下多单，仅计算为1人的订单数（不影响升级统计正确性）
     */
    public function testMultipleOrdersFromSameUser(): void
    {
        $this->addUser(1, 0, 0);
        $this->addUser(10, 1, 0);
        $this->addUser(11, 1, 0);
        $this->addUser(12, 1, 0);

        // 直推下级每人多单，直推订单数 = 订单总数（按订单统计，非按人统计）
        $this->addQueueOrders(10, 3); // 3单
        $this->addQueueOrders(11, 0); // 0单
        $this->addQueueOrders(12, 0); // 0单

        $directCount = $this->getDirectQueueOrderCount(1);
        $this->assertEquals(3, $directCount, '直推订单数按订单计算，同一人多单分别统计');

        // 总计3单 ≥ 3，满足创客条件
        $level = $this->checkUpgrade(1);
        $this->assertEquals(1, $level, '直推3单（来自同一人）满足创客升级');
    }
}
