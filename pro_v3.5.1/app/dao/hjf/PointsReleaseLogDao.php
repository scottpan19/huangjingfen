<?php
declare(strict_types=1);

namespace app\dao\hjf;

use app\dao\BaseDao;
use app\model\hjf\PointsReleaseLog;

/**
 * 积分释放日志 DAO
 * Class PointsReleaseLogDao
 * @package app\dao\hjf
 */
class PointsReleaseLogDao extends BaseDao
{
    protected function setModel(): string
    {
        return PointsReleaseLog::class;
    }

    /**
     * 查询积分明细列表（分页，支持按 type 筛选）
     * type: reward_direct | reward_umbrella | release | consume | ''(全部)
     */
    public function getDetailList(int $uid, string $type, int $page, int $limit): array
    {
        $model = $this->getModel()->where('uid', $uid);
        if ($type !== '') {
            $model = $model->where('type', $type);
        }
        $count = (clone $model)->count();
        $list  = $model->order('add_time', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
        return compact('list', 'count');
    }

    /**
     * Admin 积分释放日志（分页）
     */
    public function getAdminList(array $where, int $page, int $limit): array
    {
        $model = $this->getModel();
        if (!empty($where['keyword'])) {
            $model = $model->where('uid', 'like', '%' . $where['keyword'] . '%');
        }
        if (!empty($where['type'])) {
            $model = $model->where('type', $where['type']);
        }
        if (!empty($where['start_time'])) {
            $model = $model->where('add_time', '>=', strtotime($where['start_time']));
        }
        if (!empty($where['end_time'])) {
            $model = $model->where('add_time', '<=', strtotime($where['end_time']) + 86399);
        }
        $count = (clone $model)->count();
        $list  = $model->order('add_time', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        // 今日统计
        $todayStart = strtotime(date('Y-m-d'));
        $todayReleased = $this->getModel()
            ->where('type', 'release')
            ->where('add_time', '>=', $todayStart)
            ->sum('points');
        $todayUsers = $this->getModel()
            ->where('type', 'release')
            ->where('add_time', '>=', $todayStart)
            ->group('uid')
            ->count();

        return [
            'list'  => $list,
            'count' => $count,
            'statistics' => [
                'total_released_today' => (int)$todayReleased,
                'total_users_released' => (int)$todayUsers,
            ],
        ];
    }
}
