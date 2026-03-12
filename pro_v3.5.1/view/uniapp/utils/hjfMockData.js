/**
 * 黄精粉健康商城 - UniApp Mock 数据集中管理
 * Phase 1 前端开发使用，Phase 4 集成后可移除
 */

// ========== 场景切换系统 ==========

/**
 * 当前演示场景
 * 'A' - 新用户（首次体验）
 * 'B' - 活跃用户（等待退款中）- 默认
 * 'C' - VIP用户（退款刚触发）
 */
let MOCK_SCENARIO = 'B';

/**
 * 切换场景并触发页面刷新
 * @param {string} scenario - 'A' | 'B' | 'C'
 */
export function setMockScenario(scenario) {
  if (['A', 'B', 'C'].includes(scenario)) {
    MOCK_SCENARIO = scenario;
    console.log(`[HJF Mock] 已切换到场景 ${scenario}`);
    // 触发全局事件，通知页面刷新
    uni.$emit('hjf-scenario-changed', scenario);
    return true;
  }
  return false;
}

/**
 * 获取当前场景
 */
export function getCurrentScenario() {
  return MOCK_SCENARIO;
}

// ========== 公排模块 ==========

export const MOCK_QUEUE_STATUS = {
  totalOrders: 156,
  myOrders: [
    {
      id: 1,
      order_id: 'HJF202603100001',
      amount: 3600.00,
      queue_no: 142,
      status: 0,
      refund_time: 0,
      trigger_batch: 0,
      add_time: 1741593600,
      position: 14,
      estimated_wait: '约3天'
    },
    {
      id: 2,
      order_id: 'HJF202603080002',
      amount: 3600.00,
      queue_no: 98,
      status: 1,
      refund_time: 1741507200,
      trigger_batch: 24,
      add_time: 1741420800,
      position: 0,
      estimated_wait: '已退款'
    },
    {
      id: 3,
      order_id: 'HJF202603070003',
      amount: 3600.00,
      queue_no: 85,
      status: 0,
      refund_time: 0,
      trigger_batch: 0,
      add_time: 1741334400,
      position: 46,
      estimated_wait: '约12天'
    }
  ],
  progress: {
    current_batch_count: 2,
    trigger_multiple: 4,
    next_refund_queue_no: 39
  }
};

export const MOCK_QUEUE_HISTORY = {
  list: [
    {
      id: 1,
      order_id: 'HJF202603050001',
      amount: 3600.00,
      queue_no: 45,
      status: 1,
      refund_time: 1741334400,
      trigger_batch: 11,
      add_time: 1741161600,
      time_key: '2026-03-07'
    },
    {
      id: 2,
      order_id: 'HJF202603060002',
      amount: 3600.00,
      queue_no: 67,
      status: 0,
      refund_time: 0,
      trigger_batch: 0,
      add_time: 1741248000,
      time_key: '2026-03-06'
    },
    {
      id: 3,
      order_id: 'HJF202603040003',
      amount: 3600.00,
      queue_no: 33,
      status: 1,
      refund_time: 1741248000,
      trigger_batch: 8,
      add_time: 1741075200,
      time_key: '2026-03-04'
    }
  ],
  count: 25,
  page: 1,
  limit: 15
};

// ========== 资产模块 ==========

export const MOCK_ASSETS_OVERVIEW = {
  now_money: '7200.00',
  frozen_points: 15000,
  available_points: 3200,
  today_release: 6,
  total_queue_refund: '14400.00',
  total_points_earned: 18200,
  member_level: 2,
  member_level_name: '云店'
};

