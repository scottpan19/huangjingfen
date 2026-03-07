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

namespace app\listener\product;

use app\services\product\sku\StoreProductAttrValueServices;
use app\services\product\stock\StockRecordServices;
use app\dao\product\stock\StockRecordItemDao;
use app\model\product\stock\StockRecord;
use think\facade\Log;

/**
 * 库存变动事件监听器
 * 处理出入库记录，同步更新商品规格库存（包括正品库存和残次品库存）
 * Class Stock
 * @package app\listener\product
 */
class Stock
{
    /**
     * 处理库存变动事件
     * @param $event
     */
    public function handle($event)
    {
        [$id] = $event;

        try {
            // 获取出入库记录
            $record = $this->getStockRecord($id);
            if (!$record) {
                return;
            }

            // 获取出入库商品详情
            $items = $this->getStockRecordItems($id);
            if (empty($items)) {
                return;
            }

            // 处理库存更新
            $this->processStockUpdate($record, $items);

            Log::info("Stock事件监听器：库存同步完成，记录ID：{$id}");

        } catch (\Exception $e) {
            Log::error("Stock事件监听器执行异常：{$e->getMessage()}，记录ID：{$id}");
        }
    }

    /**
     * 获取出入库记录
     * @param int $id
     * @return array|null
     */
    private function getStockRecord(int $id): ?array
    {
        /** @var StockRecordServices $stockRecordServices */
        $stockRecordServices = app()->make(StockRecordServices::class);
        $record = $stockRecordServices->get($id);

        if (!$record) {
            Log::error("Stock事件监听器：未找到出入库记录，ID：{$id}");
            return null;
        }
        return $record->toArray();
    }

    /**
     * 获取出入库商品详情
     * @param int $recordId
     * @return array
     */
    private function getStockRecordItems(int $recordId): array
    {
        /** @var StockRecordItemDao $stockRecordItemDao */
        $stockRecordItemDao = app()->make(StockRecordItemDao::class);
        $items = $stockRecordItemDao->getItemsByRecordId($recordId);

        if (empty($items)) {
            Log::error("Stock事件监听器：未找到出入库商品详情，记录ID：{$recordId}");
            return [];
        }

        return $items;
    }

    /**
     * 处理库存更新
     * @param array $record
     * @param array $items
     */
    private function processStockUpdate(array $record, array $items): void
    {
        /** @var StoreProductAttrValueServices $attrValueServices */
        $attrValueServices = app()->make(StoreProductAttrValueServices::class);

        // 批量处理库存数据
        $attrsStock = [];

        foreach ($items as $item) {
            $productId = $item['product_id'];
            $unique = $item['unique'];
            $goodStock = $item['good_stock'] ?? 0;
            $defectiveStock = $item['defective_stock'] ?? 0;

            // 验证商品规格信息
            if (!$this->validateProductAttr($attrValueServices, $productId, $unique)) {
                continue;
            }
            // 处理正品库存
            if ($goodStock != 0) {
                $this->addToStockBatch($attrsStock, $productId, $unique, $goodStock, $record['type']);
            }

            // 处理残次品库存
            if ($defectiveStock != 0) {
                $this->processDefectiveStock($attrValueServices, $productId, $unique, $defectiveStock, $record['type']);
            }
        }

        // 批量更新正品库存
        $this->batchUpdateStock($attrValueServices, $attrsStock);
    }

    /**
     * 验证商品规格信息
     * @param StoreProductAttrValueServices $attrValueServices
     * @param int $productId
     * @param string $unique
     * @return bool
     */
    private function validateProductAttr(StoreProductAttrValueServices $attrValueServices, int $productId, string $unique): bool
    {
        $attrInfo = $attrValueServices->getOne([
            'product_id' => $productId,
            'type' => 0,
            'unique' => $unique
        ]);

        if (!$attrInfo) {
            Log::error("Stock事件监听器：未找到商品规格信息，商品ID：{$productId}，unique：{$unique}");
            return false;
        }

        return true;
    }

    /**
     * 添加到批量库存更新数组
     * @param array &$attrsStock
     * @param int $productId
     * @param string $unique
     * @param int $stock
     * @param int $recordType
     */
    private function addToStockBatch(array &$attrsStock, int $productId, string $unique, int $stock, int $recordType): void
    {
        $pm = $stock > 0 ? 1 : 0; // 1:入库增加, 0:出库减少

        // 查找是否已存在该商品的记录
        $productIndex = $this->findProductInBatch($attrsStock, $productId);

        if ($productIndex !== false) {
            // 已存在，添加到attr数组
            $attrsStock[$productIndex]['attr'][] = [
                'unique' => $unique,
                'stock' => abs($stock),
                'pm' => $pm
            ];
        } else {
            // 不存在，创建新记录
            $attrsStock[] = [
                'product_id' => $productId,
                'attr' => [[
                    'unique' => $unique,
                    'stock' => abs($stock),
                    'pm' => $pm
                ]]
            ];
        }
    }

    /**
     * 在批量数组中查找商品
     * @param array $attrsStock
     * @param int $productId
     * @return int|false
     */
    private function findProductInBatch(array $attrsStock, int $productId)
    {
        foreach ($attrsStock as $index => $item) {
            if (isset($item['product_id']) && $item['product_id'] == $productId) {
                return $index;
            }
        }
        return false;
    }

    /**
     * 处理残次品库存
     * @param StoreProductAttrValueServices $attrValueServices
     * @param int $productId
     * @param string $unique
     * @param int $defectiveStock
     * @param int $recordType
     */
    private function processDefectiveStock(StoreProductAttrValueServices $attrValueServices, int $productId, string $unique, int $defectiveStock, int $recordType): void
    {
        $pm = $defectiveStock > 0 ? 1 : 0; // 1:入库增加, 0:出库减少
        $action = ($recordType == StockRecord::TYPE_IN) ? '入库增加' : '出库减少';
        $defectiveStock  = abs($defectiveStock);
        $result = $attrValueServices->productAttrDefectiveStock($productId, $unique, $defectiveStock, 0, $pm);

        if (!$result) {
            Log::error("Stock事件监听器：{$action}残次品库存失败，商品ID：{$productId}，unique：{$unique}，数量：{$defectiveStock}");
        } else {
            Log::info("Stock事件监听器：{$action}残次品库存成功，商品ID：{$productId}，unique：{$unique}，数量：{$defectiveStock}");
        }
    }

    /**
     * 批量更新正品库存
     * @param StoreProductAttrValueServices $attrValueServices
     * @param array $attrsStock
     */
    private function batchUpdateStock(StoreProductAttrValueServices $attrValueServices, array $attrsStock): void
    {
        if (empty($attrsStock)) {
            return;
        }

        foreach ($attrsStock as $stockData) {
            try {
                $result = $attrValueServices->saveProductAttrsStock($stockData['product_id'], $stockData['attr'],0, false);

                if ($result) {
                    Log::info("Stock事件监听器：批量更新正品库存成功，商品ID：{$stockData['product_id']}");
                } else {
                    Log::error("Stock事件监听器：批量更新正品库存失败，商品ID：{$stockData['product_id']}");
                }
            } catch (\Exception $e) {
                Log::error("Stock事件监听器：批量更新正品库存异常，商品ID：{$stockData['product_id']}，错误：{$e->getMessage()}");
            }
        }
    }
}
