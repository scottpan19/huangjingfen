<?php
declare(strict_types=1);

namespace tests\hjf;

use PHPUnit\Framework\TestCase;

/**
 * P4 联调冒烟测试（SmokeTest）
 *
 * 使用说明：
 *   1. 确保 Phase 2 数据库迁移已执行（eb_queue_pool / eb_points_release_log 等表已创建）
 *   2. 确保后端服务已启动（ThinkPHP 8 + Swoole 或 PHP-FPM）
 *   3. 设置环境变量：
 *        HJF_API_BASE=http://127.0.0.1:8080/api
 *        HJF_ADMIN_BASE=http://127.0.0.1:8080/adminapi
 *        HJF_TOKEN=<有效用户Token>
 *        HJF_ADMIN_TOKEN=<有效管理员Token>
 *   4. 运行：./vendor/bin/phpunit tests/hjf/SmokeTest.php
 *
 * 覆盖场景：
 *  P4-02 UniApp 冒烟：登录后访问公排/资产/积分/会员接口
 *  P4-03 Admin 冒烟：公排订单列表 / 配置保存 / 会员等级 / 积分日志
 *  P4-05 定时任务验证：手动调用积分释放 command，验证 release_log 有新记录
 *
 * Class SmokeTest
 * @package tests\hjf
 */
class SmokeTest extends TestCase
{
    private string $apiBase;
    private string $adminBase;
    private string $token;
    private string $adminToken;

    protected function setUp(): void
    {
        $this->apiBase    = rtrim(getenv('HJF_API_BASE')   ?: 'http://127.0.0.1:8080/api', '/');
        $this->adminBase  = rtrim(getenv('HJF_ADMIN_BASE') ?: 'http://127.0.0.1:8080/adminapi', '/');
        $this->token      = getenv('HJF_TOKEN')       ?: '';
        $this->adminToken = getenv('HJF_ADMIN_TOKEN') ?: '';
    }

    // -----------------------------------------------------------------------
    //  辅助方法
    // -----------------------------------------------------------------------

