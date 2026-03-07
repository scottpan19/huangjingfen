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

const pre = 'Inventory_';

export default {
  path: '/admin/inventory',
  name: 'inventory',
  header: 'inventory',
  component: BasicLayout,
  children: [
    {
      path: 'godownEntry',
      name: `${pre}GodownEntry`,
      meta: {
        auth: ['admin-inventory-godownEntry'],
        title: '入库管理'
      },
      component: () => import('@/pages/inventory/godownEntry/index.vue')
    },
    {
      path: 'godownEntry/detail/:type?',
      name: `${pre}GodownEntryDetail`,
      meta: {
        auth: ['admin-inventory-godownEntry-detail'],
        title: '入库添加'
      },
      component: () => import('@/pages/inventory/godownEntry/detail.vue')
    },
    {
      path: 'placingEntry',
      name: `${pre}PlacingEntry`,
      meta: {
        auth: ['admin-inventory-placingEntry'],
        title: '出库管理'
      },
      component: () => import('@/pages/inventory/godownEntry/index.vue')
    },
    {
      path: 'placingEntry/detail/:type?',
      name: `${pre}PlacingEntryDetail`,
      meta: {
        auth: ['admin-inventory-placingEntry-detail'],
        title: '出库添加'
      },
      component: () => import('@/pages/inventory/godownEntry/detail.vue')
    },
    {
      path: 'storeStock',
      name: `${pre}StoreStock`,
      meta: {
        auth: ['admin-inventory-storeStock'],
        title: '出入库明细'
      },
      component: () => import('@/pages/inventory/storeStock/index.vue')
    },
    {
      path: 'storeStock/detail',
      name: `${pre}StoreStockDetail`,
      meta: {
        auth: ['admin-inventory-storeStockDetail'],
        title: '出入库明细'
      },
      component: () => import('@/pages/inventory/storeStock/detail.vue')
    },
    {
      path: 'stockChecks',
      name: `${pre}StockChecks`,
      meta: {
        auth: ['admin-inventory-stockChecks'],
        title: '库存盘点'
      },
      component: () => import('@/pages/inventory/stockChecks/index.vue')
    },
    {
      path: 'stockChecks/detail/:id?',
      name: `${pre}StockChecksDetail`,
      meta: {
        auth: ['admin-inventory-stockChecks-detail'],
        title: '库存盘点详情'
      },
      component: () => import('@/pages/inventory/stockChecks/detail.vue')
    },
    {
      path: 'shipment',
      name: `${pre}Shipment`,
      meta:{
        auth: ['admin-inventory-shipment'],
        title: '出入库统计'
      },
      component: () => import('@/pages/inventory/shipment/index.vue')
    }
  ]

}