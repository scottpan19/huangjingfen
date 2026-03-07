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

namespace app\services\product\stock;

use app\dao\product\stock\StockRecordDao;
use app\dao\product\stock\StockRecordItemDao;
use app\model\product\stock\StockRecord;
use app\services\BaseServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrValueServices;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder as Form;
use think\annotation\Inject;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Route as Url;

/**
 * 库存管理服务类
 * Class StockRecordServices
 * @package app\services\stock
 * @mixin StockRecordDao
 */
class StockRecordServices extends BaseServices
{
    /**
     * @var StockRecordDao
     */
    #[Inject]
    protected StockRecordDao $dao;

    /**
     * 获取出入库记录列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStockRecordList(array $where, $with = [], $limits = 0): array
    {
        [$page, $limit] = $this->getPageValue();
        $limit = $limits ?: $limit;
        $with = is_array($with) ? array_merge($with, ['admin']) : ['admin'];
        $unique = $where['unique'] ?? '';
        if ($unique) {
            $with[] = 'items';
        }
        $list = $this->dao->getList($where, '*', $page, $limit, $with);
        $count = $this->dao->getCount($where);
        $_items = [];
        if ($unique) {
            $itemsList = array_column($list, 'items');
            foreach ($itemsList as $v) {
                foreach ($v as $item) {
                    if ($item && isset($item['unique']) && $item['unique'] == $unique) {
                        if (!isset($_items[$item['record_id']])) {
                            $_items[$item['record_id']] = [
                                'good_stock' => $item['good_stock'],
                                'defective_stock' => $item['defective_stock'],
                            ];
                        } else {
                            $_items[$item['record_id']]['good_stock'] += $item['good_stock'];
                            $_items[$item['record_id']]['defective_stock'] += $item['defective_stock'];
                        }
                    }
                }

            }
        }
        foreach ($list as &$item) {
            if ($unique) {
                $item['good_stock'] = $_items[$item['id']]['good_stock'] ?? 0;
                $item['defective_stock'] = $_items[$item['id']]['defective_stock'] ?? 0;
            }
            $item['stock_type_name'] = StockRecord::getStockTypeName($item['stock_type']);
//            unset($item['items']);
            if ($item['stock_type'] == 'sale') $item['admin_name'] = '用户购买';
        }

        return compact('list', 'count');
    }

    /**
     * 创建出入库记录
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function createStockRecord(array $data, int $adminId, $is_stock = false): bool
    {
        // 验证数据
        $this->validateStockRecordData($data);

        $id = $this->transaction(function () use ($data, $adminId) {
            // 生成单号
            $recordNo = $this->generateUniqueRecordNo($data['type']);
            // 创建主记录
            $recordData = [
                'record_no' => $recordNo,
                'type' => $data['type'],
                'stock_type' => $data['stock_type'],
                'after_sale_no' => $data['after_sale_no'] ?? '',
                'record_date' => is_numeric($data['record_date']) ? $data['record_date'] : strtotime($data['record_date']),
                'remark' => $data['remark'] ?? '',
                'operator_id' => $adminId,
                'create_time' => time(),
                'update_time' => time(),
            ];
            $recordId = $this->dao->save($recordData);
            if (!$recordId) {
                throw new AdminException('创建出入库记录失败');
            }

            // 创建商品详情
            $itemDao = app()->make(StockRecordItemDao::class);
            $items = [];

            $storeProductServices = app()->make(StoreProductServices::class);
            $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
            $product_ids = array_unique(array_column($data['product'], 'product_id'));
            $uniques = array_unique(array_column($data['product'], 'unique'));
            $product_list = $storeProductServices->search([])->whereIn('id', $product_ids)->column('id,store_name,code', 'id');
            $attr_list = $storeProductAttrValueServices->search([])->whereIn('unique', $uniques)->column('unique,suk,image,bar_code', 'unique');
            foreach ($data['product'] as $item) {
                $stock = $this->calculateStockChanges($data['type'], $data['stock_type'], $item['good_stock'] ?? 0, $item['defective_stock'] ?? 0);
                $items[] = [
                    'record_id' => $recordId->id,
                    'product_id' => $item['product_id'],
                    'record_date' => $recordData['record_date'],
                    'unique' => $item['unique'] ?? 0,
                    'stock_type' => $data['stock_type'],
                    'good_stock' => $stock['good_stock_change'] ?? 0,
                    'defective_stock' => $stock['defective_stock_change'] ?? 0,
                    'product_name' => $product_list[$item['product_id']]['store_name'] ?? '',
                    'product_code' => $product_list[$item['product_id']]['code'] ?? '',
                    'product_image' => $attr_list[$item['unique']]['image'] ?? '',
                    'product_suk' => $attr_list[$item['unique']]['suk'] ?? '',
                    'product_bar_code' => $attr_list[$item['unique']]['bar_code'] ?? '',
                    'create_time' => time(),
                    'update_time' => time(),
                ];
            }

            if (!$itemDao->saveAllItems($items)) {
                throw new AdminException('创建商品详情失败');
            }
            return $recordId->id;
        });
        if (!$is_stock) {
            event('product.stock.create', [$id]);
        }
        return true;

    }


    /**
     * 根据出入库类型计算库存变化量
     * @param int $recordType 记录类型 1=入库 2=出库
     * @param string $stockType 库存类型
     * @param int $goodStock 良品数量
     * @param int $defectiveStock 残次品数量
     * @return array
     */
    public function calculateStockChanges(int $recordType, string $stockType, $goodStock, $defectiveStock): array
    {
        switch ($stockType) {
            case StockRecord::STOCK_TYPE_DEFECTIVE_TO_GOOD: // 残次品转良品入库
                // 良品增加（正数），残次品减少（负数）
                $goodStockChange = $goodStock;
                $defectiveStockChange = -$goodStock;
                break;

            case StockRecord::STOCK_TYPE_GOOD_TO_DEFECTIVE: // 良品转残次品出库
                // 良品减少（负数），残次品增加（正数）
                $goodStockChange = -$goodStock;
                $defectiveStockChange = $goodStock;
                break;

            default:
                // 普通入库和出库逻辑
                if ($recordType == StockRecord::TYPE_IN) {
                    // 入库：良品和残次品都是正数
                    $goodStockChange = $goodStock;
                    $defectiveStockChange = $defectiveStock;
                } else {
                    // 出库：良品和残次品都是负数
                    $goodStockChange = $goodStock ? -$goodStock : 0;
                    $defectiveStockChange = $defectiveStock ? -$defectiveStock : 0;
                }
                break;
        }

        return [
            'good_stock_change' => $goodStockChange,
            'defective_stock_change' => $defectiveStockChange
        ];
    }


