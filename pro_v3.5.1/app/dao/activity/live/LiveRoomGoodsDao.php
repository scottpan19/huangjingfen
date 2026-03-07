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

namespace app\dao\activity\live;


use app\dao\BaseDao;
use app\model\activity\live\LiveRoomGoods;

/**
 * 直播间关联商品
 * Class LiveRoomGoodsDao
 * @package app\dao\activity\live
 */
class LiveRoomGoodsDao extends BaseDao
{


    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return LiveRoomGoods::class;
    }

    /**
     * 清除直播间关联商品
     * @param int $id 直播间ID
     * @return bool
     */
    public function clear($id)
    {
        return $this->getModel()->where('live_room_id', $id)->delete();
    }
}
