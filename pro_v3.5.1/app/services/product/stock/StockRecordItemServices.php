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

namespace app\services\product\stock;

use app\dao\product\stock\StockRecordItemDao;
use app\services\BaseServices;
use think\annotation\Inject;

/**
 * 库存管理服务类
 * Class StockRecordServices
 * @package app\services\stock
 * @mixin StockRecordItemDao
 */
class StockRecordItemServices extends BaseServices
{
    /**
     * @var StockRecordItemDao
     */
    #[Inject]
    protected StockRecordItemDao $dao;

}