    /**
     * 发送 GET 请求，返回解析后的响应体数组
     */
    private function get(string $url, string $token = ''): array
    {
        $headers = ['Content-Type: application/json'];
        if ($token) {
            $headers[] = "Authorization: Bearer {$token}";
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $body = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode((string)$body, true) ?: [];
        $data['__http_code'] = $http;
        return $data;
    }

    /**
     * 发送 POST 请求，返回解析后的响应体数组
     */
    private function post(string $url, array $payload, string $token = ''): array
    {
        $headers = ['Content-Type: application/json'];
        if ($token) {
            $headers[] = "Authorization: Bearer {$token}";
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $body = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode((string)$body, true) ?: [];
        $data['__http_code'] = $http;
        return $data;
    }

    // -----------------------------------------------------------------------
    //  P4-02 UniApp 冒烟测试
    // -----------------------------------------------------------------------

    /**
     * @test
     * P4-02-A: 公排状态接口可访问，返回 200 且包含 total_orders/my_orders/progress 字段
     */
    public function testUniAppQueueStatus(): void
    {
        if (!$this->token) {
            $this->markTestSkipped('HJF_TOKEN 未设置，跳过');
        }
        $res = $this->get("{$this->apiBase}/hjf/queue/status", $this->token);

        $this->assertEquals(200, $res['__http_code'], '公排状态接口 HTTP 200');
        $this->assertArrayHasKey('data', $res, '响应包含 data 字段');

        $data = $res['data'];
        $this->assertArrayHasKey('total_orders', $data, 'data 包含 total_orders');
        $this->assertArrayHasKey('my_orders',    $data, 'data 包含 my_orders');
        $this->assertArrayHasKey('progress',     $data, 'data 包含 progress');
    }

    /**
     * @test
     * P4-02-B: 公排历史接口可访问，返回分页格式
     */
    public function testUniAppQueueHistory(): void
    {
        if (!$this->token) {
            $this->markTestSkipped('HJF_TOKEN 未设置，跳过');
        }
        $res = $this->get("{$this->apiBase}/hjf/queue/history?page=1&limit=10", $this->token);

        $this->assertEquals(200, $res['__http_code'], '公排历史接口 HTTP 200');
        $data = $res['data'] ?? [];
        $this->assertArrayHasKey('list',  $data, 'data 包含 list');
        $this->assertArrayHasKey('count', $data, 'data 包含 count');
    }

    /**
     * @test
     * P4-02-C: 资产总览接口，返回余额+积分字段
     */
    public function testUniAppAssetsOverview(): void
    {
        if (!$this->token) {
            $this->markTestSkipped('HJF_TOKEN 未设置，跳过');
        }
        $res = $this->get("{$this->apiBase}/hjf/assets/overview", $this->token);

        $this->assertEquals(200, $res['__http_code'], '资产总览接口 HTTP 200');
        $data = $res['data'] ?? [];
        $this->assertArrayHasKey('now_money',        $data, 'data 包含 now_money');
        $this->assertArrayHasKey('frozen_points',    $data, 'data 包含 frozen_points');
        $this->assertArrayHasKey('available_points', $data, 'data 包含 available_points');
    }

    /**
     * @test
     * P4-02-D: 积分明细接口，返回分页列表
     */
    public function testUniAppPointsDetail(): void
    {
        if (!$this->token) {
            $this->markTestSkipped('HJF_TOKEN 未设置，跳过');
        }
        $res = $this->get("{$this->apiBase}/hjf/points/detail?page=1&limit=10", $this->token);

        $this->assertEquals(200, $res['__http_code'], '积分明细接口 HTTP 200');
        $data = $res['data'] ?? [];
        $this->assertArrayHasKey('list',  $data, 'data 包含 list');
        $this->assertArrayHasKey('count', $data, 'data 包含 count');
    }

    /**
     * @test
     * P4-02-E: 会员信息接口，返回 member_level 字段
     */
    public function testUniAppMemberInfo(): void
    {
        if (!$this->token) {
            $this->markTestSkipped('HJF_TOKEN 未设置，跳过');
        }
        $res = $this->get("{$this->apiBase}/hjf/member/info", $this->token);

        $this->assertEquals(200, $res['__http_code'], '会员信息接口 HTTP 200');
        $data = $res['data'] ?? [];
        $this->assertArrayHasKey('member_level', $data, 'data 包含 member_level');
        $this->assertContains(
            (int)($data['member_level'] ?? -1),
            [0, 1, 2, 3, 4],
            'member_level 取值 0~4'
        );
    }

    // -----------------------------------------------------------------------
    //  P4-03 Admin 冒烟测试
    // -----------------------------------------------------------------------

    /**
     * @test
     * P4-03-A: Admin 公排订单列表接口，返回分页数据
     */
    public function testAdminQueueOrderList(): void
    {
        if (!$this->adminToken) {
            $this->markTestSkipped('HJF_ADMIN_TOKEN 未设置，跳过');
        }
        $res = $this->get("{$this->adminBase}/hjf/queue/order?page=1&limit=10", $this->adminToken);

        $this->assertEquals(200, $res['__http_code'], 'Admin 公排订单列表 HTTP 200');
        $data = $res['data'] ?? [];
        $this->assertArrayHasKey('list',  $data, 'data 包含 list');
        $this->assertArrayHasKey('count', $data, 'data 包含 count');
    }

    /**
     * @test
     * P4-03-B: Admin 公排配置读取，返回 hjf_trigger_multiple 等关键字段
     */
    public function testAdminQueueConfigGet(): void
    {
        if (!$this->adminToken) {
            $this->markTestSkipped('HJF_ADMIN_TOKEN 未设置，跳过');
        }
        $res = $this->get("{$this->adminBase}/hjf/queue/config", $this->adminToken);

        $this->assertEquals(200, $res['__http_code'], 'Admin 公排配置 HTTP 200');
        $data = $res['data'] ?? [];
        $this->assertArrayHasKey('hjf_trigger_multiple', $data, 'data 包含 hjf_trigger_multiple');
    }

    /**
     * @test
     * P4-03-C: Admin 公排配置保存（写回原值，不改变业务数据）
     */
    public function testAdminQueueConfigSave(): void
    {
        if (!$this->adminToken) {
            $this->markTestSkipped('HJF_ADMIN_TOKEN 未设置，跳过');
        }
        // 先读取当前配置
        $getRes = $this->get("{$this->adminBase}/hjf/queue/config", $this->adminToken);
        $current = $getRes['data'] ?? ['hjf_trigger_multiple' => 4, 'hjf_release_rate' => 4];

        // 写回原值
        $saveRes = $this->post("{$this->adminBase}/hjf/queue/config", $current, $this->adminToken);
        $this->assertEquals(200, $saveRes['__http_code'], 'Admin 公排配置保存 HTTP 200');
    }

    /**
     * @test
     * P4-03-D: Admin 会员列表接口，返回分页数据含 member_level 列
     */
    public function testAdminMemberList(): void
    {
        if (!$this->adminToken) {
            $this->markTestSkipped('HJF_ADMIN_TOKEN 未设置，跳过');
        }
        $res = $this->get("{$this->adminBase}/hjf/member/list?page=1&limit=10", $this->adminToken);

        $this->assertEquals(200, $res['__http_code'], 'Admin 会员列表 HTTP 200');
        $data = $res['data'] ?? [];
        $this->assertArrayHasKey('list',  $data, 'data 包含 list');
        $this->assertArrayHasKey('count', $data, 'data 包含 count');

        // 若有记录，验证字段完整性
        if (!empty($data['list'])) {
            $first = $data['list'][0];
            $this->assertArrayHasKey('member_level',         $first, 'list[0] 包含 member_level');
            $this->assertArrayHasKey('direct_order_count',   $first, 'list[0] 包含 direct_order_count');
            $this->assertArrayHasKey('umbrella_order_count', $first, 'list[0] 包含 umbrella_order_count');
        }
    }

    /**
     * @test
     * P4-03-E: Admin 积分释放日志接口，返回分页数据
     */
    public function testAdminPointsReleaseLog(): void
    {
        if (!$this->adminToken) {
            $this->markTestSkipped('HJF_ADMIN_TOKEN 未设置，跳过');
        }
        $res = $this->get("{$this->adminBase}/hjf/points/release-log?page=1&limit=10", $this->adminToken);

        $this->assertEquals(200, $res['__http_code'], 'Admin 积分释放日志 HTTP 200');
        $data = $res['data'] ?? [];
        $this->assertArrayHasKey('list',  $data, 'data 包含 list');
        $this->assertArrayHasKey('count', $data, 'data 包含 count');
    }

    // -----------------------------------------------------------------------
    //  P4-05 定时任务验证（积分每日释放）
    // -----------------------------------------------------------------------

    /**
     * @test
     * P4-05: 手动触发 Artisan/Think 积分释放命令后，release_log 有新记录
     *
     * 验证方式：
     *  1. 记录触发前的日志总数
     *  2. 调用 Admin 接口触发（或本地 CLI：php think hjf:release-points）
     *  3. 等待队列消费（最多10秒）
     *  4. 再次查询日志总数，新增量 >= 1
     */
    public function testDailyPointsReleaseCreatesLogs(): void
    {
        if (!$this->adminToken) {
            $this->markTestSkipped('HJF_ADMIN_TOKEN 未设置，跳过');
        }

        // 触发前的日志总数
        $before = $this->get("{$this->adminBase}/hjf/points/release-log?page=1&limit=1", $this->adminToken);
        $countBefore = (int)($before['data']['count'] ?? 0);

        // 触发定时任务（通过 CLI，实际测试时需在后端服务器执行）
        // 此处记录预期行为：执行后 count 应增加
        // 若在 CI 环境中可替换为：shell_exec('php think hjf:release-points');

        // 由于无法在 PHPUnit 内部确保队列消费完成，此测试标记为 skipped 并记录说明
        $this->markTestIncomplete(
            "P4-05 定时任务测试需在后端服务器手动执行：\n" .
            "  php think hjf:release-points\n" .
            "执行后再运行本测试检验 release_log count 从 {$countBefore} 增加。\n" .
            "若 frozen_points 为0的用户较多，释放量可能为0（属正常行为）。"
        );
    }
}