    /**
     * 获取商品出入库明细
     * @param array $where
     * @return array
     */
    public function getProductStockDetails(array $where): array
    {
        [$page, $limit] = $this->getPageValue();
        $itemDao = app()->make(StockRecordItemDao::class);

        $list = $itemDao->getProductStockDetails($where, $page, $limit);
        $count = $itemDao->getProductStockDetailsCount($where);

        return compact('list', 'count');
    }

    /**
     * 验证出入库记录数据
     * @param array $data
     * @throws AdminException
     */
    protected
    function validateStockRecordData(array $data): void
    {
        if (empty($data['type']) || !in_array($data['type'], [StockRecord::TYPE_IN, StockRecord::TYPE_OUT])) {
            throw new AdminException('出入库类型错误');
        }
        // 验证stock_type字段
        if (empty($data['stock_type'])) {
            throw new AdminException('出入库子类型不能为空');
        }

        $validStockTypes = [
            StockRecord::STOCK_TYPE_PURCHASE,
            StockRecord::STOCK_TYPE_RETURN,
            StockRecord::STOCK_TYPE_OTHER_IN,
            StockRecord::STOCK_TYPE_DEFECTIVE_TO_GOOD,
            StockRecord::STOCK_TYPE_EXPIRED_RETURN,
            StockRecord::STOCK_TYPE_PROFIT,
            StockRecord::STOCK_TYPE_USE_OUT,
            StockRecord::STOCK_TYPE_SCRAP_OUT,
            StockRecord::STOCK_TYPE_GOOD_TO_DEFECTIVE,
            StockRecord::STOCK_TYPE_OTHER_OUT,
            StockRecord::STOCK_TYPE_SALE,
            StockRecord::STOCK_TYPE_LOSS,
        ];

        if (!in_array($data['stock_type'], $validStockTypes)) {
            throw new AdminException('出入库子类型无效');
        }

        if (empty($data['record_date'])) {
            throw new AdminException('出入库日期不能为空');
        }

        if (empty($data['product']) || !is_array($data['product'])) {
            throw new AdminException('商品信息不能为空');
        }

        foreach ($data['product'] as $item) {
            if (empty($item['product_id'])) {
                throw new AdminException('商品ID不能为空');
            }
            if(isset($item['good_stock']) && isset($item['defective_stock']) && !$item['good_stock'] && !$item['defective_stock']){
                throw new AdminException('请填写出入库数量');
            }
        }
    }

