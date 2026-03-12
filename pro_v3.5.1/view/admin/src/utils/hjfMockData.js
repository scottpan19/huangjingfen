/**
 * 黄精粉健康商城 - Admin Mock 数据集中管理
 * Phase 1 前端开发使用，Phase 4 集成后可移除
 */

// ========== 公排管理 ==========

export const MOCK_QUEUE_ORDER_LIST = {
  list: [
    {
      id: 1,
      uid: 10086,
      nickname: '王五',
      phone: '138****8888',
      order_id: 'HJF202603100001',
      amount: 3600.00,
      queue_no: 142,
      status: 0,
      status_text: '排队中',
      refund_time: '',
      trigger_batch: 0,
      add_time: '2026-03-10 10:00:00'
    },
    {
      id: 2,
      uid: 10087,
      nickname: '张三',
      phone: '139****9999',
      order_id: 'HJF202603080002',
      amount: 3600.00,
      queue_no: 98,
      status: 1,
      status_text: '已退款',
      refund_time: '2026-03-09 12:00:00',
      trigger_batch: 24,
      add_time: '2026-03-08 09:30:00'
    },
    {
      id: 3,
      uid: 10088,
      nickname: '李四',
      phone: '137****7777',
      order_id: 'HJF202603090003',
      amount: 3600.00,
      queue_no: 120,
      status: 0,
      status_text: '排队中',
      refund_time: '',
      trigger_batch: 0,
      add_time: '2026-03-09 14:20:00'
    }
  ],
  count: 156,
  page: 1,
  limit: 20
};

export const MOCK_QUEUE_CONFIG = {
  trigger_multiple: 4,
  refund_cycle: 30,
  enabled: true,
  release_rate: 4,
  withdraw_fee_rate: 7
};

export const MOCK_QUEUE_FINANCE = {
  list: [
    {
      id: 1,
      uid: 10085,
      nickname: '赵六',
      phone: '136****6666',
      order_id: 'HJF202603090039',
      trigger_batch: 24,
      amount: 3600.00,
      queue_no: 39,
      refund_time: '2026-03-09 12:00:00',
      operator: '系统自动'
    },
    {
      id: 2,
      uid: 10082,
      nickname: '孙七',
      phone: '135****5555',
      order_id: 'HJF202603080035',
      trigger_batch: 23,
      amount: 3600.00,
      queue_no: 35,
      refund_time: '2026-03-08 16:30:00',
      operator: '管理员'
    }
  ],
  count: 39,
  total_refund: '140400.00',
  page: 1,
  limit: 20
};

// ========== 积分管理 ==========

export const MOCK_POINTS_RELEASE_LOG = {
  list: [
    {
      id: 1,
      uid: 10086,
      nickname: '王五',
      phone: '138****8888',
      points: 6,
      type: 'release',
      type_text: '释放',
      status: 1,
      status_text: '成功',
      add_time: '2026-03-10 00:01:23'
    },
    {
      id: 2,
      uid: 10087,
      nickname: '张三',
      phone: '139****9999',
      points: 3,
      type: 'release',
      type_text: '释放',
      status: 1,
      status_text: '成功',
      add_time: '2026-03-10 00:01:24'
    },
    {
      id: 3,
      uid: 10088,
      nickname: '李四',
      phone: '137****7777',
      points: 500,
      type: 'reward',
      type_text: '奖励',
      status: 1,
      status_text: '成功',
      add_time: '2026-03-09 15:30:00'
    },
    {
      id: 4,
      uid: 10086,
      nickname: '王五',
      phone: '138****8888',
      points: 200,
      type: 'consume',
      type_text: '消费',
      status: 1,
      status_text: '成功',
      add_time: '2026-03-09 11:20:00'
    },
    {
      id: 5,
      uid: 10087,
      nickname: '张三',
      phone: '139****9999',
      points: 50,
      type: 'consume',
      type_text: '消费',
      status: 0,
      status_text: '处理中',
      add_time: '2026-03-08 18:00:00'
    }
  ],
  count: 500,
  page: 1,
  limit: 20,
  statistics: {
    total_released_today: 2450,
    total_users_released: 320
  }
};

// ========== 会员管理 ==========

