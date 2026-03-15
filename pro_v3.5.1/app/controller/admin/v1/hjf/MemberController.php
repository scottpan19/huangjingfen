<?php
declare(strict_types=1);

namespace app\controller\admin\v1\hjf;

use app\controller\admin\AuthController;
use app\dao\user\UserDao;
use app\services\hjf\MemberLevelServices;
use app\services\system\SystemConfigServices;
use crmeb\services\SystemConfigService;
use think\annotation\Inject;

/**
 * Admin · 会员管理接口
 *
 * GET  /adminapi/hjf/member/list            — 会员列表（分页，支持按等级筛选）
 * PUT  /adminapi/hjf/member/level/:uid      — 手动调整会员等级
 * GET  /adminapi/hjf/member/config          — 获取会员等级配置
 * POST /adminapi/hjf/member/config          — 保存会员等级配置
 *
 * Class MemberController
 * @package app\controller\admin\v1\hjf
 */
class MemberController extends AuthController
{
    #[Inject]
    protected UserDao $userDao;

    #[Inject]
    protected MemberLevelServices $levelServices;

    /**
     * 会员列表（分页）
     */
    public function memberList(): mixed
    {
        $where = $this->request->getMore([
            ['keyword',      ''],
            ['member_level', ''],
            ['page',         1],
            ['limit',        20],
        ]);
        $page  = (int)$where['page'];
        $limit = (int)$where['limit'];

        $condition = [];
        if ($where['keyword'] !== '') {
            $condition['uid|nickname|phone'] = ['like', '%' . $where['keyword'] . '%'];
        }
        if ($where['member_level'] !== '') {
            $condition['member_level'] = (int)$where['member_level'];
        }

        $count = $this->userDao->count($condition);
        $list  = $this->userDao->selectList(
            $condition,
            'uid,nickname,avatar,phone,member_level,frozen_points,available_points,now_money,spread_uid,add_time',
            $page,
            $limit,
            'uid',
            'desc'
        );

        // 附加直推单数 & 伞下单数
        foreach ($list as &$item) {
            $item['direct_order_count']   = $this->levelServices->getDirectQueueOrderCount((int)$item['uid']);
            $item['umbrella_order_count'] = $this->levelServices->getUmbrellaQueueOrderCount((int)$item['uid']);
            $item['direct_spread_count']  = $this->levelServices->getDirectSpreadCount((int)$item['uid']);
        }
        unset($item);

        return $this->success(compact('list', 'count'));
    }

    /**
     * 手动调整会员等级
     *
     * @param int $uid
     */
    public function updateLevel(int $uid): mixed
    {
        $data = $this->request->getMore([
            ['member_level', 0],
        ]);
        $newLevel = (int)$data['member_level'];

        if ($newLevel < 0 || $newLevel > 4) {
            return $this->fail('等级范围 0-4');
        }

        $user = $this->userDao->get($uid);
        if (!$user) {
            return $this->fail('用户不存在');
        }

        $this->userDao->update($uid, ['member_level' => $newLevel], 'uid');

        return $this->success('更新成功');
    }

    /**
     * 获取会员等级配置
     */
    public function getConfig(): mixed
    {
        $keys = [
            'hjf_level_direct_require_1',
            'hjf_level_umbrella_require_2',
            'hjf_level_umbrella_require_3',
            'hjf_level_umbrella_require_4',
            'hjf_reward_direct_1',
            'hjf_reward_direct_2',
            'hjf_reward_direct_3',
            'hjf_reward_direct_4',
            'hjf_reward_umbrella_1',
            'hjf_reward_umbrella_2',
            'hjf_reward_umbrella_3',
            'hjf_reward_umbrella_4',
        ];

        $config = [];
        $defaults = [
            'hjf_level_direct_require_1'   => 3,
            'hjf_level_umbrella_require_2'  => 30,
            'hjf_level_umbrella_require_3'  => 100,
            'hjf_level_umbrella_require_4'  => 1000,
            'hjf_reward_direct_1'           => 500,
            'hjf_reward_direct_2'           => 800,
            'hjf_reward_direct_3'           => 1000,
            'hjf_reward_direct_4'           => 1300,
            'hjf_reward_umbrella_1'         => 0,
            'hjf_reward_umbrella_2'         => 300,
            'hjf_reward_umbrella_3'         => 200,
            'hjf_reward_umbrella_4'         => 300,
        ];

        foreach ($keys as $key) {
            $config[$key] = SystemConfigService::get($key, $defaults[$key] ?? 0);
        }

        return $this->success($config);
    }

    /**
     * 保存会员等级配置
     */
    public function saveConfig(SystemConfigServices $configServices): mixed
    {
        $allowedKeys = [
            'hjf_level_direct_require_1',
            'hjf_level_umbrella_require_2',
            'hjf_level_umbrella_require_3',
            'hjf_level_umbrella_require_4',
            'hjf_reward_direct_1',
            'hjf_reward_direct_2',
            'hjf_reward_direct_3',
            'hjf_reward_direct_4',
            'hjf_reward_umbrella_1',
            'hjf_reward_umbrella_2',
            'hjf_reward_umbrella_3',
            'hjf_reward_umbrella_4',
        ];

        $data = $this->request->post();
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedKeys, true)) {
                $configServices->setConfig($key, (string)$value);
            }
        }

        return $this->success('保存成功');
    }
}
