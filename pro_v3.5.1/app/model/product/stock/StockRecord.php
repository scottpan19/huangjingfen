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

namespace app\model\product\stock;

use app\model\system\admin\SystemAdmin;
use crmeb\basic\BaseModel;

/**
 * 出入库记录模型
 * Class StockRecords
 * @package app\model\stock
 */
class StockRecord extends BaseModel
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'stock_record';

    /**
     * 主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 自动时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = true;

    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'create_time';

    /**
     * 更新时间字段
     * @var string
     */
    protected $updateTime = 'update_time';

    /**
     * 类型常量
     */
    const TYPE_IN = 1;  // 入库
    const TYPE_OUT = 2; // 出库

    public function getRecordDateAttr($value)
    {
        return $value ? date('Y-m-d', $value) : '';
    }

    /**
     * 出入库类型常量
     */
    const STOCK_TYPE_PURCHASE = 'purchase';           // 采购入库
    const STOCK_TYPE_RETURN = 'return';               // 退货入库
    const STOCK_TYPE_OTHER_IN = 'other_in';           // 其他入库
    const STOCK_TYPE_DEFECTIVE_TO_GOOD = 'defective_to_good'; // 残次品转良
    const STOCK_TYPE_EXPIRED_RETURN = 'expired_return'; // 过期退货
    const STOCK_TYPE_PROFIT = 'profit';               // 盘盈入库
    const STOCK_TYPE_USE_OUT = 'use_out';             // 试用出库
    const STOCK_TYPE_SCRAP_OUT = 'scrap_out';         // 报废出库
    const STOCK_TYPE_GOOD_TO_DEFECTIVE = 'good_to_defective'; // 良品转残次品
    const STOCK_TYPE_OTHER_OUT = 'other_out';         // 其他出库
    const STOCK_TYPE_SALE = 'sale';                   // 销售出库
    const STOCK_TYPE_LOSS = 'loss';                   // 盘亏出库

    /**
     * 获取类型文本
     * @param $value
     * @param $data
     * @return string
     */
    public function getTypeTextAttr($value, $data)
    {
        $typeMap = [
            self::TYPE_IN => '入库',
            self::TYPE_OUT => '出库'
        ];
        return $typeMap[$data['type']] ?? '未知';
    }

    /**
     * 获取出入库类型文本
     * @param $value
     * @param $data
     * @return string
     */
    public function getStockTypeTextAttr($value, $data)
    {
        $stockTypeMap = [
            self::STOCK_TYPE_PURCHASE => '采购入库',
            self::STOCK_TYPE_RETURN => '退货入库',
            self::STOCK_TYPE_OTHER_IN => '其他入库',
            self::STOCK_TYPE_DEFECTIVE_TO_GOOD => '残次品转良',
            self::STOCK_TYPE_EXPIRED_RETURN => '过期退货',
            self::STOCK_TYPE_PROFIT => '盘盈入库',
            self::STOCK_TYPE_USE_OUT => '试用出库',
            self::STOCK_TYPE_SCRAP_OUT => '报废出库',
            self::STOCK_TYPE_GOOD_TO_DEFECTIVE => '良品转残次品',
            self::STOCK_TYPE_OTHER_OUT => '其他出库',
            self::STOCK_TYPE_SALE => '销售出库',
            self::STOCK_TYPE_LOSS => '盘亏出库'
        ];
        return $stockTypeMap[$data['stock_type']] ?? '未知';
    }

    /**
     * 关联出入库商品详情
     * @return \think\model\relation\HasMany
     */
    public function items()
    {
        return $this->hasMany(StockRecordItem::class, 'record_id', 'id')->where('type', 1);
    }

    public function admin()
    {
        return $this->hasOne(SystemAdmin::class, 'id', 'operator_id')->bind(['admin_name' => 'real_name']);
    }


    public function searchIdAttr($query, $value)
    {
        if ($value !== '') {
            if (is_array($value)) {
                $query->whereIn('id', $value);
            } else {
                $query->where('id', $value);
            }
        }
    }

    /**
     * 搜索器：按类型搜索
     * @param $query
     * @param $value
     */
    public function searchTypeAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('type', $value);
        }
    }

    /**
     * 搜索器：按出入库类型搜索
     * @param $query
     * @param $value
     */
    public function searchStockTypeAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('stock_type', $value);
        }
    }

    public function searchRecordNoAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('record_no', $value);
        }
    }


    /**
     * 搜索器：按日期范围搜索
     * @param $query
     * @param $value
     */
    public function searchRecordDateAttr($query, $value)
    {
        $value = explode('-', $value);
        if (is_array($value) && count($value) == 2 && $value[0] != '' && $value[1] != '') {
            $query->whereBetweenTime('record_date', strtotime($value[0]), strtotime($value[1]) + 86399);
        }
    }

    public function searchCreateTimeAttr($query, $value)
    {
        $value = explode('-', $value);
        if (is_array($value) && count($value) == 2 && $value[0] != '' && $value[1] != '') {
            $query->whereBetweenTime('create_time', strtotime($value[0]), strtotime($value[1]) + 86399);
        }
    }

    public function searchUniqueAttr($query, $value)
    {
        if ($value !== '') {
            $query->whereIn('id', function ($q) use ($value) {
                $q->name('stock_record_item')->field('record_id')->where('unique', $value)->select();
            });
        }
    }

    /**
     * 获取出入库子类型名称
     * @param int $subType 子类型
     * @return string
     */
    public static function getStockTypeName($subType)
    {
        $names = [
            self::STOCK_TYPE_PURCHASE => '采购入库',
            self::STOCK_TYPE_RETURN => '退货入库',
            self::STOCK_TYPE_OTHER_IN => '其他入库',
            self::STOCK_TYPE_DEFECTIVE_TO_GOOD => '残次品转良',
            self::STOCK_TYPE_EXPIRED_RETURN => '过期退货',
            self::STOCK_TYPE_PROFIT => '盘盈入库',
            self::STOCK_TYPE_USE_OUT => '试用出库',
            self::STOCK_TYPE_SCRAP_OUT => '报废出库',
            self::STOCK_TYPE_GOOD_TO_DEFECTIVE => '良品转残次品',
            self::STOCK_TYPE_OTHER_OUT => '其他出库',
            self::STOCK_TYPE_SALE => '销售出库',
            self::STOCK_TYPE_LOSS => '盘亏出库',
        ];
        return $names[$subType] ?? '未知类型';
    }
}
