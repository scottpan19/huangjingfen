<?php
declare(strict_types=1);

namespace app\controller\admin\v1\hjf;

use app\controller\admin\AuthController;
use app\dao\hjf\QueuePoolDao;
use app\services\system\config\SystemConfigServices;
use crmeb\services\SystemConfigService;
use think\annotation\Inject;

/**
 * Admin · 公排管理接口
 *
 * GET  /adminapi/hjf/queue/order    — 公排订单列表
 * GET  /adminapi/hjf/queue/config   — 获取公排配置
 * POST /adminapi/hjf/queue/config   — 保存公排配置
 * GET  /adminapi/hjf/queue/finance  — 公排退款财务流水
 *
 * Class QueueController
 * @package app\controller\admin\v1\hjf
 */
class QueueController extends AuthController
{
    #[Inject]
    protected QueuePoolDao $dao;

    /**
     * 公排订单列表（分页 + 筛选）
     */
    public function orderList(): mixed
    {
        $where = $this->request->getMore([
            ['keyword',    ''],
            ['status',     ''],
            ['start_time', ''],
            ['end_time',   ''],
            ['page',       1],
            ['limit',      20],
        ]);
        $page  = (int)$where['page'];
        $limit = (int)$where['limit'];
        unset($where['page'], $where['limit']);

        return $this->success($this->dao->getAdminList($where, $page, $limit));
    }

    /**
     * 获取公排配置
     */
    public function getConfig(): mixed
    {
        $config = [
            'trigger_multiple'   => (int)SystemConfigService::get('hjf_trigger_multiple', 4),
            'release_rate'       => (int)SystemConfigService::get('hjf_release_rate', 4),
            'withdraw_fee_rate'  => (int)SystemConfigService::get('hjf_withdraw_fee_rate', 7),
            'enabled'            => (bool)SystemConfigService::get('hjf_queue_enabled', 1),
        ];
        return $this->success($config);
    }

    /**
     * 保存公排配置
     */
    public function saveConfig(SystemConfigServices $configServices): mixed
    {
        $data = $this->request->getMore([
            ['trigger_multiple',  4],
            ['release_rate',      4],
            ['withdraw_fee_rate', 7],
            ['enabled',           1],
        ]);

        $map = [
            'hjf_trigger_multiple'  => (int)$data['trigger_multiple'],
            'hjf_release_rate'      => (int)$data['release_rate'],
            'hjf_withdraw_fee_rate' => (int)$data['withdraw_fee_rate'],
            'hjf_queue_enabled'     => (int)$data['enabled'],
        ];

        foreach ($map as $key => $value) {
            $configServices->setConfig($key, (string)$value);
        }

        return $this->success('保存成功');
    }

    /**
     * 公排退款财务流水（分页）
     */
    public function financeList(): mixed
    {
        $where = $this->request->getMore([
            ['start_time', ''],
            ['end_time',   ''],
            ['page',       1],
            ['limit',      20],
        ]);
        $page  = (int)$where['page'];
        $limit = (int)$where['limit'];
        unset($where['page'], $where['limit']);

        return $this->success($this->dao->getFinanceList($where, $page, $limit));
    }
}
