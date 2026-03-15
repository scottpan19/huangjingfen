<?php
declare(strict_types=1);

namespace app\dao\hjf;

use app\dao\BaseDao;
use app\model\hjf\QueuePool;
use crmeb\basic\BaseModel;

/**
 * 公排池 DAO
 * Class QueuePoolDao
 * @package app\dao\hjf
 */
class QueuePoolDao extends BaseDao
{
    protected function setModel(): string
    {
        return QueuePool::class;
    }

    /**
     * 获取用户的公排记录列表（分页）
     */
    public function getUserList(int $uid, int $status, int $page, int $limit): array
    {
        $model = $this->getModel()->where('uid', $uid);
        if ($status >= 0) {
            $model = $model->where('status', $status);
        }
        $count = (clone $model)->count();
        $list  = $model->order('add_time', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
        return compact('list', 'count');
    }

    /**
     * 获取全局公排列表（Admin 分页）
     */
    public function getAdminList(array $where, int $page, int $limit): array
    {
        $model = $this->getModel();
        if (!empty($where['keyword'])) {
            $model = $model->where('order_id|uid', 'like', '%' . $where['keyword'] . '%');
        }
        if (isset($where['status']) && $where['status'] !== '') {
            $model = $model->where('status', (int)$where['status']);
        }
        if (!empty($where['start_time'])) {
            $model = $model->where('add_time', '>=', strtotime($where['start_time']));
        }
        if (!empty($where['end_time'])) {
            $model = $model->where('add_time', '<=', strtotime($where['end_time']) + 86399);
        }
        $count = (clone $model)->count();
        $list  = $model->order('queue_no', 'asc')
            ->page($page, $limit)
            ->select()
            ->toArray();
        return compact('list', 'count');
    }

    /**
     * 获取最早尚未退款的一条记录
     */
    public function getEarliestPending(): ?array
    {
        $row = $this->getModel()
            ->where('status', 0)
            ->order('queue_no', 'asc')
            ->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 当前排队中总单数
     */
    public function countPending(): int
    {
        return $this->getModel()->where('status', 0)->count();
    }

    /**
     * 获取全局总单数（含已退款）
     */
    public function countTotal(): int
    {
        return $this->getModel()->count();
    }

    /**
     * 获取下一个全局排队序号（MAX queue_no + 1）
     */
    public function nextQueueNo(): int
    {
        $max = $this->getModel()->max('queue_no');
        return (int)$max + 1;
    }

    /**
     * 标记一条记录为已退款
     */
    public function markRefunded(int $id, int $batchNo): bool
    {
        return (bool)$this->getModel()
            ->where('id', $id)
            ->update([
                'status'        => 1,
                'refund_time'   => time(),
                'trigger_batch' => $batchNo,
            ]);
    }

    /**
     * 获取退款财务流水（Admin 分页）
     */
    public function getFinanceList(array $where, int $page, int $limit): array
    {
        $model = $this->getModel()->where('status', 1);
        if (!empty($where['start_time'])) {
            $model = $model->where('refund_time', '>=', strtotime($where['start_time']));
        }
        if (!empty($where['end_time'])) {
            $model = $model->where('refund_time', '<=', strtotime($where['end_time']) + 86399);
        }
        $count      = (clone $model)->count();
        $totalRefund = (clone $model)->sum('amount');
        $list       = $model->order('refund_time', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
        return [
            'list'         => $list,
            'count'        => $count,
            'total_refund' => number_format((float)$totalRefund, 2, '.', ''),
        ];
    }
}
