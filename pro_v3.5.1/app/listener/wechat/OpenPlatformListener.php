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

namespace app\listener\wechat;

use EasyWeChat\Kernel\Contracts\EventHandlerInterface;

/**
 * 公众平台消息
 * Class OpenPlatformListener
 * @package app\listener\wechat
 */
class OpenPlatformListener implements EventHandlerInterface
{
    /**
     * 处理开放平台消息事件
     * @param mixed $payload 消息负载
     * @return void
     */
    public function handle($payload = null)
    {
        // TODO: Implement handle() method.
    }
}
