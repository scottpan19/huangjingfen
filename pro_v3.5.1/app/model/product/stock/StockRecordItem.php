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

use crmeb\basic\BaseModel;

/**
 * 出入库商品详情模型
 * Class StockRecordItem
 * @package app\model\stock
 */
class StockRecordItem extends BaseModel
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'stock_record_item';

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
     * 类型转换
     * @var array
     */
    protected $type = [
        'unit_price' => 'float',
        'total_amount' => 'float'
    ];

    /**
     * 获取总数量
     * @param $value
     * @param $data
     * @return int
     */
    public function getTotalQuantityAttr($value, $data)
    {
        return ($data['good_stock'] ?? 0) + ($data['defective_stock'] ?? 0);
    }

    /**
     * 关联出入库记录
     * @return \think\model\relation\BelongsTo
     */
    public function record()
    {
        return $this->belongsTo(StockRecord::class, 'record_id', 'id');
    }

    /**
     * 搜索器：按记录ID搜索
     * @param $query
     * @param $value
     */
    public function searchRecordIdAttr($query, $value)
    {
        if ($value !== '') {
            if (is_array($value)) {
                $query->whereIn('record_id', $value);
            } else {
                $query->where('record_id', $value);
            }
        }
    }

    /**
     * 搜索器：按商品ID搜索
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
     * 搜索器：按商品ID搜索
     * @param $query
     * @param $value
     */
    public function searchProductIdAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('product_id', $value);
        }
    }

    /**
     * 搜索器：按SKU ID搜索
     * @param $query
     * @param $value
     */
    public function searchUniqueAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('unique', $value);
        }
    }

    public function searchTimeAttr($query, $value)
    {
        $value = explode('-', $value);
        if (is_array($value) && count($value) == 2) {
            $query->whereBetweenTime('create_time', strtotime($value[0]), strtotime($value[1]) + 86399);
        }
    }

    public function searchKeywordsAttr($query, $value)
    {
        if ($value !== '') {
            $query->whereLike('product_id|unique|product_name|product_code|product_suk|product_bar_code', '%' . $value . '%');
        }
    }
}
