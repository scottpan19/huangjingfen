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


use app\services\user\member\MemberRightServices;
use crmeb\interfaces\ListenerInterface;

/**
 * 会员信息更新成功事件监听
 * Class MemberUpdateSuccess
 * @package app\listener\user
 */
class MemberUpdateSuccess implements ListenerInterface
{
    /**
     * 处理会员信息更新成功事件
     * @param mixed $event 事件数据
     * @return void
     */
    public function handle($event): void
    {
        /** @var MemberRightServices $memberRightService */
        $memberRightService = app()->make(MemberRightServices::class);
        $memberRightService->cacheTag()->clear();
    }
}
