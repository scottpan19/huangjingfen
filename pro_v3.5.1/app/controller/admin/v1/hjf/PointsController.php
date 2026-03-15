<?php
declare(strict_types=1);

namespace app\controller\admin\v1\hjf;

use app\controller\admin\AuthController;
use app\dao\hjf\PointsReleaseLogDao;
use think\annotation\Inject;

/**
 * Admin · 积分管理接口
 *
 * GET /adminapi/hjf/points/release-log — 积分释放日志（分页）
 *
 * Class PointsController
 * @package app\controller\admin\v1\hjf
 */
class PointsController extends AuthController
{
    #[Inject]
    protected PointsReleaseLogDao $dao;

    /**
     * 积分释放日志（分页）
     */
    public function releaseLog(): mixed
    {
        $where = $this->request->getMore([
            ['keyword',    ''],
            ['type',       ''],
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
}
