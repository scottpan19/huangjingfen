// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2021 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
import BasicLayout from '@/layouts/basic-layout';

const pre = 'hjf_';

export default {
    path: '/admin/hjf',
    name: 'hjf',
    header: 'hjf',
    meta: {
        auth: ['admin-hjf']
    },
    component: BasicLayout,
    children: [
        {
            path: 'queue/order',
            name: `${pre}queueOrder`,
            meta: {
                auth: ['hjf-queue-order'],
                title: '公排订单管理'
            },
            component: () => import('@/pages/hjf/queueOrder/index')
        },
        {
            path: 'queue/finance',
            name: `${pre}queueFinance`,
            meta: {
                auth: ['hjf-queue-finance'],
                title: '公排财务流水'
            },
            component: () => import('@/pages/hjf/queueFinance/index')
        },
        {
            path: 'queue/config',
            name: `${pre}queueConfig`,
            meta: {
                auth: ['hjf-queue-config'],
                title: '公排配置'
            },
            component: () => import('@/pages/hjf/queueConfig/index')
        },
        {
            path: 'member/level',
            name: `${pre}memberLevel`,
            meta: {
                auth: ['hjf-member-level'],
                title: '会员等级管理'
            },
            component: () => import('@/pages/hjf/memberLevel/index')
        },
        {
            path: 'member/config',
            name: `${pre}memberConfig`,
            meta: {
                auth: ['hjf-member-config'],
                title: '会员配置'
            },
            component: () => import('@/pages/hjf/memberConfig/index')
        },
        {
            path: 'points/log',
            name: `${pre}pointsLog`,
            meta: {
                auth: ['hjf-points-log'],
                title: '积分日志'
            },
            component: () => import('@/pages/hjf/pointsLog/index')
        }
    ]
};
