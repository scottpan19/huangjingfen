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

namespace app\controller\admin\v1\product\stock;

use app\controller\admin\AuthController;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\product\stock\StockRecordServices;
use think\annotation\Inject;

/**
 * 库存管理控制器
 * Class StockRecordController
 * @package app\controller\admin\stock
 */
class StockRecordController extends AuthController
{

    /**
     * @var StockRecordServices
     */
    #[Inject]
    protected StockRecordServices $services;


    /**
     * 获取出入库记录列表
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['record_no', ''],
            ['type', ''],
            ['stock_type', ''],
            ['record_date', ''],
            ['create_time', ''],
            ['unique', ''],
        ]);
        return $this->success($this->services->getStockRecordList($where));
    }

    /**
     * 创建出入库记录
     * @return \think\Response
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['type', 0],
            ['stock_type', 1],
            ['order_id', '', '', 'after_sale_no'],
            ['record_date', ''],
            ['remark', ''],
            ['product', []],
        ]);

        if (!$data['type']) {
            return $this->fail('请选择出入库类型');
        }

        if (!$data['record_date']) {
            return $this->fail('请选择出入库日期');
        }

        if (empty($data['product'])) {
            return $this->fail('请添加商品信息');
        }

        try {
            $this->services->createStockRecord($data, $this->adminId);
            return $this->success('创建成功');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }


    /**
     * 售后订单商品信息
     * @return \think\Response
     * User: liusl
     * DateTime: 2025/9/17 16:14
     */
    public function refundList()
    {
        [$order_id] = $this->request->postMore([
            ['order_id', 0],
        ], true);
        if (!$order_id) {
            return $this->fail('请填写售后单号');
        }
        return $this->success($this->services->getRefundList($order_id));
    }


    /**
     * 获取出入库记录详情
     * @param int $id
     * @return \think\Response
     */
    public function read()
    {
        [$unique, $id] = $this->request->postMore([
            ['unique', 0],
            ['id', 0],
        ], true);
        if (!$id) {
            return $this->fail('记录不存在');
        }
        return $this->success($this->services->read($id, $unique));
    }

    /**
     * 备注表单
     * @param $id
     * @return \think\Response
     * User: liusl
     * DateTime: 2025/9/18 14:36
     */
    public function remark($id)
    {
        if (!$id) {
            return $this->fail('记录不存在');
        }
        return $this->success($this->services->remarkFrom($id));
    }

    /**
     * 备注
     * @param $id
     * @return \think\Response
     * User: liusl
     * DateTime: 2025/9/18 14:43
     */
    public function remarkSave($id)
    {
        $data = $this->request->postMore([
            ['remark', ''],
        ]);
        if (!$id) {
            return $this->fail('记录不存在');
        }
        if (!$data['remark']) {
            return $this->fail('请填写备注');
        }
        try {
            $this->services->update($id, ['remark' => $data['remark']]);
            return $this->success('保存成功');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 出入库明细
     * @param StoreProductAttrValueServices $attrValueServices
     * @return \think\Response
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/9/19 11:22
     */
    public function productList(StoreProductAttrValueServices $attrValueServices)
    {
        $where = $this->request->getMore([
            ['keyword', ''],
            ['stock_range', ''],
        ]);
        $where['product_type'] = 0;
        return $this->success($attrValueServices->getAttrValueList($where));
    }

    /**
     * 明细卡片
     * @return \think\Response
     * User: liusl
     * DateTime: 2025/10/17 17:33
     */
    public function productStatistics()
    {
        $where = $this->request->getMore([
            ['keyword', ''],
            ['stock_range', ''],
            ['time', '']
        ]);
        return $this->success($this->services->getProductStatistics($where));
    }

    /**
     * 获取出入库统计数据
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function stockStatistics()
    {
        $where = $this->request->getMore([
            ['product_name', ''],
            ['record_date', ''],
            ['stock_type', ''],
        ]);

        return $this->success($this->services->getStockStatisticsList($where));
    }

    /**
     * 获取出入库整体统计卡片数据
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function overallStatistics()
    {
        $where = $this->request->getMore([
            ['product_name', ''],
            ['record_date', ''],
            ['stock_type', ''],
        ]);

        return $this->success($this->services->getStockOverallStatistics($where));
    }

}
