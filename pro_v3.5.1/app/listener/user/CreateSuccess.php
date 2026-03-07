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


use crmeb\interfaces\ListenerInterface;

/**
 * 用户创建成功事件监听
 * Class CreateSuccess
 * @package app\listener\user
 */
class CreateSuccess implements ListenerInterface
{
    /**
     * 处理用户创建成功事件
     * @param mixed $event 事件数据
     * @return void
     */
    public function handle($event): void
    {
        event('get.config');

    }
}
