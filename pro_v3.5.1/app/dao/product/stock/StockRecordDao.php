<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2026 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace app\dao\product\stock;

use app\dao\BaseDao;
use app\model\product\stock\StockRecord;

/**
 * 出入库记录DAO
 * Class StockRecordDao
 * @package app\dao\stock
 */
class StockRecordDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StockRecord::class;
    }

    /**
     * 获取出入库记录列表
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @param array $with
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where = [], string $field = '*', int $page = 0, int $limit = 0, array $with = []): array
    {
        return $this->search($where,false)->field($field)->when($with, function ($query) use ($with) {
            $query->with($with);
        })

            ->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->order('id DESC')->select()->toArray();
    }

    /**
     * 根据条件获取出入库记录数量
     * @param array $where
     * @return int
     */
    public function getCount(array $where = []): int
    {
        return $this->search($where)->count();
    }

    /**
     * 生成出入库单号
     * @param int $type
     * @return string
     */
    public function generateRecordNo(int $type): string
    {
        if($type == 3){
            $prefix = 'IC';
        }else{
            $prefix = $type == StockRecord::TYPE_IN ? 'IN' : 'OUT';
        }
        $date = date('Ymd');
        $rand = mt_rand(1000, 9999);
        return $prefix . $date . $rand;
    }

    /**
     * 检查单号是否存在
     * @param string $recordNo
     * @param int $excludeId
     * @return bool
     */
    public function checkRecordNoExists(string $recordNo, int $excludeId = 0): bool
    {
        $query = $this->getModel()->where('record_no', $recordNo);
        if ($excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }
        return $query->count() > 0;
    }

    /**
     * 获取统计数据
     * @param array $where
     * @return array
     */
    public function getStatistics(array $where = []): array
    {
        $query = $this->search($where);

        return [
            'total_count' => $query->count(),
            'in_count' => $query->where('type', StockRecord::TYPE_IN)->count(),
            'out_count' => $query->where('type', StockRecord::TYPE_OUT)->count(),
            'pending_count' => $query->count(),
            'approved_count' => $query->count(),
        ];
    }

    /**
     * 根据日期范围获取记录
     * @param int $startTime 开始时间戳
     * @param int $endTime 结束时间戳
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRecordsByDateRange(int $startTime, int $endTime, array $where = []): array
    {
        return $this->search($where)
            ->where('record_date', '>=', $startTime)
            ->where('record_date', '<=', $endTime)
            ->with(['items'])
            ->order('record_date DESC, id DESC')
            ->select()
            ->toArray();
    }

    /**
     * 获取待审核的记录
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPendingRecords(int $limit = 10): array
    {
        return $this->getModel()
            ->with(['items'])
            ->order('create_time ASC')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 根据出入库类型获取记录
     * @param int $type 出入库类型
     * @param int $subType 子类型
     * @param array $where 其他条件
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRecordsByType(int $type, int $subType = 0, array $where = []): array
    {
        $query = $this->search($where)->where('type', $type);
        if ($subType > 0) {
            $query->where('sub_type', $subType);
        }
        return $query->with(['items'])
            ->order('record_date DESC, id DESC')
            ->select()
            ->toArray();
    }
}
