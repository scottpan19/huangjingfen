<?php

namespace app\dao\system;

use app\dao\BaseDao;
use app\model\system\SystemRecommend;

class SystemRecommendDao extends BaseDao
{
    /**
     * 设置模型名
     * @return string
     */
    protected function setModel(): string
    {
        return SystemRecommend::class;
    }

    /**
     * 获取条件查询模型
     * @param array $where 查询条件
     * @return \think\Model
     */
    public function getConditionModel($where)
    {
        return $this->getModel();
    }

    /**
     * 获取推荐列表
     * @param array $where 查询条件
     * @param int $page 页码
     * @param int $limit 每页数量
     * @param string $field 查询字段
     * @param string $order 排序
     * @return array
     */
    public function recommendList($where, $page = 0, $limit = 0, $field = '*', $order = 'id desc')
    {
        return $this->getConditionModel($where)->when($page != 0, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->field($field)->order($order)->select()->toArray();
    }

    /**
     * 获取推荐数量
     * @param array $where 查询条件
     * @return int
     */
    public function recommendCount($where)
    {
        return $this->getConditionModel($where)->count();
    }
}
