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

namespace app\jobs\order;

use app\services\order\StoreOrderServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 订单支付类型处理队列
 * Class OrderPayTypeJob
 * @package app\jobs\order
 */
class OrderPayTypeJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 直接执行订单支付类型处理
     * 不需要传参数，直接处理所有需要处理的订单
     * @return bool
     */
    public function doJob()
    {
        try {

            /** @var StoreOrderServices $storeOrderServices */
            $storeOrderServices = app()->make(StoreOrderServices::class);

            Log::info('开始执行订单支付类型处理任务');

            // 查询所有已支付的订单
            $where = [
                'paid' => 1,
                'is_del' => 0,
                'is_system_del' => 0
            ];

            // 分批处理，避免内存溢出
            $limit = 100;
            $page = 1;
            $totalProcessed = 0;
            $successCount = 0;
            $failCount = 0;

            do {
                Log::info('开始执行订单支付类型处理任务111');
                $orders = $storeOrderServices->getList($where, ['id', 'pay_type', 'pay_price', 'yue_price', 'online_price'], $page, $limit);

                if (empty($orders)) {
                    break;
                }

                foreach ($orders as $order) {
                    if ($this->processOrder($order)) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                    $totalProcessed++;
                }

                $page++;
                Log::info("已处理第 {$page} 页，当前批次处理 " . count($orders) . " 条订单");

            } while (count($orders) === $limit);

            Log::info("订单支付类型处理任务完成，共处理：{$totalProcessed}条订单，成功：{$successCount}，失败：{$failCount}");
            return $failCount === 0;

        } catch (\Exception $e) {
            Log::error('订单支付类型处理任务异常：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 处理单个订单
     * @param array $order 订单数据
     * @return bool
     */
    private function processOrder($order)
    {
        try {
            /** @var StoreOrderServices $storeOrderServices */
            $storeOrderServices = app()->make(StoreOrderServices::class);

            $orderId = $order['id'];
            $payType = $order['pay_type'] ?? '';
            $payPrice = $order['pay_price'] ?? 0;
            $currentYuePrice = $order['yue_price'] ?? 0;
            $currentOnlinePrice = $order['online_price'] ?? 0;

            // 准备更新数据
            $updateData = [];
            $needUpdate = false;

            // 根据支付类型设置相应字段
            if ($payType === 'yue') {
                // 余额支付：yue_price = pay_price, online_price = 0
                if ($currentYuePrice != $payPrice || $currentOnlinePrice != 0) {
                    $updateData['yue_price'] = $payPrice;
                    $updateData['online_price'] = 0;
                    $needUpdate = true;
                    Log::info("订单ID：{$orderId}，余额支付，需要更新 yue_price：{$payPrice}，online_price：0");
                }
            } else {
                // 其他支付方式：online_price = pay_price, yue_price = 0
                if ($currentOnlinePrice != $payPrice || $currentYuePrice != 0) {
                    $updateData['online_price'] = $payPrice;
                    $updateData['yue_price'] = 0;
                    $needUpdate = true;
                    Log::info("订单ID：{$orderId}，线上支付({$payType})，需要更新 online_price：{$payPrice}，yue_price：0");
                }
            }

            // 如果需要更新则执行更新
            if ($needUpdate) {
                $result = $storeOrderServices->update($orderId, $updateData);

                if ($result) {
                    Log::info("订单支付类型处理成功，订单ID：{$orderId}，支付类型：{$payType}");
                    return true;
                } else {
                    Log::error("订单支付类型处理失败，订单ID：{$orderId}");
                    return false;
                }
            } else {
                // 数据已经正确，无需更新
                Log::debug("订单ID：{$orderId} 数据已正确，无需更新");
                return true;
            }

        } catch (\Exception $e) {
            Log::error("处理订单ID：{$order['id']} 时发生异常：" . $e->getMessage());
            return false;
        }
    }
}
