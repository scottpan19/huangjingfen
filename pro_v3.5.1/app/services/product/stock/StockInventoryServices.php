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

use app\dao\product\stock\StockInventoryDao;
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
 * @mixin StockInventoryDao
 */
class StockInventoryServices extends BaseServices
{
    /**
     * @var StockInventoryDao
     */
    #[Inject]
    protected StockInventoryDao $dao;

    /**
     * 获取出入库记录列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStockRecordList(array $where, array $with = [], $limits = 0): array
    {
        [$page, $limit] = $this->getPageValue();
        $limit = $limits ?: $limit;
        $with = is_array($with) ? array_merge($with, ['admin']) : ['admin'];
        $list = $this->dao->getList($where, '*', $page, $limit, $with);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 创建
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function createStockRecord(array $data, int $adminId, $id = 0): bool
    {
        // 验证数据
        $this->validateStockRecordData($data);

        return $this->transaction(function () use ($data, $adminId, $id) {
            $id = $this->saveStockInventoryRecord($data, $adminId, $id);

            $itemDao = app()->make(StockRecordItemDao::class);
            if ($id) {
                $itemDao->delete(['record_id' => $id, 'type' => 2]);
            }

            $items = [];
            $good_stock = $good_inventory_stock = $defective_stock = $defective_inventory_stock = 0;
            $out_library_items = [];
            $enter_library_items = [];

            $storeProductServices = app()->make(StoreProductServices::class);
            $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
            $product_ids = array_unique(array_column($data['product'], 'product_id'));
            $uniques = array_unique(array_column($data['product'], 'unique'));
            $product_list = $storeProductServices->search([])->whereIn('id', $product_ids)->column('id,store_name,code', 'id');
            $attr_list = $storeProductAttrValueServices->search([])->whereIn('unique', $uniques)->column('unique,suk,image,bar_code', 'unique');

            foreach ($data['product'] as $item) {
                $item['product_name'] = $product_list[$item['product_id']]['store_name'] ?? '';
                $item['product_code'] = $product_list[$item['product_id']]['code'] ?? '';
                $item['product_image'] = $attr_list[$item['unique']]['image'] ?? '';
                $item['product_suk'] = $attr_list[$item['unique']]['suk'] ?? '';
                $item['product_bar_code'] = $attr_list[$item['unique']]['bar_code'] ?? '';
                $this->processProductItem($item, $out_library_items, $enter_library_items, $good_stock, $good_inventory_stock, $defective_stock, $defective_inventory_stock, $items, $id);
            }

            $this->dao->update($id, [
                'good_stock' => $good_stock,
                'good_inventory_stock' => $good_inventory_stock,
                'defective_stock' => $defective_stock,
                'defective_inventory_stock' => $defective_inventory_stock
            ]);

            if (!$itemDao->saveAllItems($items)) {
                throw new AdminException('创建商品详情失败');
            }
            if ($data['status'] == 1) {
                $stockRecordServices = app()->make(StockRecordServices::class);
                if (!empty($out_library_items)) {
                    $out_library = [
                        'type' => StockRecord::TYPE_OUT,
                        'stock_type' => StockRecord::STOCK_TYPE_LOSS,
                        'record_date' => time(),
                        'product' => $out_library_items,
                        'remark' => '盘点亏损'
                    ];
                    $stockRecordServices->createStockRecord($out_library, $adminId);
                }
                if (!empty($enter_library_items)) {
                    $enter_library = [
                        'type' => StockRecord::TYPE_IN,
                        'stock_type' => StockRecord::STOCK_TYPE_PROFIT,
                        'record_date' => time(),
                        'product' => $enter_library_items,
                        'remark' => '盘点报溢'
                    ];
                    $stockRecordServices->createStockRecord($enter_library, $adminId);
                }
            }


            return true;
        });
    }

    /**
     * 创建盘点记录
     * @param array $data
     * @param int $adminId
     * @param $id
     * @return mixed
     */
    private function saveStockInventoryRecord(array $data, int $adminId, $id): mixed
    {
        if ($id) {
            $info = $this->dao->get($id);
            if (!$info) {
                throw new AdminException('盘点记录不存在');
            }
            $info->remark = $data['remark'] ?? '';
            $info->operator_id = $adminId;
            $info->status = $data['status'];
            $info->save();
        } else {
            // 生成单号
            $recordNo = app()->make(StockRecordServices::class)->generateUniqueRecordNo(3);
            // 创建主记录
            $recordData = [
                'record_no' => $recordNo,
                'remark' => $data['remark'] ?? '',
                'operator_id' => $adminId,
                'status' => $data['status'],
                'create_time' => time(),
                'update_time' => time(),
            ];
            $record = $this->dao->save($recordData);
            if (!$record) {
                throw new AdminException('创建盘点记录失败');
            }
            $id = $record->id;
        }
        return $id;
    }

