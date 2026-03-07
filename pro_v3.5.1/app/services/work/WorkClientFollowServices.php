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

namespace app\services\work;


use app\dao\work\WorkClientFollowDao;
use app\services\BaseServices;
use think\annotation\Inject;

/**
 * 企业微信客户跟踪
 * Class WorkClientFollowServices
 * @package app\services\work
 * @mixin WorkClientFollowDao
 */
class WorkClientFollowServices extends BaseServices
{

    /**
     * @var WorkClientFollowDao
     */
    #[Inject]
    protected WorkClientFollowDao $dao;
    

    /**
     * 获取扫描渠道码添加的客户列表
     * @param int $channelId
     * @param string $name
     * @return array
     */
    public function getChannelCodeClientList(int $channelId, string $name = '')
    {
        [$page, $limit] = $this->getPageValue();
        $where = ['state' => 'channelCode-' . $channelId, 'user_name' => $name, 'is_del_user' => 0];
        $list = $this->dao->getDataList($where, ['createtime as create_time', 'client_id'], $page, $limit, 'create_time', ['client' => function ($query) {
            $query->field(['id', 'name', 'avatar']);
        }]);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 获取渠道码客户数量
     * @param int $channelId 渠道码ID
     * @return int
     */
    public function getChannelCodeClientCount($channelId)
    {
        return $this->dao->count(['state' => 'channelCode-' . $channelId, 'is_del_user' => 0]) ?? 0;
    }
}
