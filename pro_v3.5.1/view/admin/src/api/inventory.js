// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2021 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
import request from '@/plugins/request';

/**
 * 获取出入库记录列表
 * @param {*} params 
 * @returns 
 */
export function getInventoryRecordList(params) {
  return request({
    url: '/stock/record/index',
    method: 'get',
    params
  });
}

/**
 * @description 创建出入库记录
 * @param {*} data 
 * @returns
 */
export function createInventoryRecord(data) {
  return request({
    url: '/stock/record/save',
    method: 'post',
    data
  });
}

export function getInventoryStockRefundList(params) {
  return request({
    url: '/stock/record/refund_list',
    method: 'get',
    params
  });
}

//获取出入库记录详情
export function getInventoryRecordDetail(params) {
  return request({
    url: `/stock/record/read`,
    method: 'get',
    params
  });
}

//获取出入库记录备注
export function getInventoryRecordRemark(id) {
  return request({
    url: `/stock/record/remark/${id}`,
    method: 'get'
  });
}

/**
 * @description 出入库明细
 */
export function getInventoryDetailList(params) {
  return request({
    url: '/stock/product/list',
    method: 'get',
    params
  });
}

export function saveInventoryApi(data) {
  return request({
    url: '/stock/inventory/save',
    method: 'post',
    data
  });
}

//Route::get('inventory/index', 'v1.product.stock.StockInventoryController/index')->option(['real_name' => '获取出库存盘点列表']);
export function getInventoryList(params) {
  return request({
    url: '/stock/inventory/index',
    method: 'get',
    params
  });
}

//获取出入库记录备注
export function inventorySetMarkApi(id) {
  return request({
    url: `/stock/inventory/remark/${id}`,
    method: 'get'
  });
}

//Route::get('inventory/read', 'v1.product.stock.StockInventoryController/read')->option(['real_name' => '获取出库存盘点记录详情']);
export function inventoryReadApi(id) {
  return request({
    url: `/stock/inventory/read/${id}`,
    method: 'get'
  });
}

export function editInventoryApi(id, data) {
  return request({
    url: '/stock/inventory/update/' + id,
    method: 'post',
    data
  });
}

export function getInventoryStatisticsApi(params) { 
  return request({
    url: '/stock/statistics',
    method: 'get',
    params
  });
}

/**
 * @description 导出库存记录
 * @param {*} data 
 * @returns 
 */
export function exportStockApi(data) {
  return request({
    url: '/export/stockExport',
    method: 'get',
    params: data
  });
}

/**
 * @description 导出出库存盘点记录
 * @param {*} data 
 * @returns 
 */
export function exportInventoryApi(data) {
  return request({
    url: '/export/inventoryExport',
    method: 'get',
    params: data
  });
}

/**
 * @description 导出库存详情
 * @param {*} data 
 * @returns 
 */
export function exportStockDetailsApi(data) {
  return request({
    url: '/export/stockDetailsExport',
    method: 'get',
    params: data
  });
}

/**
 * @description 获取库存总体统计
 * @param {*} params 
 * @returns 
 */
export function getInventoryOverallStatisticsApi(params) {
  return request({
    url: '/stock/overall_statistics',
    method: 'get',
    params
  });
}

/**
 * @description 获取库存统计
 * @param {*} params 
 * @returns 
 */
export function getInventoryProductStatisticsApi(params) {
  return request({
    url: '/stock/product/statistics',
    method: 'get',
    params
  });
}
