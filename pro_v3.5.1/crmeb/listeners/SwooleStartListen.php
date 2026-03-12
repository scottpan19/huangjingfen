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

namespace crmeb\listeners;


use crmeb\interfaces\ListenerInterface;
use Swoole\Runtime;

/**
 * swoole启动监听
 * Class SwooleStartListen
 * @package crmeb\listeners
 */
class SwooleStartListen implements ListenerInterface
{

    /**
     * 事件执行
     * @param $event
     */
    public function handle($event): void
    {
        if (!extension_loaded('swoole')) {
            return;
        }

        $flags = Runtime::getHookFlags();

        if (defined('SWOOLE_HOOK_CURL')) {
            $flags &= ~SWOOLE_HOOK_CURL;
        }
        if (defined('SWOOLE_HOOK_NATIVE_CURL')) {
            $flags &= ~SWOOLE_HOOK_NATIVE_CURL;
        }

        Runtime::setHookFlags($flags);
    }
}
