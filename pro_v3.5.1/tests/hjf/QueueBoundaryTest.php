<?php
declare(strict_types=1);

namespace tests\hjf;

use PHPUnit\Framework\TestCase;

/**
 * P5-03 公排边界测试
 *
 * 测试策略：纯逻辑单元测试，不依赖数据库/Redis，
 * 通过内存数组模拟 QueuePool 状态，验证业务规则。
 *
 * 覆盖点：
 *  1. 第4单入队触发退款（triggerMultiple=4）
 *  2. 前3单入队不触发退款
 *  3. 退款对象是最早入队（queue_no最小）的记录
 *  4. 退款后该记录 status=1，不再参与后续触发统计
 *  5. 第5~8单仍旧触发下一批退款（第5单触发第2单退款）
 *  6. 并发重复提交同一 order_id 只入队一次（幂等性）
 *  7. 边界值：triggerMultiple=1（每单立即退款）
 *  8. 退款金额与入队金额一致（无精度损失）
 *
 * Class QueueBoundaryTest
 * @package tests\hjf
 */
class QueueBoundaryTest extends TestCase
{
    // -----------------------------------------------------------------------
    //  内存模拟的公排引擎（复现 QueuePoolServices 核心逻辑）
    // -----------------------------------------------------------------------

    /** @var array[] 公排记录 */
    private array $pool = [];

    /** @var array[] 退款记录（模拟 QueueRefundJob 执行结果） */
    private array $refunds = [];

    /** @var int 全局序号计数器 */
    private int $nextQueueNo = 1;

    /** @var int 批次计数器 */
    private int $batchNo = 0;

    /** @var int 并发锁（0=未锁，1=已锁） */
    private int $lock = 0;

    /**
     * 入队（模拟 QueuePoolServices::enqueue）
     *
     * @param int    $uid      用户 ID
     * @param string $orderId  订单号
     * @param float  $amount   金额
     * @param int    $multiple 触发倍数
     * @return array 入队记录
     * @throws \RuntimeException 并发锁冲突
     */
    private function enqueue(int $uid, string $orderId, float $amount, int $multiple = 4): array
    {
        // 模拟 Redis SET NX 锁
        if ($this->lock === 1) {
            throw new \RuntimeException('公排入队繁忙，请稍后重试');
        }
        $this->lock = 1;

        try {
            // 幂等检查：同一 orderId 不重复入队
            foreach ($this->pool as $r) {
                if ($r['order_id'] === $orderId) {
                    throw new \RuntimeException("订单 {$orderId} 已入队，不可重复");
                }
            }

            $record = [
                'id'            => count($this->pool) + 1,
                'uid'           => $uid,
                'order_id'      => $orderId,
                'amount'        => $amount,
                'queue_no'      => $this->nextQueueNo++,
                'status'        => 0,  // 0=排队中
                'refund_time'   => 0,
                'trigger_batch' => 0,
                'add_time'      => time(),
            ];
            $this->pool[] = $record;

            // 触发退款检查
            $this->checkAndTriggerRefund($multiple);

            return $record;
        } finally {
            $this->lock = 0;
        }
    }

    /**
     * 检查并触发退款（模拟 QueuePoolServices::checkAndTriggerRefund）
     */
    private function checkAndTriggerRefund(int $multiple): void
    {
        $pending = $this->countPending();
        if ($pending < $multiple) {
            return;
        }
        $earliest = $this->getEarliestPending();
        if (!$earliest) {
            return;
        }
        $this->batchNo++;
        $this->executeRefund($earliest['id'], $earliest['uid'], $earliest['amount'], $this->batchNo);
    }

    /** 统计排队中（status=0）的记录数 */
    private function countPending(): int
    {
        return count(array_filter($this->pool, fn($r) => $r['status'] === 0));
    }

