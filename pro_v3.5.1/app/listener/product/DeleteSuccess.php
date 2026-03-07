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

namespace app\listener\product;


use app\services\order\StoreCartServices;
use crmeb\interfaces\ListenerInterface;

/**
 * 删除商品成功事件
 * Class DeleteSuccess
 * @package app\listener\product
 */
class DeleteSuccess implements ListenerInterface
{
    /**
     * 处理商品删除成功事件
     * @param mixed $event 事件数据
     * @return void
     */
    public function handle($event): void
    {
        [$id] = $event;
        /** @var StoreCartServices $cartService */
        $cartService = app()->make(StoreCartServices::class);
        $cartService->changeStatus($id, 0);
        event('get.config');
    }
}
