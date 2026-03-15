<?php
declare(strict_types=1);

namespace app\controller\api\v1\hjf;

use app\Request;
use app\services\hjf\QueuePoolServices;
use think\annotation\Inject;

/**
 * 用户端 · 公排接口
 *
 * GET /api/hjf/queue/status   — 公排状态摘要
 * GET /api/hjf/queue/history  — 公排历史记录（分页）
 *
 * Class QueueController
 * @package app\controller\api\v1\hjf
 */
class QueueController
{
    #[Inject]
    protected QueuePoolServices $services;

    /**
     * 公排状态摘要
     * 返回：全平台总单数、当前批次进度、用户自己的订单列表（含预估等待）
     *
     * @param Request $request
     * @return mixed
     */
    public function status(Request $request): mixed
    {
        $uid = (int)$request->uid();
        return app('json')->success($this->services->getUserStatus($uid));
    }

    /**
     * 公排历史记录（分页）
     *
     * @param Request $request
     * @return mixed
     */
    public function history(Request $request): mixed
    {
        $uid    = (int)$request->uid();
        $status = (int)$request->param('status', -1);  // -1=全部, 0=排队中, 1=已退款
        [$page, $limit] = $this->getPage($request);

        return app('json')->success(
            $this->services->getUserHistory($uid, $status, $page, $limit)
        );
    }

    private function getPage(Request $request): array
    {
        $page  = max(1, (int)$request->param('page', 1));
        $limit = min(50, max(1, (int)$request->param('limit', 15)));
        return [$page, $limit];
    }
}