    /**
     * 生成唯一的单号
     * @param int $type
     * @return string
     */
    public function generateUniqueRecordNo(int $type): string
    {
        $maxAttempts = 10;
        $attempts = 0;

        do {
            $recordNo = $this->dao->generateRecordNo($type);
            $exists = $this->dao->checkRecordNoExists($recordNo);
            $attempts++;
        } while ($exists && $attempts < $maxAttempts);

        if ($exists) {
            throw new AdminException('生成单号失败，请重试');
        }

        return $recordNo;
    }

    /**
     * 导出出入库记录
     * @param array $where
     * @return array
     */
    public
    function exportStockRecords(array $where): array
    {
        $list = $this->dao->getList($where, '*', 1, 0, ['items']);

        $exportData = [];
        foreach ($list as $record) {
            foreach ($record['items'] as $item) {
                $exportData[] = [
                    'record_no' => $record['record_no'],
                    'type' => $record['type'] == 1 ? '入库' : '出库',
                    'product_id' => $item['product_id'],
                    'sku_id' => $item['sku_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'record_date' => date('Y-m-d H:i:s', $record['record_date']),
                    'status' => $this->getStatusText($record['status']),
                    'remark' => $record['remark']
                ];
            }
        }

        return $exportData;
    }

    /**
     * 获取详情
     * @param $id
     * @return array
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/9/17 16:50
     */
    public function read($id, $unique = '')
    {
        $info = $this->dao->get($id, ['*'], ['admin']);
        if (!$info) {
            throw new AdminException('记录不存在');
        }
        $info = $info->toArray();
        $info['stock_type_name'] = StockRecord::getStockTypeName($info['stock_type']);

        $product = app()->make(StockRecordItemServices::class)->search(['record_id' => $id, 'unique' => $unique, 'type' => 1])->select()->toArray();
        if (!is_array($product)) {
            $product = [];
        }
        foreach ($product as &$item){
            $item['good_stock'] = abs($item['good_stock']);
            $item['defective_stock'] = abs($item['defective_stock']);
        }
        $info['product'] = $product;
        return $info;
    }

    /**
     * 备注表单
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/9/18 14:42
     */
    public
    function remarkFrom($id)
    {
        $info = $this->dao->get($id);
        if (!$info) {
            throw new AdminException('记录不存在');
        }
        $field[] = Form::textarea('remark', '备注：', $info['remark'] ?? '');
        return create_form('添加备注', $field, Url::buildUrl('/stock/record/remark_save/' . $id), 'POST');

    }

    /**
     * 获取售后单商品信息列表
     * @param string $order_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/9/17 16:15
     */
    public
    function getRefundList(string $order_id)
    {
        $storeOrderRefundServices = app()->make(StoreOrderRefundServices::class);
        if ($this->dao->get(['after_sale_no' => $order_id, 'type' => 1])) {
            throw new AdminException('该订单已经入库过了');
        }
        $order = $storeOrderRefundServices->get(['order_id' => $order_id], ['*']);
        if (!$order) throw new ValidateException('订单不存在');
        $order = $order->toArray();
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $orderInfo = $orderServices->get($order['store_order_id'], ['*'], ['invoice', 'virtual']);
        $orderInfo = $orderInfo->toArray();
        $orderInfo = $orderServices->tidyOrder($orderInfo, true, true);
        $cartInfo = $orderInfo['cartInfo'];
        return $cartInfo;
    }

    /**
     * 获取库存类型定义
     * @return array
     */
    private function getStockTypes(): array
    {
        return [
            'in' => ['purchase', 'return', 'other_in', 'defective_to_good', 'profit'],
            'out' => ['expired_return', 'use_out', 'scrap_out', 'good_to_defective', 'other_out', 'sale', 'loss']
        ];
    }

    /**
     * 构建基础查询模型
     * @param array $where
     * @return mixed
     */
    private function buildBaseQuery(array $where)
    {
        /** @var StockRecordItemDao $stockRecordItemDao */
        $stockRecordItemDao = app()->make(StockRecordItemDao::class);

        /** @var StoreProductServices $productServices */
        $productServices = app()->make(StoreProductServices::class);

        // 构建基础查询
        $model = $stockRecordItemDao->search(['type' => 1, 'keywords' => $where['product_name'] ?? '']);

        $where['record_date'] = $where['record_date'] && is_string($where['record_date']) ? explode('-', $where['record_date']) : [];
        if (count($where['record_date']) == 2 && $where['record_date'][0] && $where['record_date'][1]) {
            $startTime = strtotime($where['record_date'][0]);
            $endTime = strtotime($where['record_date'][1]) + 86400 - 1;
            $model = $model->whereBetween('record_date', [$startTime, $endTime]);
        }

        return $model;
    }

    /**
     * 获取库存类型名称映射
     * @return array
     */
    private function getStockTypeNames(): array
    {
        return [
            'purchase' => '采购入库',
            'return' => '退货入库',
            'other_in' => '其他入库',
            'defective_to_good' => '残次品转良',
            'expired_return' => '过期退货',
            'profit' => '盘盈入库',
            'use_out' => '试用出库',
            'scrap_out' => '报废出库',
            'good_to_defective' => '良品转残次品',
            'other_out' => '其他出库',
            'sale' => '销售出库',
            'loss' => '盘亏出库'
        ];
    }

    /**
     * 获取出入库统计数据列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStockStatisticsList(array $where): array
    {
        [$page, $limit] = $this->getPageValue();

        /** @var StoreProductServices $productServices */
        $productServices = app()->make(StoreProductServices::class);

        /** @var StoreProductAttrValueServices $attrValueServices */
        $attrValueServices = app()->make(StoreProductAttrValueServices::class);

        // 获取库存类型定义
        $stockTypes = $this->getStockTypes();
        $inStockTypes = $stockTypes['in'];
        $outStockTypes = $stockTypes['out'];

        // 构建基础查询
        $model = $this->buildBaseQuery($where);
        if ($model === null) {
            return ['list' => [], 'count' => 0];
        }

        // 根据stock_type参数筛选入库或出库
        if (isset($where['stock_type']) && $where['stock_type'] !== '') {
            if ($where['stock_type'] == 1) {
                // 查询入库统计
                $model = $model->whereIn('stock_type', $inStockTypes);
            } elseif ($where['stock_type'] == 2) {
                // 查询出库统计
                $model = $model->whereIn('stock_type', $outStockTypes);
            }
        }

        // 按商品规格唯一值分组统计，分别统计各种类型的数量
        $fieldArray = [
            'product_id',
            'product_suk',
            'product_name',
            'product_image',
            'product_bar_code',
            'unique',
            'SUM(CASE 
                WHEN stock_type = "defective_to_good" THEN good_stock 
                WHEN stock_type = "good_to_defective" THEN defective_stock 
                ELSE good_stock + defective_stock 
            END) as total_stock',
            'product_bar_code'
        ];

