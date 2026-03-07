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
use app\model\product\stock\StockRecordItem;

/**
 * 出入库商品详情DAO
 * Class StockRecordItemDao
 * @package app\dao\stock
 */
class StockRecordItemDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StockRecordItem::class;
    }

    /**
     * 根据出入库记录ID获取商品详情
     * @param int $recordId
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getItemsByRecordId(int $recordId, string $field = '*'): array
    {
        return $this->getModel()
            ->where('record_id', $recordId)
            ->where('type', 1)
            ->field($field)
            ->order('id ASC')
            ->select()
            ->toArray();
    }

    /**
     * 批量添加出入库商品详情
     * @param array $data
     * @return bool
     */
    public function saveAllItems(array $data): bool
    {
        return $this->getModel()->saveAll($data) !== false;
    }

    /**
     * 根据记录ID删除商品详情
     * @param int $recordId
     * @return bool
     */
    public function deleteByRecordId(int $recordId): bool
    {
        return $this->getModel()->where('record_id', $recordId)->delete() !== false;
    }

    /**
     * 获取商品的出入库统计
     * @param int $productId
     * @param int $skuId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getProductStockStatistics(int $productId, int $skuId = 0, string $startDate = '', string $endDate = ''): array
    {
        $query = $this->getModel()
            ->alias('item')
            ->leftJoin('stock_record record', 'item.record_id = record.id')
            ->where('item.product_id', $productId)
            ->where('record.status', 2); // 已审核状态

        if ($skuId > 0) {
            $query->where('item.sku_id', $skuId);
        }

        if ($startDate && $endDate) {
            $query->whereBetweenTime('record.record_date', $startDate, $endDate);
        }

        // 入库统计
        $inStats = (clone $query)
            ->where('record.type', 1)
            ->field([
                'SUM(item.good_stock) as total_good_in',
                'SUM(item.defective_stock) as total_defective_in',
                'COUNT(*) as in_count'
            ])
            ->find();

        // 出库统计
        $outStats = (clone $query)
            ->where('record.type', 2)
            ->field([
                'SUM(item.good_stock) as total_good_out',
                'SUM(item.defective_stock) as total_defective_out',
                'COUNT(*) as out_count'
            ])
            ->find();

        return [
            'in_stats' => $inStats ? $inStats->toArray() : ['total_good_in' => 0, 'total_defective_in' => 0, 'in_count' => 0],
            'out_stats' => $outStats ? $outStats->toArray() : ['total_good_out' => 0, 'total_defective_out' => 0, 'out_count' => 0],
        ];
    }

    /**
     * 更新商品库存（这里实际不需要更新，因为库存通过出入库记录计算）
     * @param int $productId
     * @param int $skuId
     * @param int $quantity
     * @param string $operation
     * @return bool
     */
    public function updateProductStock(int $productId, int $skuId, int $quantity, string $operation): bool
    {
        // 由于库存通过出入库记录实时计算，这里不需要实际更新操作
        return true;
    }

    /**
     * 获取商品库存详情
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getProductStockDetails(array $where = [], int $page = 1, int $limit = 20): array
    {
        $query = $this->getModel()->alias('item')
            ->leftJoin('stock_record record', 'item.record_id = record.id')
            ->leftJoin('store_product p', 'item.product_id = p.id')
            ->leftJoin('store_product_attr_value pav', 'item.sku_id = pav.unique')
            ->field([
                'item.product_id',
                'item.sku_id',
                'p.store_name',
                'p.image',
                'pav.sku as sku_name',
                'SUM(CASE WHEN record.type = 1 AND record.status = 2 THEN item.quantity ELSE 0 END) as in_stock',
                'SUM(CASE WHEN record.type = 2 AND record.status = 2 THEN item.quantity ELSE 0 END) as out_stock',
                'SUM(CASE WHEN record.type = 1 AND record.status = 2 THEN item.quantity ELSE -item.quantity END) as current_stock'
            ])
            ->group('item.product_id, item.sku_id')
            ->order('current_stock desc');

        if (!empty($where['product_name'])) {
            $query->where('p.store_name', 'like', '%' . $where['product_name'] . '%');
        }

        if (isset($where['low_stock']) && $where['low_stock']) {
            $query->having('current_stock', '<', 10);
        }

        return $query->page($page, $limit)->select()->toArray();
    }

    /**
     * 获取商品库存详情总数
     * @param array $where
     * @return int
     */
    public function getProductStockDetailsCount(array $where = []): int
    {
        $query = $this->getModel()->alias('item')
            ->leftJoin('eb_stock_record record', 'item.record_id = record.id')
            ->leftJoin('store_product p', 'item.product_id = p.id')
            ->group('item.product_id, item.sku_id');

        if (!empty($where['product_name'])) {
            $query->where('p.store_name', 'like', '%' . $where['product_name'] . '%');
        }

        return $query->count();
    }

    /**
     * 检查库存是否充足
     * @param int $productId
     * @param int $skuId
     * @param int $quantity
     * @return bool
     */
    public function checkStockSufficient(int $productId, int $skuId, int $quantity): bool
    {
        $currentStock = $this->getCurrentStock($productId, $skuId);
        return $currentStock >= $quantity;
    }

    /**
     * 获取当前库存
     * @param int $productId
     * @param int $skuId
     * @return int
     */
    public function getCurrentStock(int $productId, int $skuId): int
    {
        $inStock = $this->getModel()->alias('item')
            ->leftJoin('eb_stock_record record', 'item.record_id = record.id')
            ->where('item.product_id', $productId)
            ->where('item.sku_id', $skuId)
            ->where('record.type', 1)
            ->where('record.status', 2)
            ->sum('item.quantity');

        $outStock = $this->getModel()->alias('item')
            ->leftJoin('eb_stock_record record', 'item.record_id = record.id')
            ->where('item.product_id', $productId)
            ->where('item.sku_id', $skuId)
            ->where('record.type', 2)
            ->where('record.status', 2)
            ->sum('item.quantity');

        return ($inStock ?: 0) - ($outStock ?: 0);
    }

    /**
     * 获取库存预警列表
     * @param int $warningStock
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getWarningStockList(int $warningStock = 10, int $page = 1, int $limit = 20): array
    {
        $query = $this->getModel()->alias('item')
            ->leftJoin('eb_stock_record record', 'item.record_id = record.id')
            ->leftJoin('store_product p', 'item.product_id = p.id')
            ->leftJoin('store_product_attr_value pav', 'item.sku_id = pav.unique')
            ->field([
                'item.product_id',
                'item.sku_id',
                'p.store_name',
                'p.image',
                'pav.sku as sku_name',
                'SUM(CASE WHEN record.type = 1 AND record.status = 2 THEN item.quantity ELSE 0 END) as in_stock',
                'SUM(CASE WHEN record.type = 2 AND record.status = 2 THEN item.quantity ELSE 0 END) as out_stock',
                'SUM(CASE WHEN record.type = 1 AND record.status = 2 THEN item.quantity ELSE -item.quantity END) as current_stock'
            ])
            ->group('item.product_id, item.sku_id')
            ->having('current_stock', '<', $warningStock)
            ->order('current_stock ASC');

        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ];
    }
}
