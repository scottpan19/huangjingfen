<?php
declare(strict_types=1);

namespace app\controller\api\v1\hjf;

use app\Request;
use app\dao\hjf\PointsReleaseLogDao;
use think\annotation\Inject;

/**
 * 用户端 · 积分明细接口
 *
 * GET /api/hjf/points/detail — 积分明细（分页，支持5种类型筛选）
 *
 * Class PointsController
 * @package app\controller\api\v1\hjf
 */
class PointsController
{
    #[Inject]
    protected PointsReleaseLogDao $dao;

    /**
     * 积分明细（分页）
     *
     * 查询参数：
     *  - type: '' | reward_direct | reward_umbrella | release | consume
     *  - page, limit
     *
     * @param Request $request
     * @return mixed
     */
    public function detail(Request $request): mixed
    {
        $uid   = (int)$request->uid();
        $type  = (string)$request->param('type', '');
        $page  = max(1, (int)$request->param('page', 1));
        $limit = min(50, max(1, (int)$request->param('limit', 15)));

        $validTypes = ['', 'reward_direct', 'reward_umbrella', 'release', 'consume'];
        if (!in_array($type, $validTypes, true)) {
            $type = '';
        }

        return app('json')->success(
            $this->dao->getDetailList($uid, $type, $page, $limit)
        );
    }
}
