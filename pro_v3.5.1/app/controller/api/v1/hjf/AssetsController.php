<?php
declare(strict_types=1);

namespace app\controller\api\v1\hjf;

use app\Request;
use app\services\hjf\HjfAssetsServices;
use think\annotation\Inject;

/**
 * 用户端 · 资产接口
 *
 * GET /api/hjf/assets/overview      — 资产总览（余额 + 积分）
 * GET /api/hjf/assets/cash/detail   — 现金流水（分页）
 *
 * Class AssetsController
 * @package app\controller\api\v1\hjf
 */
class AssetsController
{
    #[Inject]
    protected HjfAssetsServices $assetsServices;

    /**
     * 资产总览
     *
     * @param Request $request
     * @return mixed
     */
    public function overview(Request $request): mixed
    {
        $uid = (int)$request->uid();
        return app('json')->success(
            $this->assetsServices->getOverview($uid)
        );
    }

    /**
     * 现金流水（分页）
     *
     * 查询参数：
     *  - type:  '' | queue_refund | withdraw | recharge
     *  - page, limit
     *
     * @param Request $request
     * @return mixed
     */
    public function cashDetail(Request $request): mixed
    {
        $uid   = (int)$request->uid();
        $type  = (string)$request->param('type', '');
        $page  = max(1, (int)$request->param('page', 1));
        $limit = min(50, max(1, (int)$request->param('limit', 15)));

        $validTypes = ['', 'queue_refund', 'withdraw', 'recharge', 'pay'];
        if (!in_array($type, $validTypes, true)) {
            $type = '';
        }

        return app('json')->success(
            $this->assetsServices->getCashDetail($uid, $type, $page, $limit)
        );
    }
}