export const MOCK_MEMBER_LIST = {
  list: [
    {
      uid: 10086,
      nickname: '王五',
      phone: '138****8888',
      avatar: '',
      member_level: 2,
      member_level_name: '云店',
      no_assess: 0,
      direct_count: 8,
      umbrella_orders: 42,
      frozen_points: 15000,
      available_points: 3200,
      now_money: '7200.00',
      spread_uid: 10001,
      spread_nickname: '系统',
      team_performance: '18600.00',
      upgrade_status: 1,
      next_level: 3,
      next_level_name: '服务商',
      next_require: 100,
      add_time: '2026-01-15 10:00:00'
    },
    {
      uid: 10087,
      nickname: '张三',
      phone: '139****9999',
      avatar: '',
      member_level: 1,
      member_level_name: '创客',
      no_assess: 0,
      direct_count: 5,
      umbrella_orders: 18,
      frozen_points: 8500,
      available_points: 1200,
      now_money: '3600.00',
      spread_uid: 10086,
      spread_nickname: '王五',
      team_performance: '6480.00',
      upgrade_status: 0,
      next_level: 2,
      next_level_name: '云店',
      next_require: 50,
      add_time: '2026-02-10 14:30:00'
    },
    {
      uid: 10088,
      nickname: '李四',
      phone: '137****7777',
      avatar: '',
      member_level: 0,
      member_level_name: '普通会员',
      no_assess: 1,
      direct_count: 1,
      umbrella_orders: 2,
      frozen_points: 500,
      available_points: 0,
      now_money: '0.00',
      spread_uid: 10087,
      spread_nickname: '张三',
      team_performance: '720.00',
      upgrade_status: 0,
      next_level: 1,
      next_level_name: '创客',
      next_require: 10,
      add_time: '2026-03-01 09:20:00'
    }
  ],
  count: 256,
  page: 1,
  limit: 20
};

export const MOCK_MEMBER_CONFIG = {
  levels: [
    { level: 0, name: '普通会员', require_orders: 0,   direct_reward: 800, umbrella_reward_rate: 0,  enabled: true },
    { level: 1, name: '创客',    require_orders: 10,  direct_reward: 800, umbrella_reward_rate: 5,  enabled: true },
    { level: 2, name: '云店',    require_orders: 50,  direct_reward: 800, umbrella_reward_rate: 8,  enabled: true },
    { level: 3, name: '服务商',  require_orders: 100, direct_reward: 800, umbrella_reward_rate: 12, enabled: true },
    { level: 4, name: '分公司',  require_orders: 300, direct_reward: 800, umbrella_reward_rate: 15, enabled: true }
  ]
};

// ========== 财务管理 ==========

export const MOCK_FINANCE_BALANCE_LOG = {
  list: [
    {
      id: 1,
      uid: 10086,
      nickname: '王五',
      phone: '138****8888',
      pm: 1,
      type: 'queue_refund',
      type_text: '公排退款',
      number: '3600.00',
      balance: '7200.00',
      mark: '公排触发退款，批次24',
      add_time: '2026-03-09 12:00:00'
    },
    {
      id: 2,
      uid: 10086,
      nickname: '王五',
      phone: '138****8888',
      pm: 0,
      type: 'extract',
      type_text: '提现',
      number: '1000.00',
      balance: '3600.00',
      mark: '提现到微信零钱',
      add_time: '2026-03-08 15:30:00'
    }
  ],
  count: 45,
  page: 1,
  limit: 20
};

export const MOCK_FINANCE_WITHDRAW_LIST = {
  list: [
    {
      id: 1,
      uid: 10086,
      nickname: '王五',
      phone: '138****8888',
      extract_price: '1000.00',
      fee_price: '70.00',
      real_price: '930.00',
      status: 1,
      status_text: '已通过',
      extract_type: 'wx',
      extract_type_text: '微信零钱',
      add_time: '2026-03-08 15:30:00',
      verify_time: '2026-03-08 16:00:00'
    },
    {
      id: 2,
      uid: 10087,
      nickname: '张三',
      phone: '139****9999',
      extract_price: '500.00',
      fee_price: '35.00',
      real_price: '465.00',
      status: 0,
      status_text: '待审核',
      extract_type: 'alipay',
      extract_type_text: '支付宝',
      add_time: '2026-03-10 09:15:00',
      verify_time: ''
    }
  ],
  count: 28,
  page: 1,
  limit: 20,
  statistics: {
    total_apply_today: 12,
    total_amount_today: '8500.00'
  }
};
