<?php

namespace app\services\system;

use app\dao\system\SystemRecommendDao;
use app\services\BaseServices;
use app\services\order\StoreCartServices;
use app\services\product\product\StoreProductLogServices;
use app\services\product\product\StoreProductServices;
use app\services\user\UserRelationServices;
use crmeb\exceptions\AdminException;
use think\annotation\Inject;

class SystemRecommendServices extends BaseServices
{
    /**
     * @var SystemRecommendDao
     */
    #[Inject]
    protected SystemRecommendDao $dao;

    /**
     * 获取推荐配置列表
     * @param array $where 查询条件
     * @return array
     */
    public function getRecommendList($where = [])
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->recommendList($where, $page, $limit, 'id,name,title,pic,type,update_time,status', 'id asc');
        foreach ($list as &$item) {
            $item['update_time'] = date('Y-m-d H:i:s', $item['update_time']);
        }
        $count = $this->dao->recommendCount($where);
        return compact('list', 'count');
    }

    /**
     * 获取推荐配置详情
     * @param int $id 推荐ID
     * @return array
     */
    public function getRecommendInfo($id)
    {
        $info = $this->dao->getOne(['id' => $id]);
        if ($info) {
            $info = $info->toArray();
            $info['specify_recommend_content'] = json_decode($info['specify_recommend_content'], true);
            $info['personality_recommend_content'] = json_decode($info['personality_recommend_content'], true);
            $info['sort_recommend_content'] = json_decode($info['sort_recommend_content'], true);
        } else {
            $info = [];
        }
        return $info;
    }

    /**
     * 编辑推荐标题
     * @param int $id 推荐ID
     * @param string $title 标题
     * @return bool
     */
    public function editRecommendTitle($id, $title)
    {
        $this->dao->update($id, ['title' => $title]);
        return true;
    }

    /**
     * 保存推荐配置
     * @param int $id 推荐ID
     * @param array $data 配置数据
     * @return bool
     * @throws AdminException
     */
    public function saveRecommend($id, $data)
    {
        $data['specify_recommend_content'] = json_encode($data['specify_recommend_content']);
        $data['personality_recommend_content'] = json_encode($data['personality_recommend_content']);
        $data['sort_recommend_content'] = json_encode($data['sort_recommend_content']);
        $data['update_time'] = time();
        if ($id) {
            $res = $this->dao->update($id, $data);
        } else {
            $res = $this->dao->save($data);
        }
        if (!$res) {
            throw new AdminException('保存失败');
        }
        return true;
    }

    /**
     * 设置推荐状态
     * @param int $id 推荐ID
     * @param int $status 状态
     * @return bool
     */
    public function setRecommendStatus($id, $status)
    {
        $data = [
            'status' => $status,
            'update_time' => time(),
        ];
        return $this->dao->update((int)$id, $data);
    }

    /**
     * 获取推荐商品ID列表
     * @param int $type 推荐类型
     * @param int $uid 用户ID
     * @return array
     */
    public function getRecommendProductIds($type, $uid = 0)
    {
        $info = $this->dao->getOne(['type' => $type]);
        if (!$info || !$info['status']) return ['为您推荐', []];
        $info = $info->toArray();
        $specifyProductIds = $personalityProductIds = $sortProductIds = [];
        $info['specify_recommend_content'] = json_decode($info['specify_recommend_content'], true);
        $info['personality_recommend_content'] = json_decode($info['personality_recommend_content'], true);
        $info['sort_recommend_content'] = json_decode($info['sort_recommend_content'], true);
        if ($info['specify_recommend_status'] == 1) {
            $specifyProductIds = array_column($info['specify_recommend_content'], 'product_id');
        }
        if ($uid != 0 && $info['personality_recommend_status'] == 1) {
            $productLogServices = app()->make(StoreProductLogServices::class);
            foreach ($info['personality_recommend_content'] as $item) {
                switch ($item['value']) {
                    case 1:
                        $cartProductIds = $productLogServices->getColumn([['uid', '=', $uid], ['type', '=', 'cart'], ['add_time', '>', strtotime('-30 days')]], 'product_id');
                        $personalityProductIds = array_merge($personalityProductIds, array_diff($cartProductIds, $personalityProductIds));
                        break;
                    case 2:
                        $relationProductIds = $productLogServices->getColumn([['uid', '=', $uid], ['type', '=', 'collect'], ['add_time', '>', strtotime('-30 days')]], 'product_id');
                        $personalityProductIds = array_merge($personalityProductIds, array_diff($relationProductIds, $personalityProductIds));
                        break;
                    case 3:
                        $visitProductIds = $productLogServices->getColumn([['uid', '=', $uid], ['type', '=', 'visit'], ['add_time', '>', strtotime('-30 days')]], 'product_id');
                        $personalityProductIds = array_merge($personalityProductIds, array_diff($visitProductIds, $personalityProductIds));
                        break;
                    case 4:
                        $payProductIds = $productLogServices->getColumn([['uid', '=', $uid], ['type', '=', 'pay'], ['add_time', '>', strtotime('-30 days')]], 'product_id');
                        $personalityProductIds = array_merge($personalityProductIds, array_diff($payProductIds, $personalityProductIds));
                        break;
                }
            }
        }
        if ($info['sort_recommend_status'] == 1) {
            $productLogServices = app()->make(StoreProductLogServices::class);
            $productServices = app()->make(StoreProductServices::class);
            foreach ($info['sort_recommend_content'] as $item) {
                switch ($item['value']) {
                    case 1:
                        $salesProductIds = [];
                        $salesProductList = $productLogServices->getProductIds('pay', 'product_id, COUNT(*) as pay_count', 'pay_count desc', 20);
                        if (count($salesProductList)) $salesProductIds = array_column($salesProductList, 'product_id');
                        $sortProductIds = array_merge($sortProductIds, array_diff($salesProductIds, $sortProductIds));
                        break;
                    case 2:
                        $payPriceProductIds = [];
                        $payPriceProductList = $productLogServices->getProductIds('pay', 'product_id, SUM(pay_price) as total_pay_price', 'total_pay_price desc', 20);
                        if (count($payPriceProductList)) $payPriceProductIds = array_column($payPriceProductList, 'product_id');
                        $sortProductIds = array_merge($sortProductIds, array_diff($payPriceProductIds, $sortProductIds));
                        break;
                    case 3:
                        $addTimeProductIds = $productServices->getProductIds(['is_show' => 1, 'is_del' => 0, 'is_verify' => 1], 'product_id, add_time', 'add_time desc', 20);
                        $sortProductIds = array_merge($sortProductIds, array_diff($addTimeProductIds, $sortProductIds));
                        break;
                    case 4:
                        $starProductIds = $productServices->getProductIds(['is_show' => 1, 'is_del' => 0, 'is_verify' => 1], 'product_id, star', 'star desc, add_time desc', 20);
                        $sortProductIds = array_merge($sortProductIds, array_diff($starProductIds, $sortProductIds));
                        break;
                    case 5:
                        $visitProductIds = [];
                        $visitProductList = $productLogServices->getProductIds('visit', 'product_id, COUNT(*) as visit_count', 'visit_count desc', 20);
                        if (count($visitProductList)) $visitProductIds = array_column($visitProductList, 'product_id');
                        $sortProductIds = array_merge($sortProductIds, array_diff($visitProductIds, $sortProductIds));
                        break;
                }

            }
        }
        $productIds = array_merge($specifyProductIds, array_diff($personalityProductIds, $specifyProductIds));
        $productIds = array_merge($productIds, array_diff($sortProductIds, $productIds));
        return [$info['title'], $productIds];
    }
}
