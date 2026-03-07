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

namespace app\listener\user;


use app\services\work\WorkClientServices;
use crmeb\interfaces\ListenerInterface;

/**
 * 用户绑定客户
 * Class UserBindClient
 * @package app\listener\user
 */
class UserBindClient implements ListenerInterface
{
    /**
     * 处理用户绑定客户事件
     * @param mixed $event 事件数据
     * @return void
     */
    public function handle($event): void
    {
        [$uid, $unionid] = $event;

        try {
            /** @var WorkClientServices $make */
            $make = app()->make(WorkClientServices::class);
            $clientInfo = $make->get(['unionid' => $unionid], ['id', 'unionid', 'uid']);
            if ($clientInfo) {
                $clientInfo->uid = $uid;
                $clientInfo->save();
            }
        } catch (\Throwable $e) {
            \think\facade\Log::error(json_encode([
                'error' => '用户绑定客户失败:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]));
        }
    }
}
