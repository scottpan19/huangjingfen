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
class StockInventory extends BaseModel
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'stock_inventory';

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
     * 关联出入库商品详情
     * @return \think\model\relation\HasMany
     */
    public function items()
    {
        return $this->hasMany(StockRecordItem::class, 'record_id', 'id')->where('type', 2);
    }

    public function admin()
    {
        return $this->hasOne(SystemAdmin::class, 'id', 'operator_id')->bind(['admin_name' => 'real_name']);
    }

    public function searchRecordNoAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('record_no', $value);
        }
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

    public function searchStatusAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('status', $value);
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
            $query->whereBetweenTime('record_date', strtotime($value[0]), strtotime($value[1]));
        }
    }

    public function searchCreateTimeAttr($query, $value)
    {
        $value = explode('-', $value);
        if (is_array($value) && count($value) == 2 && $value[0] != '' && $value[1] != '') {
            $query->whereBetweenTime('create_time', strtotime($value[0]), strtotime($value[1]) + 86400);
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
}
