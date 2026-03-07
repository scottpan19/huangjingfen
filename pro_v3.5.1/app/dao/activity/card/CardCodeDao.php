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
declare (strict_types=1);

namespace app\dao\activity\card;

use app\dao\BaseDao;
use app\model\activity\card\CardCode;
use app\services\user\UserServices;

/**
 * 卡密
 * Class CardCodeDao
 * @package app\dao\activity\card
 */
class CardCodeDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return CardCode::class;
    }

    /**
     * 获取卡密列表
     * @param array $where 查询条件
     * @param int $page 页码
     * @param int $limit 每页数量
     * @param string $order 排序
     * @return array
     */
    public function getList($where = [], $page = 0, $limit = 0, $order = 'id desc')
    {
        return $this->search($where)
            ->when(isset($where['field_key']) && $where['field_key'] && isset($where['keyword']) && $where['keyword'], function ($query) use ($where) {
                switch ($where['field_key']) {
                    case 'card_number':
                        $query->whereLike('card_number', "%{$where['keyword']}%");
                        break;
                    case 'nickname':
                        $query->whereIn('uid', 'in', function ($q) use ($where) {
                            $q->name('user')->whereLike('nickname', '%' . $where['keyword'] . '%')->field(['uid'])->select();
                        });
                    case 'uid':
                        $query->where('uid', $where['keyword']);
                        break;
                }
            })
            ->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })
            ->order($order)->select()->toArray();
    }

}
