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
use app\services\product\stock\StockInventoryServices;
use think\annotation\Inject;

/**
 * 库存管理控制器
 * Class StockRecordController
 * @package app\controller\admin\stock
 */
class StockInventoryController extends AuthController
{

    /**
     * @var StockInventoryServices
     */
    #[Inject]
    protected StockInventoryServices $services;


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
            ['create_time', ''],
            ['unique', ''],
            ['status', ''],
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
            ['status', 0],
            ['remark', ''],
            ['product', []],
        ]);
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

    public function update($id)
    {
        $data = $this->request->postMore([
            ['status', 0],
            ['remark', ''],
            ['product', []],
        ]);

        if (empty($data['product'])) {
            return $this->fail('请添加商品信息');
        }
        try {
            $this->services->createStockRecord($data, $this->adminId, $id);
            return $this->success('创建成功');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }


    /**
     * 获取出入库记录详情
     * @param int $id
     * @return \think\Response
     */
    public function read($id)
    {
        if (!$id) {
            return $this->fail('记录不存在');
        }
        return $this->success($this->services->read($id));
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
}