        // 根据查询类型添加对应的统计字段
        if ($where['stock_type'] == 1) {
            // 入库统计
            foreach ($inStockTypes as $type) {
                if ($type === 'defective_to_good') {
                    $fieldArray[] = "SUM(CASE WHEN stock_type = '{$type}' THEN good_stock ELSE 0 END) as {$type}_stock";
                } else {
                    $fieldArray[] = "SUM(CASE WHEN stock_type = '{$type}' THEN good_stock + defective_stock ELSE 0 END) as {$type}_stock";
                }
            }
        } else {
            // 出库统计
            foreach ($outStockTypes as $type) {
                if ($type === 'good_to_defective') {
                    $fieldArray[] = "SUM(CASE WHEN stock_type = '{$type}' THEN defective_stock ELSE 0 END) as {$type}_stock";
                } else {
                    $fieldArray[] = "SUM(CASE WHEN stock_type = '{$type}' THEN good_stock + defective_stock ELSE 0 END) as {$type}_stock";
                }
            }
        }

        $statistics = $model->field($fieldArray)
            ->group('product_id, unique')
            ->page($page, $limit)
            ->select()
            ->toArray();

        // 获取总数 - 重新构建查询
        $countModel = $this->buildBaseQuery($where);
        if ($countModel === null) {
            return ['list' => [], 'count' => 0];
        }

