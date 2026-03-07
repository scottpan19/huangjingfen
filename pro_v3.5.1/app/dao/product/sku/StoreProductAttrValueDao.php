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

namespace app\dao\product\sku;

use app\dao\BaseDao;
use app\model\product\sku\StoreProductAttrValue;

/**
 * Class StoreProductAttrValueDao
 * @package app\dao\product\sku
 */
class StoreProductAttrValueDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreProductAttrValue::class;
    }

    /**
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return array
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, string $field = '*', int $page = 0, int $limit = 0, string $order = 'id desc', $with = [])
    {
        return $this->search($where)->field($field)
            ->when($with !== [], function ($query) use ($with) {
                $query->with($with);
            })

            ->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->when(!$page && $limit, function ($query) use ($limit) {
                $query->limit($limit);
            })->order($order)->select()->toArray();
    }

    /**
     * 获取商品规格数量
     * @param array $where 查询条件
     * @return int
     */
    public function getCounts($where = [])
    {
        return $this->search($where)
            ->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
                $query->where(function ($q) use ($where) {
                    $q->whereLike('bar_code|code|unique', '%' . trim($where['keyword']) . '%')->whereOr('product_id', 'in', function ($q) use ($where) {
                        $q->name('store_product')->field('id')->where('store_name|keyword|id|bar_code', '%' . trim($where['keyword']) . '%')->select();
                    });
                });
            })->count();
    }


    /**
     * 搜索
     * @param $where
     * @return \crmeb\basic\BaseModel
     * User: liusl
     * DateTime: 2025/9/19 10:50
     */
    public function joinAttrSearch($where = [])
    {
        return $this->getModel()->alias('a')
            ->join('store_product p', 'a.product_id=p.id')
            ->where('p.is_del', 0)
            ->where('a.product_type', 0)
            ->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
                $query->where(function ($q) use ($where) {
                    $q->whereLike('a.bar_code|a.code|a.unique', '%' . trim($where['keyword']) . '%')
                        ->whereOr('p.store_name|p.keyword|p.id|p.bar_code', 'like', '%' . trim($where['keyword']) . '%');
                });
            })
            ->when(isset($where['stock_range']) && $where['stock_range'], function ($query) use ($where) {
                $stock_range = explode('-', $where['stock_range']);

                if (count($stock_range) == 1) {
                    $query->where('a.stock', '>=', $stock_range[0]);
                }
                if (count($stock_range) == 2 && ($stock_range[0] !== '' || $stock_range[1] !== '')) {
                    if ($stock_range[0] === '') {
                        $query->where('a.stock', '<=', $stock_range[1]);
                    } elseif ($stock_range[1] === '') {
                        $query->where('a.stock', '>=', $stock_range[0]);
                    } else {
                        $query->whereBetween('a.stock', $stock_range);
                    }
                }
            });
    }

    /**
     * 减库存
     * @param array $where
     * @param int $num
     * @param string $stock
     * @param string $sales
     * @return bool|mixed
     */
    public function decStockIncSales(array $where, int $num, string $stock = 'stock', string $sales = 'sales')
    {
        $isQuota = false;
        if (isset($where['type']) && $where['type']) {
            $isQuota = true;
            if (count($where) == 2) {
                unset($where['type']);
            }
        }
        $field = $isQuota ? 'stock,quota' : 'stock';
        $product = $this->getModel()->where($where)->field($field)->find();
        if ($product) {
            return $this->getModel()->where($where)->when($isQuota, function ($query) use ($num) {
                $query->dec('quota', $num);
            })->dec($stock, $num)->dec('sum_stock', $num)->inc($sales, $num)->update();
        }
        return true;
    }

    /**
     * 加库存
     * @param array $where
     * @param int $num
     * @param string $stock
     * @param string $sales
     * @return bool|mixed
     */
    public function incStockDecSales(array $where, int $num, string $stock = 'stock', string $sales = 'sales')
    {
        $isQuota = false;
        if (isset($where['type']) && $where['type']) {
            $isQuota = true;
            if (count($where) == 2) {
                unset($where['type']);
            }
        }
        $salesOne = $this->getModel()->where($where)->value($sales);
        if ($salesOne) {
            $salesNum = $num;
            if ($num > $salesOne) {
                $salesNum = $salesOne;
            }
            return $this->getModel()->where($where)->when($isQuota, function ($query) use ($num) {
                $query->inc('quota', $num);
            })->inc($stock, $num)->inc('sum_stock', $num)->dec($sales, $salesNum)->update();
        }
        return true;
    }

    /**
     * 更新商品规格残次库存
     * @param int $productId 商品ID
     * @param string $unique 规格唯一标识
     * @param int $num 数量
     * @param int $type 商品类型
     * @param int $pm 增减标识(1增加/0减少)
     * @return bool
     */
    public function productAttrDefectiveStock(int $productId, string $unique, int $num, int $type = 0, int $pm = 1)
    {
        return !!$this->getModel()->where([
            'unique' => $unique,
            'product_id' => $productId,
            'type' => $type
        ])->when(isset($pm), function ($query) use ($num, $pm) {
            if ($pm == 1) {
                $query->inc('defective_stock', $num);
            } else {
                $query->dec('defective_stock', $num);
            }
        })->update();
    }


    /**
     * 根据条件获取规格value
     * @param array $where
     * @param string $field
     * @param string $key
     * @param bool $search
     * @return array
     */
    public function getColumn(array $where, string $field = '*', string $key = 'suk', bool $search = false, $order = '')
    {
        if ($search) {
            return $this->search($where)
                ->when(isset($where['store_id']) && $where['store_id'], function ($query) use ($where) {
                    $query->with(['storeBranch' => function ($querys) use ($where) {
                        $querys->where(['store_id' => $where['store_id'], 'product_id' => $where['product_id']]);
                    }]);
                })
                ->when($order != '', function ($query) use ($order) {
                    $query->order($order);
                })
                ->column($field, $key);
        } else {
            return $this->getModel()::where($where)
                ->when(isset($where['product_id']) && $where['product_id'], function ($query) use ($where, $field) {
                    if (is_array($where['product_id'])) {
                        $query->whereIn('product_id', $where['product_id']);
                    } else {
                        $query->where('product_id', $where['product_id']);
                    }
                })
                ->when($order != '', function ($query) use ($order) {
                    $query->order($order);
                })
                ->column($field, $key);
        }

    }

    /**
     * 根据条件删除规格value
     * @param int $id
     * @param int $type
     * @param array $suk
     * @return bool
     */
    public function del(int $id, int $type, array $suk = [])
    {
        return $this->search(['product_id' => $id, 'type' => $type, 'suk' => $suk])->delete();
    }

    /**
     * 保存数据
     * @param array $data
     * @return mixed|\think\Collection
     * @throws \Exception
     */
    public function saveAll(array $data)
    {
        return $this->getModel()->saveAll($data);
    }

    /**
     * 根据条件获取规格数据列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductAttrValue(array $where)
    {
        return $this->search($where)->order('id asc')->select()->toArray();
    }

    /**
     * 获取属性列表
     * @return mixed
     */
    public function attrValue()
    {
        return $this->search()->field('product_id,sum(sales * price) as val')->with(['product'])->group('product_id')->limit(20)->select()->toArray();
    }

    /**
     * 获取属性库存
     * @param string $unique
     * @return int
     */
    public function uniqueByStock(string $unique)
    {
        return $this->search(['unique' => $unique])->value('stock') ?: 0;
    }

    /**
     * 根据条形码获取一条商品规格信息
     * @param string $bar_code
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAttrByBarCode(string $bar_code)
    {
        return $this->getModel()->where('bar_code', $bar_code)->order('id desc')->find();
    }

    /**
     * 根据规格信息获取商品库存
     * @param array $ids
     * @return array|\think\Model|null
     */
    public function getProductStockByValues(array $ids)
    {
        return $this->getModel()->whereIn('product_id', $ids)->where('type', 0)
            ->field('`product_id` AS `id`, SUM(`stock`) AS `stock`')->group("product_id")->select()->toArray();
    }

    /**
     * 分组查询
     * @param string $file
     * @param string $group_id
     * @param array $where
     * @param string $having
     * @return array|\crmeb\basic\BaseModel[]|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGroupData(string $file, string $group_id, array $where, string $having = '')
    {
        return $this->getModel()->when($where, function ($query) use ($where) {
            $query->where($where);
        })->field($file)->group($group_id)->when($having, function ($query) use ($having) {
            $query->having($having);
        })->select();
    }

    /**
     * 库存警戒查询
     * @param array $where
     * @return int
     * @throws \think\db\exception\DbException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/4/18
     */
    public function getPolice(array $where)
    {
        return $this->getModel()->when($where, function ($query) use ($where) {
            $query->where($where);
        })->count();
    }

}