    /** 获取最早排队中记录（queue_no 最小） */
    private function getEarliestPending(): ?array
    {
        $pending = array_filter($this->pool, fn($r) => $r['status'] === 0);
        if (empty($pending)) {
            return null;
        }
        usort($pending, fn($a, $b) => $a['queue_no'] <=> $b['queue_no']);
        return reset($pending);
    }

    /**
     * 执行退款（模拟 QueueRefundJob::doJob，二次检查幂等）
     */
    private function executeRefund(int $id, int $uid, float $amount, int $batchNo): void
    {
        foreach ($this->pool as &$r) {
            if ($r['id'] === $id) {
                // 二次检查幂等
                if ($r['status'] === 1) {
                    return;
                }
                $r['status']        = 1;
                $r['refund_time']   = time();
                $r['trigger_batch'] = $batchNo;
                break;
            }
        }
        unset($r);

        $this->refunds[] = [
            'queue_id' => $id,
            'uid'      => $uid,
            'amount'   => $amount,
            'batch_no' => $batchNo,
        ];
    }

    /** 重置公排状态（每个测试独立） */
    protected function setUp(): void
    {
        $this->pool       = [];
        $this->refunds    = [];
        $this->nextQueueNo = 1;
        $this->batchNo    = 0;
        $this->lock       = 0;
    }

    // -----------------------------------------------------------------------
    //  测试用例
    // -----------------------------------------------------------------------

    /**
     * @test
     * 前3单入队时不触发退款（triggerMultiple=4）
     */
    public function testFirst3OrdersNoRefund(): void
    {
        $this->enqueue(1, 'ORDER-001', 3600.00);
        $this->enqueue(2, 'ORDER-002', 3600.00);
        $this->enqueue(3, 'ORDER-003', 3600.00);

        $this->assertCount(0, $this->refunds, '前3单不触发退款');
        $this->assertEquals(3, $this->countPending(), '3单全部排队中');
    }

    /**
     * @test
     * 第4单入队后触发退款（退款最早的第1单）
     */
    public function testFourthOrderTriggersRefundToFirst(): void
    {
        $this->enqueue(1, 'ORDER-001', 3600.00);
        $this->enqueue(2, 'ORDER-002', 3600.00);
        $this->enqueue(3, 'ORDER-003', 3600.00);
        $this->enqueue(4, 'ORDER-004', 3600.00);

        $this->assertCount(1, $this->refunds, '第4单触发1次退款');
        $this->assertEquals(1, $this->refunds[0]['uid'],      '退款对象是第1单用户(uid=1)');
        $this->assertEquals(3600.00, $this->refunds[0]['amount'], '退款金额正确');
        $this->assertEquals(1, $this->refunds[0]['batch_no'], '第1批次');
        $this->assertEquals(3, $this->countPending(),         '退款后剩余3单排队中');
    }

    /**
     * @test
     * 退款触发后，被退款的记录 status=1，不再排队中
     */
    public function testRefundedRecordStatusUpdated(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->enqueue($i, "ORDER-00{$i}", 3600.00);
        }

