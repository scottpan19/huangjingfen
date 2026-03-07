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
use app\model\product\stock\StockInventory;
use app\model\product\stock\StockRecord;

/**
 * 出入库记录DAO
 * Class StockInventoryDao
 * @package app\dao\stock
 */
class StockInventoryDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StockInventory::class;
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
        return $this->search($where, false)->field($field)->when($with, function ($query) use ($with) {
            $query->with($with);
        })
            ->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->order('id DESC')->select()->toArray();
    }
}
