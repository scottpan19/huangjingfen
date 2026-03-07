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

namespace app\listener\supplier;


use crmeb\interfaces\ListenerInterface;

/**
 * 供应商创建成功事件
 * Class SupplierSuccess
 * @package app\listener\supplier
 */
class SupplierSuccess implements ListenerInterface
{
    /**
     * 处理供应商创建成功事件
     * @param mixed $event 事件数据
     * @return void
     */
    public function handle($event): void
    {

    }


}