export const MOCK_POINTS_DETAIL = {
  list: [
    {
      id: 1,
      title: '直推奖励 - 用户张三购买报单商品',
      type: 'reward_direct',
      points: 800,
      pm: 1,
      status: 'frozen',
      add_time: '2026-03-10 14:30',
      order_id: 'HJF202603100005'
    },
    {
      id: 2,
      title: '每日释放 - 待释放积分自动解冻',
      type: 'release',
      points: 6,
      pm: 1,
      status: 'released',
      add_time: '2026-03-10 00:00',
      release_date: '2026-03-10'
    },
    {
      id: 3,
      title: '积分消费 - 购买普通商品',
      type: 'consume',
      points: 200,
      pm: 0,
      status: 'released',
      add_time: '2026-03-09 16:22',
      order_id: 'HJF202603090012'
    },
    {
      id: 4,
      title: '伞下奖励 - 用户李四购买报单商品',
      type: 'reward_umbrella',
      points: 300,
      pm: 1,
      status: 'frozen',
      add_time: '2026-03-09 10:15',
      order_id: 'HJF202603090003'
    },
    {
      id: 5,
      title: '每日释放 - 待释放积分自动解冻',
      type: 'release',
      points: 6,
      pm: 1,
      status: 'released',
      add_time: '2026-03-09 00:00',
      release_date: '2026-03-09'
    }
  ],
  count: 45,
  page: 1,
  limit: 15
};

export const MOCK_CASH_DETAIL = {
  list: [
    {
      id: 1,
      title: '公排退款 - 订单HJF202603050001',
      amount: '3600.00',
      pm: 1,
      add_time: '2026-03-07 12:00',
      order_id: 'HJF202603050001'
    },
    {
      id: 2,
      title: '提现 - 微信零钱',
      amount: '930.00',
      pm: 0,
      add_time: '2026-03-06 15:30',
      remark: '手续费¥70.00'
    },
    {
      id: 3,
      title: '购物消费',
      amount: '299.00',
      pm: 0,
      add_time: '2026-03-05 09:20',
      order_id: 'HJF202603050010'
    }
  ],
  count: 12,
  page: 1,
  limit: 15
};

export const MOCK_WITHDRAW_INFO = {
  now_money: '7200.00',
  min_extract: 100,
  fee_rate: 7,
  extract_bank: ['微信零钱', '支付宝', '银行卡'],
  bank_list: [
    { bank_name: '中国工商银行', bank_code: '1234****5678' }
  ]
};

// ========== 会员模块 ==========

export const MOCK_MEMBER_INFO = {
  member_level: 2,
  member_level_name: '云店',
  direct_count: 8,
  umbrella_count: 35,
  umbrella_orders: 42,
  next_level_name: '服务商',
  next_level_require: 100,
  progress_percent: 42
};

export const MOCK_TEAM_DATA = {
  direct_count: 8,
  umbrella_count: 35,
  umbrella_orders: 42,
  members: [
    {
      uid: 10087,
      nickname: '张三',
      avatar: '/static/images/default_avatar.png',
      member_level: 1,
      member_level_name: '创客',
      join_time: '2026-02-15',
      direct_orders: 5,
      is_direct: true
    },
    {
      uid: 10088,
      nickname: '李四',
      avatar: '/static/images/default_avatar.png',
      member_level: 0,
      member_level_name: '普通会员',
      join_time: '2026-03-01',
      direct_orders: 1,
      is_direct: false,
      parent_nickname: '张三'
    },
    {
      uid: 10089,
      nickname: '王五',
      avatar: '/static/images/default_avatar.png',
      member_level: 2,
      member_level_name: '云店',
      join_time: '2026-01-20',
      direct_orders: 12,
      is_direct: true
    }
  ],
  page: 1,
  count: 35
};

export const MOCK_TEAM_INCOME = {
  list: [
    {
      id: 1,
      title: '直推奖励',
      from_uid: 10087,
      from_nickname: '张三',
      order_id: 'HJF202603100005',
      points: 800,
      type: 'direct',
      add_time: '2026-03-10 14:30'
    },
    {
      id: 2,
      title: '伞下奖励(级差)',
      from_uid: 10088,
      from_nickname: '李四',
      order_id: 'HJF202603090003',
      points: 300,
      type: 'umbrella',
      add_time: '2026-03-09 10:15'
    },
    {
      id: 3,
      title: '直推奖励',
      from_uid: 10089,
      from_nickname: '王五',
      order_id: 'HJF202603080010',
      points: 800,
      type: 'direct',
      add_time: '2026-03-08 16:45'
    }
  ],
  count: 22,
  page: 1,
  limit: 15
};

// ========== 引导模块 ==========