        // 第1条记录（queue_no=1）应已退款
        $firstRecord = $this->pool[0];
        $this->assertEquals(1, $firstRecord['status'],       '第1单 status=1（已退款）');
        $this->assertGreaterThan(0, $firstRecord['refund_time'], '有退款时间戳');
        $this->assertEquals(1, $firstRecord['trigger_batch'], '批次号=1');
    }

    /**
     * @test
     * 第5单入队后，pending=4，再次触发退款（退款最早排队中的第2单）
     */
    public function testFifthOrderTriggersSecondRefund(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->enqueue($i, "ORDER-00{$i}", 3600.00);
        }

        // 第4单触发退款1（退第1单），第5单入队时 pending=4，再触发退款2（退第2单）
        $this->assertCount(2, $this->refunds, '共发生2次退款');
        $this->assertEquals(1, $this->refunds[0]['uid'], '第1次退款是uid=1');
        $this->assertEquals(2, $this->refunds[1]['uid'], '第2次退款是uid=2');
        $this->assertEquals(2, $this->refunds[1]['batch_no'], '第2批次');
    }

    /**
     * @test
     * 8单入队共触发2次退款（每4单1次）
     */
    public function testEightOrdersTriggerTwoRefunds(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $this->enqueue($i, "ORDER-00{$i}", 3600.00);
        }

        $this->assertCount(2, $this->refunds, '8单触发2次退款');
        $this->assertEquals(1, $this->refunds[0]['uid'], '退款1：uid=1');
        $this->assertEquals(2, $this->refunds[1]['uid'], '退款2：uid=2');
    }

    /**
     * @test
     * 同一 orderId 重复提交，第二次应抛出异常（幂等性保证）
     */
    public function testDuplicateOrderIdRejected(): void
    {
        $this->enqueue(1, 'ORDER-DUP', 3600.00);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/已入队/');

        $this->enqueue(1, 'ORDER-DUP', 3600.00);
    }

    /**
     * @test
     * 并发锁：锁已持有时入队应抛出异常
     */
    public function testConcurrentLockPreventsDoubleEntry(): void
    {
        // 手动持锁，模拟另一个请求正在处理
        $this->lock = 1;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/繁忙/');

        $this->enqueue(1, 'ORDER-LOCK', 3600.00);
    }

    /**
     * @test
     * 退款金额精度：3600.00 与 3600 相同，浮点无损
     */
    public function testRefundAmountPrecision(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->enqueue($i, "ORDER-A{$i}", 3600.00);
        }

        $refundAmount = $this->refunds[0]['amount'];
        $this->assertSame(3600.00, $refundAmount, '退款金额为精确的 3600.00');
        // bcadd 精度验证
        $bcResult = bcadd((string)$refundAmount, '0', 2);
        $this->assertEquals('3600.00', $bcResult, 'bcadd 精度正确');
    }

    /**
     * @test
     * triggerMultiple=1：每单立即退款（边界值测试）
     */
    public function testTriggerMultipleEqualsOne(): void
    {
        $multiple = 1;
        $this->enqueue(1, 'ORDER-M1', 3600.00, $multiple);
        $this->assertCount(1, $this->refunds, 'triggerMultiple=1 时第1单即触发退款');
        $this->assertEquals(1, $this->refunds[0]['uid']);

        $this->enqueue(2, 'ORDER-M2', 3600.00, $multiple);
        $this->assertCount(2, $this->refunds, '第2单也立即触发退款');
    }

    /**
     * @test
     * QueueRefundJob 幂等性：对已退款记录重复执行不产生第二条退款记录
     */
    public function testRefundJobIdempotent(): void
    {
        // 入队4单触发1次退款
        for ($i = 1; $i <= 4; $i++) {
            $this->enqueue($i, "ORDER-IDEM{$i}", 3600.00);
        }
        $this->assertCount(1, $this->refunds, '初始退款1次');

        // 对已退款记录（id=1, status=1）再次调用 executeRefund
        $firstRecord = $this->pool[0];
        $this->assertEquals(1, $firstRecord['status'], '确认已退款');

        $refundsBefore = count($this->refunds);
        $this->executeRefund($firstRecord['id'], $firstRecord['uid'], $firstRecord['amount'], 99);

        $this->assertCount($refundsBefore, $this->refunds, '幂等：重复退款不增加退款记录');
    }

    /**
     * @test
     * 批量压力：100单入队，应恰好触发25次退款（100 / 4 = 25）
     */
    public function testBulkEnqueueTriggerCount(): void
    {
        for ($i = 1; $i <= 100; $i++) {
            $this->enqueue($i % 10 + 1, sprintf('ORDER-%03d', $i), 3600.00);
        }

        $this->assertCount(25, $this->refunds, '100单触发25次退款');
        $this->assertEquals(25, $this->countPending(), '剩余25单排队中');

        // 验证退款批次连续
        $batches = array_column($this->refunds, 'batch_no');
        $this->assertEquals(range(1, 25), $batches, '退款批次从1连续递增到25');
    }
}
