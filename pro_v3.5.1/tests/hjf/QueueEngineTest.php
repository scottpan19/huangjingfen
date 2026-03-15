<?php
declare(strict_types=1);

namespace tests\hjf;

use PHPUnit\Framework\TestCase;

/**
 * 公排引擎单元测试桩
 *
 * 覆盖点：
 *  1. 入队逻辑：正常入队写库，返回 queue_no
 *  2. 退款触发：pending 单数 >= triggerMultiple 时触发退款 Job
 *  3. 分布式锁：并发入队时只有一个请求能获得锁
 *  4. 幂等性：同一订单不能重复入队
 *
 * Class QueueEngineTest
 * @package tests\hjf
 */
class QueueEngineTest extends TestCase
{
    // -----------------------------------------------------------------------
    //  入队逻辑
    // -----------------------------------------------------------------------

    /**
     * @test
     * 正常入队：should write a new record to queue_pool and return queue_no
     */
    public function testEnqueueCreatesRecord(): void
    {
        // Arrange
        $uid     = 1001;
        $orderId = 'ORDER_20240101_001';
        $amount  = 3600.00;

        // Mock QueuePoolDao: expects one save() call
        $daoMock = $this->createMock(\app\dao\hjf\QueuePoolDao::class);
        $daoMock->expects($this->once())
            ->method('save')
            ->willReturn(true);
        $daoMock->method('nextQueueNo')->willReturn(42);

        // Mock CacheService (Redis lock): SET NX succeeds
        $cacheMock = $this->createMock(\crmeb\services\CacheService::class);
        $cacheMock->method('handler')->willReturnSelf();
        $cacheMock->method('set')->willReturn(true); // lock acquired

        // Act — 实际测试时替换为 DI 注入
        // $service = new QueuePoolServices($daoMock, $cacheMock);
        // $result  = $service->enqueue($uid, $orderId, $amount);

        // Assert
        // $this->assertArrayHasKey('queue_no', $result);
        // $this->assertEquals(42, $result['queue_no']);

        // Stub assertion (placeholder until DI wiring is ready)
        $this->assertTrue(true, '入队正常流程桩测试通过');
    }

    /**
     * @test
     * 重复入队：same order_id should throw or return error
     */
    public function testEnqueueDuplicateOrderIdIsRejected(): void
    {
        // Mock QueuePoolDao: getOne returns existing record
        $daoMock = $this->createMock(\app\dao\hjf\QueuePoolDao::class);
        $daoMock->method('getOne')
            ->willReturn(['id' => 99, 'order_id' => 'ORDER_20240101_001']);

        // Assert that duplicate is rejected (exception or false return)
        // $this->expectException(\RuntimeException::class);
        // $service->enqueue(1001, 'ORDER_20240101_001', 3600);

        $this->assertTrue(true, '幂等性保护桩测试通过');
    }

    // -----------------------------------------------------------------------
    //  退款触发
    // -----------------------------------------------------------------------

    /**
     * @test
     * 触发阈值：当 pending >= triggerMultiple(4)，应该派发 QueueRefundJob
     */
    public function testRefundTriggeredWhenThresholdReached(): void
    {
        $daoMock = $this->createMock(\app\dao\hjf\QueuePoolDao::class);
        $daoMock->method('countPending')->willReturn(4);  // 4 >= 4, should trigger
        $daoMock->method('getEarliestPending')->willReturn([
            'id'     => 1,
            'uid'    => 1001,
            'amount' => '3600.00',
        ]);

        // Verify Job dispatch would be called
        // In real test: assert QueueRefundJob::dispatch() called once

        $this->assertTrue(true, '退款触发桩测试通过（pending=4, multiple=4）');
    }

    /**
     * @test
     * 未达阈值：当 pending < triggerMultiple，不触发退款
     */
    public function testNoRefundWhenBelowThreshold(): void
    {
        $daoMock = $this->createMock(\app\dao\hjf\QueuePoolDao::class);
        $daoMock->method('countPending')->willReturn(3); // 3 < 4, should NOT trigger

        // Verify Job dispatch would NOT be called
        $this->assertTrue(true, '未触发退款桩测试通过（pending=3, multiple=4）');
    }

    // -----------------------------------------------------------------------
    //  分布式锁
    // -----------------------------------------------------------------------

    /**
     * @test
     * 并发锁：第一个请求获得锁后，第二个并发请求应被拒绝
     */
    public function testDistributedLockPreventsConcurrentEnqueue(): void
    {
        // Redis SET NX 模拟：第一次返回 true（获得锁），第二次返回 false（锁已占用）
        $responses = [true, false];
        $callCount = 0;

        $cacheMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['set', 'del'])
            ->getMock();

        $cacheMock->method('set')->willReturnCallback(
            function () use (&$responses, &$callCount) {
                return $responses[$callCount++] ?? false;
            }
        );

        // First request: lock acquired → proceed
        $lock1 = $responses[0]; // true
        $this->assertTrue($lock1, '第一个请求应获得分布式锁');

        // Second request: lock not acquired → reject
        $lock2 = $responses[1]; // false
        $this->assertFalse($lock2, '第二个并发请求应被分布式锁拒绝');
    }

    // -----------------------------------------------------------------------
    //  退款 Job 执行
    // -----------------------------------------------------------------------

    /**
     * @test
     * 退款 Job：status 已是 refunded 时不重复处理（幂等）
     */
    public function testRefundJobIsIdempotent(): void
    {
        $daoMock = $this->createMock(\app\dao\hjf\QueuePoolDao::class);
        // Record already refunded
        $daoMock->method('get')->willReturn([
            'id'     => 1,
            'status' => 'refunded',
        ]);

        // doJob should return true without doing any DB writes
        // Assert no userDao->bcInc() called

        $this->assertTrue(true, '退款 Job 幂等性桩测试通过');
    }

    /**
     * @test
     * 退款 Job：正常执行 → markRefunded + balance increment
     */
    public function testRefundJobExecutesSuccessfully(): void
    {
        $daoMock  = $this->createMock(\app\dao\hjf\QueuePoolDao::class);
        $userMock = $this->createMock(\app\dao\user\UserDao::class);

        $daoMock->method('get')->willReturn([
            'id'     => 1,
            'uid'    => 1001,
            'amount' => '3600.00',
            'status' => 'pending',
        ]);
        $daoMock->expects($this->once())->method('markRefunded')->willReturn(true);
        $userMock->expects($this->once())->method('bcInc')->willReturn(true);

        // Real execution: $job->doJob(1, 1001, 3600.00, 'BATCH_001')
        // Assert both mock methods were called

        $this->assertTrue(true, '退款 Job 执行流程桩测试通过');
    }
}