export const MOCK_GUIDE_DATA = {
  slides: [
    {
      title: '欢迎来到黄精粉健康商城',
      desc: '健康好物，品质生活',
      image: '/static/images/guide/slide1.png'
    },
    {
      title: '公排返利机制',
      desc: '购买报单商品自动进入公排，每进4单退1单全额返还',
      image: '/static/images/guide/slide2.png'
    },
    {
      title: '会员积分体系',
      desc: '推荐好友即获积分奖励，积分每日自动释放',
      image: '/static/images/guide/slide3.png'
    }
  ]
};

// ========== 场景数据集合 ==========

/**
 * 场景 A - 新用户（首次体验）
 */
const SCENARIO_A_DATA = {
  queueStatus: {
    totalOrders: 12,
    myOrders: [],
    progress: {
      current_batch_count: 0,
      trigger_multiple: 4,
      next_refund_queue_no: 4
    }
  },
  queueHistory: {
    list: [],
    count: 0,
    page: 1,
    limit: 15
  },
  assetsOverview: {
    now_money: '0.00',
    frozen_points: 0,
    available_points: 0,
    today_release: 0,
    total_queue_refund: '0.00',
    total_points_earned: 0,
    member_level: 0,
    member_level_name: '普通会员'
  },
  pointsDetail: {
    list: [],
    count: 0,
    page: 1,
    limit: 15
  },
  cashDetail: {
    list: [],
    count: 0,
    page: 1,
    limit: 15
  },
  withdrawInfo: {
    now_money: '0.00',
    min_extract: 100,
    fee_rate: 7,
    extract_bank: ['微信零钱', '支付宝', '银行卡'],
    bank_list: []
  },
  memberInfo: {
    member_level: 0,
    member_level_name: '普通会员',
    direct_count: 0,
    umbrella_count: 0,
    umbrella_orders: 0,
    next_level_name: '创客',
    next_level_require: 3,
    progress_percent: 0
  },
  teamData: {
    direct_count: 0,
    umbrella_count: 0,
    umbrella_orders: 0,
    members: [],
    page: 1,
    count: 0
  },
  teamIncome: {
    list: [],
    count: 0,
    page: 1,
    limit: 15
  }
};

/**
 * 场景 B - 活跃用户（等待退款中）- 使用原有数据
 */
const SCENARIO_B_DATA = {
  queueStatus: MOCK_QUEUE_STATUS,
  queueHistory: MOCK_QUEUE_HISTORY,
  assetsOverview: MOCK_ASSETS_OVERVIEW,
  pointsDetail: MOCK_POINTS_DETAIL,
  cashDetail: MOCK_CASH_DETAIL,
  withdrawInfo: MOCK_WITHDRAW_INFO,
  memberInfo: MOCK_MEMBER_INFO,
  teamData: MOCK_TEAM_DATA,
  teamIncome: MOCK_TEAM_INCOME
};

/**
 * 场景 C - VIP 用户（退款刚触发）
 */
