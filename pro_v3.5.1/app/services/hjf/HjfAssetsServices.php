<?php
declare(strict_types=1);

namespace app\services\hjf;

use app\dao\user\UserBillDao;
use app\dao\user\UserDao;
use app\services\BaseServices;
use think\annotation\Inject;

/**
 * HJF 资产服务
 *
 * 提供用户资产总览和现金流水查询功能。
 * 复用 CRMEB 原有 eb_user（余额/积分）和 eb_user_bill（流水）字段。
 *
 * Class HjfAssetsServices
 * @package app\services\hjf
 */
class HjfAssetsServices extends BaseServices
{
    #[Inject]
    protected UserDao $userDao;

    #[Inject]
    protected UserBillDao $billDao;

    /**
     * 获取用户资产总览
     *
     * 返回：
     *  - now_money          现金余额
     *  - frozen_points      待释放积分
     *  - available_points   已释放积分（可消费）
     *  - total_points       总积分（frozen + available）
     *
     * @param int $uid
     * @return array
     */
    public function getOverview(int $uid): array
    {
        $user = $this->userDao->get($uid, 'uid,now_money,frozen_points,available_points');
        if (!$user) {
            return [
                'now_money'        => '0.00',
                'frozen_points'    => 0,
                'available_points' => 0,
                'total_points'     => 0,
            ];
        }

        $frozen    = (int)($user['frozen_points']    ?? 0);
        $available = (int)($user['available_points'] ?? 0);

        return [
            'now_money'        => number_format((float)($user['now_money'] ?? 0), 2, '.', ''),
            'frozen_points'    => $frozen,
            'available_points' => $available,
            'total_points'     => $frozen + $available,
        ];
    }

    /**
     * 获取现金流水（分页）
     *
     * 复用 eb_user_bill 表，筛选 category='now_money' 的记录。
     *
     * @param int    $uid
     * @param string $type   流水类型筛选（'' = 全部，'queue_refund' 公排退款，'withdraw' 提现等）
     * @param int    $page
     * @param int    $limit
     * @return array
     */
    public function getCashDetail(int $uid, string $type, int $page, int $limit): array
    {
        $where = [
            'uid'      => $uid,
            'category' => 'now_money',
        ];
        if ($type !== '') {
            $where['type'] = $type;
        }

        $count = $this->billDao->count($where);
        $list  = $this->billDao->getBalanceRecord($where, $page, $limit);

        return compact('list', 'count');
    }
}