        if (isset($where['stock_type']) && $where['stock_type'] !== '') {
            if ($where['stock_type'] == 1) {
                $countModel = $countModel->whereIn('stock_type', $inStockTypes);
            } elseif ($where['stock_type'] == 2) {
                $countModel = $countModel->whereIn('stock_type', $outStockTypes);
            }
        }

        // 获取分组后的总数
        $countResult = $countModel->field('product_id, unique')->group('product_id, unique')->select();
        $count = count($countResult);

        // 获取类型名称映射
        $typeNames = $this->getStockTypeNames();

        // 组装返回数据
        $list = [];
        foreach ($statistics as $item) {

            $result = [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'unique' => $item['unique'],
                'bar_code' => $item['product_bar_code'] ?? '',
                'sku_name' => $item['product_suk'],
                'total_stock' => $item['total_stock']
            ];
            $_result[] = [
                'prop' => abs((int)$item['total_stock']),
                'label' => '总库存'
            ];
            // 添加各种类型的统计数据
            $allTypes = array_merge($inStockTypes, $outStockTypes);
            foreach ($allTypes as $type) {
                $stockKey = $type . '_stock';
                if (isset($item[$stockKey])) {
                    $_result[] = [
                        'prop' => abs((int)$item[$stockKey]),
                        'label' => $typeNames[$type]
                    ];
                }
            }
            $result['result'] = $_result;
            $list[] = $result;
        }