const SCENARIO_C_DATA = {
  queueStatus: {
    totalOrders: 289,
    myOrders: [
      {
        id: 10,
        order_id: 'HJF202603110001',
        amount: 3600.00,
        queue_no: 285,
        status: 1,
        refund_time: Date.now() / 1000 - 120,
        trigger_batch: 71,
        add_time: Date.now() / 1000 - 86400 * 5,
        position: 0,
        estimated_wait: '已退款'
      },
      {
        id: 9,
        order_id: 'HJF202603090010',
        amount: 3600.00,
        queue_no: 268,
        status: 0,
        refund_time: 0,
        trigger_batch: 0,
        add_time: Date.now() / 1000 - 86400 * 2,
        position: 8,
        estimated_wait: '约2天'
      },
      {
        id: 8,
        order_id: 'HJF202603080008',
        amount: 3600.00,
        queue_no: 244,
        status: 1,
        refund_time: Date.now() / 1000 - 86400 * 3,
        trigger_batch: 61,
        add_time: Date.now() / 1000 - 86400 * 8,
        position: 0,
        estimated_wait: '已退款'
      },
      {
        id: 7,
        order_id: 'HJF202603050007',
        amount: 3600.00,
        queue_no: 196,
        status: 1,
        refund_time: Date.now() / 1000 - 86400 * 10,
        trigger_batch: 49,
        add_time: Date.now() / 1000 - 86400 * 15,
        position: 0,
        estimated_wait: '已退款'
      }
    ],
    progress: {
      current_batch_count: 1,
      trigger_multiple: 4,
      next_refund_queue_no: 72
    }
  },
  queueHistory: {
    list: [
      {
        id: 10,
        order_id: 'HJF202603110001',
        amount: 3600.00,
        queue_no: 285,
        status: 1,
        refund_time: Date.now() / 1000 - 120,
        trigger_batch: 71,
        add_time: Date.now() / 1000 - 86400 * 5,
        time_key: '2026-03-11'
      },
      {
        id: 9,
        order_id: 'HJF202603090010',
        amount: 3600.00,
        queue_no: 268,
        status: 0,
        refund_time: 0,
        trigger_batch: 0,
        add_time: Date.now() / 1000 - 86400 * 2,
        time_key: '2026-03-09'
      },
      {
        id: 8,
        order_id: 'HJF202603080008',
        amount: 3600.00,
        queue_no: 244,
        status: 1,
        refund_time: Date.now() / 1000 - 86400 * 3,
        trigger_batch: 61,
        add_time: Date.now() / 1000 - 86400 * 8,
        time_key: '2026-03-08'
      }
    ],
    count: 4,
    page: 1,
    limit: 15
  },
  assetsOverview: {
    now_money: '25200.00',
    frozen_points: 38500,
    available_points: 12600,
    today_release: 15,
    total_queue_refund: '50400.00',
    total_points_earned: 51100,
    member_level: 3,
    member_level_name: '服务商'
  },
  pointsDetail: {
    list: [
      {
        id: 50,
        title: '直推奖励 - 用户刘五购买报单商品',
        type: 'reward_direct',
        points: 1000,
        pm: 1,
        status: 'frozen',
        add_time: '2026-03-11 10:20',
        order_id: 'HJF202603110025'
      },
      {
        id: 49,
        title: '伞下奖励 - 用户赵六购买报单商品',
        type: 'reward_umbrella',
        points: 200,
        pm: 1,
        status: 'frozen',
        add_time: '2026-03-11 08:15',
        order_id: 'HJF202603110018'
      },
      {
        id: 48,
        title: '每日释放 - 待释放积分自动解冻',
        type: 'release',
        points: 15,
        pm: 1,
        status: 'released',
        add_time: '2026-03-11 00:00',
        release_date: '2026-03-11'
      },
      {
        id: 47,
        title: '直推奖励 - 用户孙七购买报单商品',
        type: 'reward_direct',
        points: 1000,
        pm: 1,
        status: 'frozen',
        add_time: '2026-03-10 16:30',
        order_id: 'HJF202603100045'
      }
    ],
    count: 156,
    page: 1,
    limit: 15
  },
  cashDetail: {
    list: [
      {
        id: 15,
        title: '公排退款 - 订单HJF202603110001',
        amount: '3600.00',
        pm: 1,
        add_time: '2026-03-11 10:00',
        order_id: 'HJF202603110001'
      },
      {
        id: 14,
        title: '公排退款 - 订单HJF202603080008',
        amount: '3600.00',
        pm: 1,
        add_time: '2026-03-08 14:00',
        order_id: 'HJF202603080008'
      },
      {
        id: 13,
        title: '提现 - 微信零钱',
        amount: '9300.00',
        pm: 0,
        add_time: '2026-03-07 15:30',
        remark: '手续费¥700.00'
      },
      {
        id: 12,
        title: '公排退款 - 订单HJF202603050007',
        amount: '3600.00',
        pm: 1,
        add_time: '2026-03-05 12:00',
        order_id: 'HJF202603050007'
      }
    ],
    count: 28,
    page: 1,
    limit: 15
  },
  withdrawInfo: {
    now_money: '25200.00',
    min_extract: 100,
    fee_rate: 7,
    extract_bank: ['微信零钱', '支付宝', '银行卡'],
    bank_list: [
      { bank_name: '中国工商银行', bank_code: '1234****5678' }
    ]
  },
  memberInfo: {
    member_level: 3,
    member_level_name: '服务商',
    direct_count: 15,
    umbrella_count: 80,
    umbrella_orders: 125,
    next_level_name: '分公司',
    next_level_require: 1000,
    progress_percent: 12.5
  },
  teamData: {
    direct_count: 15,
    umbrella_count: 80,
    umbrella_orders: 125,
    members: [
      {
        uid: 10091,
        nickname: '刘五',
        avatar: '/static/images/default_avatar.png',
        member_level: 2,
        member_level_name: '云店',
        join_time: '2026-02-01',
        direct_orders: 35,
        is_direct: true
      },
      {
        uid: 10092,
        nickname: '赵六',
        avatar: '/static/images/default_avatar.png',
        member_level: 2,
        member_level_name: '云店',
        join_time: '2026-02-10',
        direct_orders: 28,
        is_direct: true
      },
      {
        uid: 10093,
        nickname: '孙七',
        avatar: '/static/images/default_avatar.png',
        member_level: 1,
        member_level_name: '创客',
        join_time: '2026-02-20',
        direct_orders: 12,
        is_direct: true
      },
      {
        uid: 10094,
        nickname: '周八',
        avatar: '/static/images/default_avatar.png',
        member_level: 0,
        member_level_name: '普通会员',
        join_time: '2026-03-05',
        direct_orders: 2,
        is_direct: false,
        parent_nickname: '刘五'
      }
    ],
    page: 1,
    count: 80
  },
  teamIncome: {
    list: [
      {
        id: 50,
        title: '直推奖励',
        from_uid: 10091,
        from_nickname: '刘五',
        order_id: 'HJF202603110025',
        points: 1000,
        type: 'direct',
        add_time: '2026-03-11 10:20'
      },
      {
        id: 49,
        title: '伞下奖励(级差)',
        from_uid: 10094,
        from_nickname: '周八',
        order_id: 'HJF202603110018',
        points: 200,
        type: 'umbrella',
        add_time: '2026-03-11 08:15'
      },
      {
        id: 48,
        title: '直推奖励',
        from_uid: 10092,
        from_nickname: '赵六',
        order_id: 'HJF202603100045',
        points: 1000,
        type: 'direct',
        add_time: '2026-03-10 16:30'
      },
      {
        id: 47,
        title: '伞下奖励(级差)',
        from_uid: 10095,
        from_nickname: '吴九',
        order_id: 'HJF202603100032',
        points: 200,
        type: 'umbrella',
        add_time: '2026-03-10 11:45'
      }
    ],
    count: 85,
    page: 1,
    limit: 15
  }
};

