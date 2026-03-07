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

use app\services\product\stock\StockRecordServices;
use think\facade\Log;

/**
 * 库存记录事件监听器
 * 专门用于创建出入库记录，不直接修改库存数量
 * 与Stock监听器配合使用：StockRecords负责记录，Stock负责库存同步
 *
 * @package app\listener\product
 */
class StockRecords
{
    /**
     * 处理库存记录事件
     *
     * 参数格式：
     *    [0] int $productId 商品ID
     *    [1] string $stockType 库存操作类型 (purchase, sale, return等)
     *    [2] array $attr 规格数组，每个元素包含：
     *        - unique: string SKU唯一标识
     *        - stock: int 库存数量
     *        - pm: int 出入库标识 (1:入库, 0:出库)
     *    [3] int $adminId 操作员ID
     *
     * @param array $event 事件参数数组
     * @return void
     */
    public function handle($event): void
    {
        try {
            // 解构多规格事件参数
            [$stockType, $type, $attr, $adminId, $order_id] = $event;
            // 验证基础参数
            if (!$this->validateMultiSpecBaseParams($stockType, $attr, $adminId)) {
                return;
            }

//            // 按出入库类型分组处理
//            $groupedSpecs = $this->groupSpecsByType($attr);

//            foreach ($groupedSpecs as $type => $specs) {
            $this->createMultiSpecRecord($type, $stockType, $attr, $adminId, $order_id);
//            }

            Log::info("StockRecords事件监听器：多规格库存记录创建成功", [
//                'product_id' => $productId,
                'stock_type' => $stockType,
                'specs_count' => count($attr),
                'admin_id' => $adminId
            ]);

        } catch (\Exception $e) {
            Log::error("StockRecords事件监听器执行异常：{$e->getMessage()}", [
                'event' => $event,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 验证多规格基础参数
     *
     * @param mixed $productId 商品ID
     * @param mixed $stockType 库存操作类型
     * @param mixed $attr 规格数组
     * @param mixed $adminId 操作员ID
     * @return bool
     */
    private function validateMultiSpecBaseParams($stockType, $attr, $adminId): bool
    {
        // 验证商品ID
//        if (!is_numeric($productId) || $productId <= 0) {
//            Log::error("StockRecords事件监听器：无效的商品ID", ['product_id' => $productId]);
//            return false;
//        }

        // 验证库存操作类型
        if (empty($stockType) || !is_string($stockType)) {
            Log::error("StockRecords事件监听器：无效的库存操作类型", ['stock_type' => $stockType]);
            return false;
        }

        // 验证规格数组
        if (!is_array($attr) || empty($attr)) {
            Log::error("StockRecords事件监听器：规格数组不能为空", ['attr' => $attr]);
            return false;
        }

        // 验证操作员ID
//        if (!is_numeric($adminId) || $adminId <= 0) {
//            Log::error("StockRecords事件监听器：无效的操作员ID", ['admin_id' => $adminId]);
//            return false;
//        }

        // 验证每个规格的数据
        foreach ($attr as $index => $spec) {
            if (!$this->validateSpecData($spec, $index)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 验证单个规格数据
     *
     * @param mixed $spec 规格数据
     * @param int $index 规格索引
     * @return bool
     */
    private function validateSpecData($spec, int $index): bool
    {
        if (!is_array($spec)) {
            Log::error("StockRecords事件监听器：规格数据必须是数组", ['index' => $index, 'spec' => $spec]);
            return false;
        }

        // 验证unique字段
        if (empty($spec['unique'])) {
            Log::error("StockRecords事件监听器：SKU唯一标识不能为空", ['index' => $index, 'spec' => $spec]);
            return false;
        }

        // 验证stock字段
        if (!isset($spec['inventory']) || !is_numeric($spec['inventory']) || $spec['inventory'] < 0) {
            Log::error("StockRecords事件监听器：无效的库存数量", ['index' => $index, 'spec' => $spec]);
            return false;
        }

        // 验证pm字段
//        if (!isset($spec['pm']) || !in_array($spec['pm'], [0, 1])) {
//            Log::error("StockRecords事件监听器：无效的出入库标识", ['index' => $index, 'spec' => $spec]);
//            return false;
//        }

        return true;
    }

    /**
     * 创建多规格库存记录
     *
     * @param int $productId 商品ID
     * @param int $type 出入库类型
     * @param string $stockType 库存操作类型
     * @param array $specs 同类型的规格数组
     * @param int $adminId 操作员ID
     * @return void
     */
    private function createMultiSpecRecord(int $type, string $stockType, array $specs, int $adminId, string $order_id): void
    {
        // 构建产品数组
        $products = [];
        foreach ($specs as $spec) {
            $products[] = [
                'product_id' => $spec['product_id'],
                'unique' => $spec['unique'],
                'good_stock' => (int)$spec['inventory'],
                'defective_stock' => 0 // 默认残次品库存为0
            ];
        }

        // 构建记录数据
        $recordData = [
            'stock_type' => $stockType,
            'after_sale_no' => $order_id,
            'type' => $type,
            'record_date' => time(),
            'remark' => $this->generateMultiSpecRemark($specs[0]['product_id'], $type, $stockType, $specs),
            'product' => $products
        ];

        // 创建库存记录
        $this->createStockRecord($recordData, $adminId);
    }

    /**
     * 生成多规格操作备注
     *
     * @param int $productId 商品ID
     * @param int $type 出入库类型
     * @param string $stockType 库存操作类型
     * @param array $specs 规格数组
     * @return string
     */
    private function generateMultiSpecRemark(int $productId, int $type, string $stockType, array $specs): string
    {
        $stockTypeText = $this->getStockTypeText($stockType);
        $totalStock = array_sum(array_column($specs, 'inventory'));
        $specCount = count($specs);

        return "系统自动记录：{$stockTypeText}，商品ID：{$productId}，规格数：{$specCount}，总数量：{$totalStock}";
    }

    /**
     * 获取库存操作类型文本
     *
     * @param string $stockType 库存操作类型
     * @return string 类型文本
     */
    private function getStockTypeText(string $stockType): string
    {
        $typeMap = [
            'purchase' => '采购',
            'return' => '退货',
            'other_in' => '其他入库',
            'defective_to_good' => '残次品转良',
            'expired_return' => '过期退货',
            'profit' => '盘盈',
            'use_out' => '试用出库',
            'scrap_out' => '报废出库',
            'good_to_defective' => '良品转残次品',
            'other_out' => '其他出库',
            'sale' => '销售',
            'loss' => '盘亏'
        ];

        return $typeMap[$stockType] ?? $stockType;
    }

    /**
     * 创建库存记录
     *
     * @param array $recordData 库存记录数据
     * @param int $adminId 操作员ID
     * @return void
     * @throws \Exception 创建失败时抛出异常
     */
    private function createStockRecord(array $recordData, int $adminId): void
    {
        /** @var StockRecordServices $stockRecordServices */
        $stockRecordServices = app()->make(StockRecordServices::class);

        // 第三个参数设为true，表示不触发库存同步事件，避免循环调用
        $result = $stockRecordServices->createStockRecord($recordData, $adminId, true);

        if (!$result) {
            throw new \Exception("创建库存记录失败");
        }
    }
}
