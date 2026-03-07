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

namespace app\listener\pay;

/**
 * 扫码支付异步回调
 * Class ScannedNotifyListener
 * @package app\listener\pay
 */
class ScannedNotifyListener
{
    /**
     * 处理扫码支付回调事件
     * @param mixed $event 事件数据
     * @return void
     */
    public function handle($event)
    {
        [$message] = $event;


    }
}