/**
 * 场景数据映射
 */
const MOCK_SCENARIO_DATA = {
  A: SCENARIO_A_DATA,
  B: SCENARIO_B_DATA,
  C: SCENARIO_C_DATA
};

/**
 * 场景感知的 Mock 数据获取函数
 */
export function getMockQueueStatus() {
  return JSON.parse(JSON.stringify(MOCK_SCENARIO_DATA[MOCK_SCENARIO].queueStatus));
}

export function getMockQueueHistory() {
  return JSON.parse(JSON.stringify(MOCK_SCENARIO_DATA[MOCK_SCENARIO].queueHistory));
}

export function getMockAssetsOverview() {
  return JSON.parse(JSON.stringify(MOCK_SCENARIO_DATA[MOCK_SCENARIO].assetsOverview));
}

export function getMockPointsDetail() {
  return JSON.parse(JSON.stringify(MOCK_SCENARIO_DATA[MOCK_SCENARIO].pointsDetail));
}

export function getMockCashDetail() {
  return JSON.parse(JSON.stringify(MOCK_SCENARIO_DATA[MOCK_SCENARIO].cashDetail));
}

export function getMockWithdrawInfo() {
  return JSON.parse(JSON.stringify(MOCK_SCENARIO_DATA[MOCK_SCENARIO].withdrawInfo));
}

export function getMockMemberInfo() {
  return JSON.parse(JSON.stringify(MOCK_SCENARIO_DATA[MOCK_SCENARIO].memberInfo));
}

export function getMockTeamData() {
  return JSON.parse(JSON.stringify(MOCK_SCENARIO_DATA[MOCK_SCENARIO].teamData));
}

export function getMockTeamIncome() {
  return JSON.parse(JSON.stringify(MOCK_SCENARIO_DATA[MOCK_SCENARIO].teamIncome));
}