        return compact('list', 'count');
    }

    /**
     * 获取出入库整体统计数据
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStockOverallStatistics(array $where): array
    {
        // 获取库存类型定义
        $stockTypes = $this->getStockTypes();
        $inStockTypes = $stockTypes['in'];
        $outStockTypes = $stockTypes['out'];

        // 构建基础查询
        $model = $this->buildBaseQuery($where);
        if ($model === null) {
            return $this->getEmptyStatisticsCards($where);
        }

        // 根据stock_type参数筛选入库或出库
        if (isset($where['stock_type']) && $where['stock_type'] !== '') {
            if ($where['stock_type'] == 1) {
                // 只查询入库统计
                $model = $model->whereIn('stock_type', $inStockTypes);
            } elseif ($where['stock_type'] == 2) {
                // 只查询出库统计
                $model = $model->whereIn('stock_type', $outStockTypes);
            }
        }

        // 根据查询类型构建不同的统计字段
        if (isset($where['stock_type']) && $where['stock_type'] == 1) {
            // 只统计入库
            $fieldArray = [
                'SUM(CASE 
                    WHEN stock_type = "defective_to_good" THEN good_stock 
                    WHEN stock_type IN ("' . implode('","', array_diff($inStockTypes, ['defective_to_good'])) . '") THEN good_stock + defective_stock 
                    ELSE 0 
                END) as total_in_stock',
                'SUM(CASE WHEN stock_type = "purchase" THEN good_stock + defective_stock ELSE 0 END) as purchase_stock',
                'SUM(CASE WHEN stock_type = "profit" THEN good_stock + defective_stock ELSE 0 END) as profit_stock',
                'SUM(CASE WHEN stock_type = "return" THEN good_stock + defective_stock ELSE 0 END) as return_stock',
                'SUM(CASE WHEN stock_type = "other_in" THEN good_stock + defective_stock ELSE 0 END) as other_in_stock',
                'SUM(CASE WHEN stock_type = "defective_to_good" THEN good_stock ELSE 0 END) as defective_to_good_stock'
            ];
        } else {
            // 只统计出库
            $fieldArray = [
                'SUM(CASE 
                    WHEN stock_type = "good_to_defective" THEN defective_stock 
                    WHEN stock_type IN ("' . implode('","', array_diff($outStockTypes, ['good_to_defective'])) . '") THEN good_stock + defective_stock 
                    ELSE 0 
                END) as total_out_stock',
                'SUM(CASE WHEN stock_type = "sale" THEN good_stock + defective_stock ELSE 0 END) as sale_stock',
                'SUM(CASE WHEN stock_type = "loss" THEN good_stock + defective_stock ELSE 0 END) as loss_stock',
                'SUM(CASE WHEN stock_type = "expired_return" THEN good_stock + defective_stock ELSE 0 END) as expired_return_stock',
                'SUM(CASE WHEN stock_type = "use_out" THEN good_stock + defective_stock ELSE 0 END) as use_out_stock',
                'SUM(CASE WHEN stock_type = "scrap_out" THEN good_stock + defective_stock ELSE 0 END) as scrap_out_stock',
                'SUM(CASE WHEN stock_type = "other_out" THEN good_stock + defective_stock ELSE 0 END) as other_out_stock',
                'SUM(CASE WHEN stock_type = "good_to_defective" THEN defective_stock ELSE 0 END) as good_to_defective_stock'
            ];
        }

        // 执行统计查询
        $statistics = $model->field($fieldArray)->find();

        if (!$statistics) {
            return $this->getEmptyStatisticsCards($where);
        }

        // 转换为数组
        $stats = $statistics->toArray();

        // 根据查询类型组装返回数据
        if (isset($where['stock_type']) && $where['stock_type'] == 1) {
            // 只返回入库统计卡片
            return [
                ['name' => '总入库数', 'type' => 1, 'field' => '件', 'count' => (int)$stats['total_in_stock'], 'className' => 'icondingdanjine', 'col' => 8],
                ['name' => '采购入库', 'type' => 1, 'field' => '件', 'count' => (int)$stats['purchase_stock'], 'className' => 'iconshouyintai-shouyin1', 'col' => 8],
                ['name' => '盘盈入库', 'type' => 1, 'field' => '件', 'count' => (int)$stats['profit_stock'], 'className' => 'iconhexiaodingdanjine', 'col' => 8],
                ['name' => '退货入库', 'type' => 1, 'field' => '件', 'count' => (int)$stats['return_stock'], 'className' => 'iconshouhou_tuikuan', 'col' => 8],
                ['name' => '其他入库', 'type' => 1, 'field' => '件', 'count' => (int)$stats['other_in_stock'], 'className' => 'icontuikuandingdanliang', 'col' => 8],
                ['name' => '残次品转良品', 'type' => 1, 'field' => '件', 'count' => (int)$stats['defective_to_good_stock'], 'className' => 'iconfenpeidingdanjine', 'col' => 8],
            ];
        } else {
            // 只返回出库统计卡片
            return [
                ['name' => '总出库数', 'type' => 1, 'field' => '件', 'count' => abs((int)$stats['total_out_stock']), 'className' => 'icondingdanjine', 'col' => 6],
                ['name' => '销售出库', 'type' => 1, 'field' => '件', 'count' => abs((int)$stats['sale_stock']), 'className' => 'iconzaishoushangpin', 'col' => 6],
                ['name' => '盘亏出库', 'type' => 1, 'field' => '件', 'count' => abs((int)$stats['loss_stock']), 'className' => 'icondaishenhe-shequneirong', 'col' => 6],
                ['name' => '过期退货', 'type' => 1, 'field' => '件', 'count' => abs((int)$stats['expired_return_stock']), 'className' => 'iconshouyintai-tuihuo1', 'col' => 6],
                ['name' => '试用出库', 'type' => 1, 'field' => '件', 'count' => abs((int)$stats['use_out_stock']), 'className' => 'iconshouhou-tuikuan-lv', 'col' => 6],
                ['name' => '报废出库', 'type' => 1, 'field' => '件', 'count' => abs((int)$stats['scrap_out_stock']), 'className' => 'iconjingjiekucun', 'col' => 6],
                ['name' => '其他出库', 'type' => 1, 'field' => '件', 'count' => abs((int)$stats['other_out_stock']), 'className' => 'icontuikuandingdanliang', 'col' => 6],
                ['name' => '良品转残次品', 'type' => 1, 'field' => '件', 'count' => abs((int)$stats['good_to_defective_stock']), 'className' => 'iconfenpeidingdanjine', 'col' => 6],
            ];
        }
    }

    /**
     * 获取空的统计数据卡片
     * @param array $where
     * @return array
     */
    private function getEmptyStatisticsCards(array $where = []): array
    {
        // 根据查询类型返回对应的空数据卡片
        if (isset($where['stock_type']) && $where['stock_type'] == 1) {
            // 只返回入库空数据卡片
            return [
                ['name' => '总入库数', 'type' => 1, 'field' => '件', 'count' => 0, 'className' => 'icondingdanjine', 'col' => 8],
                ['name' => '采购入库', 'type' => 1, 'field' => '件', 'count' => 0, 'className' => 'iconshouyintai-shouyin1', 'col' => 8],
                ['name' => '盘盈入库', 'type' => 1, 'field' => '件', 'count' => 0, 'className' => 'iconhexiaodingdanjine', 'col' => 8],
                ['name' => '退货入库', 'type' => 1, 'field' => '件', 'count' => 0, 'className' => 'iconshouhou_tuikuan', 'col' => 8],
                ['name' => '其他入库', 'type' => 1, 'field' => '件', 'count' => 0, 'className' => 'icontuikuandingdanliang', 'col' => 8],
                ['name' => '残次品转良品', 'type' => 1, 'field' => '件', 'count' => 0, 'className' => 'iconfenpeidingdanjine', 'col' => 8]
            ];
        } else {
            // 只返回出库空数据卡片
            return [
                ['name' => '总出库数', 'type' => 1, 'field' => '件', 'count' => 0, 'className' => 'iconchuku', 'col' => 6],
                ['name' => '销售出库', 'type' => 1, 'field' => '件', 'count' => 0, 'className' => 'iconzaishoushangpin', 'col' => 6],
                ['name' => '盘亏出库', 'type' => 1, 'field' => '件', 'count' => 0, 'className' => 'icondaishenhe-shequneirong', 'col' => 6],
                ['name' => '过期退货', 'type' => 1, 'field' => '件', 'count' => 0, 'className' => 'iconshouyintai-tuihuo1', 'col' => 6],
                ['name' => '试用出库', 'type' => 1, 'field' => '件', 'count' => 0, 'className' => 'iconshouhou-tuikuan-lv', 'col' => 6],
                ['name' => '报废出库', 'type' => 1, 'field' => '件', 'count' => 0, 'className' => 'iconjingjiekucun', 'col' => 6],
                ['name' => '其他出库', 'type' => 1, 'field' => '件', 'count' => 0, 'className' => 'icontuikuandingdanliang', 'col' => 6],
                ['name' => '良品转残次品', 'type' => 1, 'field' => '件', 'count' => 0, 'className' => 'iconfenpeidingdanjine', 'col' => 6]
            ];
        }
    }

    /**
     * 明细导出查询
     * @param array $where
     * @param $with
     * @param int $limits
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/10/11 09:38
     */
    public function getStockDetailsList(array $where, int $limits = 0): array
    {
        [$page, $limit] = $this->getPageValue();
        $limit = $limits ?: $limit;

        $list = $this->dao->getList($where, '*', $page, $limit, ['admin']);
        $ids = array_column($list, 'id');
        $items = app()->make(StockRecordItemServices::class)->search(['record_id' => $ids, 'unique' => $where['unique']])->select()->toArray();
        $_items = [];
        foreach ($items as $item) {
            $_items[$item['record_id']][] = $item;
        }
        foreach ($list as &$item) {
            $item['items'] = $_items[$item['id']] ?? [];
            $item['stock_type_name'] = StockRecord::getStockTypeName($item['stock_type']);
        }

        return $list;
    }

    /**
     * 获取库存统计概览数据
     * @param array $where 查询条件
     * @return array
     */
    public function getProductStatistics($where = [])
    {
        // 获取当前库存数量（不受时间筛选影响）
        $currentStock = $this->getCurrentStock($where);
        //警戒库存商品
        $store_stock = sys_config('store_stock', 0);
        $productIds = app()->make(StoreProductServices::class)->search(['status' => 5])->column('id');
        $attrStock = app()->make(StoreProductAttrValueServices::class)->search(['product_id' => $productIds])->where('stock', '<=', $store_stock)->sum('stock');
        return [
            ['name' => '良品库存', 'field' => '件', 'count' => $currentStock['good_stock'] ?? 0, 'className' => 'ios-cube', 'col' => 8],
//            ['name' => '良品入库数量', 'field' => '件', 'count' => $stockMovement['good_in_stock'] ?? 0, 'className' => 'ios-arrow-down', 'col' => 8],
//            ['name' => '良品出库数量', 'field' => '件', 'count' => $stockMovement['good_out_stock'] ?? 0, 'className' => 'ios-arrow-up', 'col' => 8],
            ['name' => '残次品库存', 'field' => '件', 'count' => $currentStock['defective_stock'] ?? 0, 'className' => 'ios-arrow-round-down', 'col' => 8],
            ['name' => '警戒库存', 'field' => '件', 'count' => $attrStock ?? 0, 'className' => 'ios-warning', 'col' => 8],
//            ['name' => '残次品出库数量', 'field' => '件', 'count' => $stockMovement['defective_out_stock'] ?? 0, 'className' => 'ios-arrow-round-up', 'col' => 8],
        ];
    }

    /**
     * 获取当前库存数量（不受时间筛选影响）
     * @param array $where
     * @return array
     */
    private function getCurrentStock($where = [])
    {
        /** @var StoreProductAttrValueServices $attrValueServices */
        $attrValueServices = app()->make(StoreProductAttrValueServices::class);

        unset($where['time']);
        $where['type'] = 0;

        // 查询当前库存
        $stockData = $attrValueServices->joinAttrSearch($where)
            ->field('SUM(a.stock) as good_stock, SUM(a.defective_stock) as defective_stock')
            ->find();

        return $stockData ? $stockData->toArray() : ['good_stock' => 0, 'defective_stock' => 0];
    }

    /**
     * 获取出入库统计数量（根据时间筛选）
     * @param array $where
     * @return array
     */
    private function getStockMovement($where = [])
    {
        /** @var StockRecordItemDao $itemDao */
        $itemDao = app()->make(StockRecordItemDao::class);
        $query = $itemDao->search($where);


        // 定义入库和出库类型
        $inStockTypes = ['purchase', 'return', 'other_in', 'defective_to_good', 'profit'];
        $outStockTypes = ['expired_return', 'use_out', 'scrap_out', 'good_to_defective', 'other_out', 'sale', 'loss'];

        // 统计入库和出库数量
        $statistics = $query->field([
            // 良品入库数量
            'SUM(CASE WHEN stock_type IN ("' . implode('","', $inStockTypes) . '") THEN good_stock ELSE 0 END) as good_in_stock',
            // 良品出库数量
            'SUM(CASE WHEN stock_type IN ("' . implode('","', $outStockTypes) . '") THEN good_stock ELSE 0 END) as good_out_stock',
            // 残次品入库数量
            'SUM(CASE WHEN stock_type IN ("' . implode('","', $inStockTypes) . '") THEN defective_stock ELSE 0 END) as defective_in_stock',
            // 残次品出库数量
            'SUM(CASE WHEN stock_type IN ("' . implode('","', $outStockTypes) . '") THEN defective_stock ELSE 0 END) as defective_out_stock'
        ])->find();

        return $statistics ? $statistics->toArray() : [
            'good_in_stock' => 0,
            'good_out_stock' => 0,
            'defective_in_stock' => 0,
            'defective_out_stock' => 0
        ];
    }
}