    /**
     * 处理商品项
     * @param array $item
     * @param array $out_library_items
     * @param array $enter_library_items
     * @param $good_stock
     * @param $good_inventory_stock
     * @param $defective_stock
     * @param $defective_inventory_stock
     * @param array $items
     * @param int $id
     * @return void
     * User: liusl
     * DateTime: 2025/9/22 15:49
     */
    private function processProductItem(array $item, array &$out_library_items, array &$enter_library_items, &$good_stock, &$good_inventory_stock, &$defective_stock, &$defective_inventory_stock, array &$items, int $id)
    {
        $poor_good_stock = $poor_defective_stock = 0;
        if ($item['good_inventory_stock'] !== null) {
            $poor_good_stock = bcsub($item['good_inventory_stock'], $item['good_stock'], 0);
        }
        if ($item['defective_inventory_stock'] !== null) {
            $poor_defective_stock = bcsub($item['defective_inventory_stock'], $item['defective_stock'], 0);
        }

        $_enter_library_items = [];
        $_out_library_items = [];
        if ($poor_good_stock > 0) {
            $good_stock += (int)$poor_good_stock;
            $_enter_library_items = [
                'product_id' => $item['product_id'],
                'unique' => $item['unique'] ?? 0,
                'good_stock' => $poor_good_stock,
                'defective_stock' => 0,
            ];
        } elseif ($poor_good_stock < 0) {
            $good_inventory_stock += (int)$poor_good_stock;
            $_out_library_items = [
                'product_id' => $item['product_id'],
                'unique' => $item['unique'] ?? 0,
                'good_stock' => bcmul($poor_good_stock, -1, 0),
                'defective_stock' => 0,
            ];
        }

        if ($poor_defective_stock > 0) {
            $defective_stock += (int)$poor_defective_stock;

            if ($_enter_library_items) {
                $_enter_library_items['defective_stock'] = $poor_defective_stock;
            } else {
                $_enter_library_items = [
                    'product_id' => $item['product_id'],
                    'unique' => $item['unique'] ?? 0,
                    'good_stock' => 0,
                    'defective_stock' => $poor_defective_stock,
                ];
            }

        } elseif ($poor_defective_stock < 0) {
            $defective_inventory_stock += (int)$poor_defective_stock;

            if ($_out_library_items) {
                $_out_library_items['defective_stock'] = bcmul($poor_defective_stock, -1, 0);
            } else {
                $_out_library_items = [
                    'product_id' => $item['product_id'],
                    'unique' => $item['unique'] ?? 0,
                    'good_stock' => 0,
                    'defective_stock' => bcmul($poor_defective_stock, -1, 0),
                ];
            }
        }
        if (count($_out_library_items) > 0) {
            $out_library_items[] = $_out_library_items;
        }
        if (count($_enter_library_items) > 0) {
            $enter_library_items[] = $_enter_library_items;
        }


        $items[] = [
            'type' => 2,
            'record_id' => $id,
            'product_id' => $item['product_id'],
            'unique' => $item['unique'] ?? 0,
            'good_stock' => $item['good_inventory_stock'] !== null ? ($item['good_stock'] ?? 0) : 0,
            'good_inventory_stock' => $item['good_inventory_stock'] ?? 0,
            'defective_stock' => $item['defective_inventory_stock'] !== null ? ($item['defective_stock'] ?? 0) : 0,
            'defective_inventory_stock' => $item['defective_inventory_stock'] ?? 0,
            'product_name' => $item['product_name'] ?? '',
            'product_image' => $item['product_image'] ?? '',
            'product_code' => $item['product_code'] ?? '',
            'product_suk' => $item['product_suk'] ?? '',
            'product_bar_code' => $item['product_bar_code'] ?? '',
            'create_time' => time(),
            'update_time' => time(),
        ];
    }

    /**
     * 审核出入库记录
     * @param int $id
     * @param int $status
     * @param string $remark
     * @return bool
     * @throws \Exception
     */
    public function auditStockRecord(int $id, int $status, string $remark = ''): bool
    {
        $record = $this->dao->get($id);
        if (!$record) {
            throw new AdminException('盘点记录不存在');
        }


        Db::startTrans();
        try {
            // 更新记录状态
            $updateData = [
                'status' => $status,
                'audit_remark' => $remark,
                'audit_time' => time(),
                'update_time' => time(),
            ];

            if (!$this->dao->update($id, $updateData)) {
                throw new AdminException('更新记录状态失败');
            }


            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }


    /**
     * 验证出入库记录数据
     * @param array $data
     * @throws AdminException
     */
    protected function validateStockRecordData(array $data): void
    {

        if (empty($data['product']) || !is_array($data['product'])) {
            throw new AdminException('商品信息不能为空');
        }

        foreach ($data['product'] as $item) {
            if (empty($item['product_id'])) {
                throw new AdminException('商品ID不能为空');
            }
        }
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

    /**
     * 获取盘点详情
     * @param int $id 盘点记录ID
     * @return array
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function read($id)
    {
        $info = $this->dao->get($id, ['*'], ['admin']);
        if (!$info) {
            throw new AdminException('记录不存在');
        }
        $info = $info->toArray();

        $product = app()->make(StockRecordItemServices::class)->search(['record_id' => $id, 'type' => 2])->select()->toArray();
        if (!is_array($product)) {
            $product = [];
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
    public function remarkFrom($id)
    {
        $info = $this->dao->get($id);
        if (!$info) {
            throw new AdminException('记录不存在');
        }
        $field[] = Form::textarea('remark', '备注', $info['remark'] ?? '');
        return create_form('添加备注', $field, Url::buildUrl('/stock/inventory/remark_save/' . $id), 'POST');

    }

}
