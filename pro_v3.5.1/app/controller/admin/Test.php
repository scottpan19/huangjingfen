<?php

namespace app\controller\admin;

use app\jobs\order\OrderPayTypeJob;
use app\jobs\user\UserBirthdayFormatJob;
use app\listener\order\Pay;
use app\listener\product\Stock;
use app\services\activity\holiday\HolidayGiftPushServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderServices;
use crmeb\services\CacheService;
use crmeb\services\wechat\SwooleResponse;

class Test
{
    public function index()
    {
//        try {
//            \crmeb\basic\__deoZdLXkdyv3S2MEnVxvEGEZQlM();
//            $res = true;
//        } catch (\Throwable $e) {
//            $res = false;
//        }
//        return app('json')->success(['res' => $res]);
    }
}
