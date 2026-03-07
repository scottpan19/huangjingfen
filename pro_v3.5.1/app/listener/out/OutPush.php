<?php

namespace app\listener\out;

use app\jobs\out\OutPushJob;
use app\services\out\OutAccountServices;
use crmeb\interfaces\ListenerInterface;
use crmeb\services\CacheService;
use crmeb\services\HttpService;
use think\facade\Log;

/**
 * 外部推送事件监听
 * Class OutPush
 * @package app\listener\out
 */
class OutPush implements ListenerInterface
{
    /**
     * 处理外部推送事件
     * @param mixed $event 事件数据
     * @return void
     */
    public function handle($event): void
    {
        OutPushJob::dispatchDo('push', $event);
    }
}
