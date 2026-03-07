<?php

namespace app\dao\out;

use app\dao\BaseDao;
use app\model\out\OutInterface;

class OutInterfaceDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return OutInterface::class;
    }

    /**
     * 获取接口列表
     * @param array $where 查询条件
     * @param string|array $field 查询字段
     * @return array
     */
    public function getInterfaceList($where, $field)
    {
        return $this->getModel()->where($where)->field($field)->select()->toArray();
    }
}