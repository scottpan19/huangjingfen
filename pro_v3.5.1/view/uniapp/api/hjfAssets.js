/**
 * 黄精粉健康商城 - 资产相关 API
 * 资产概览、积分明细、现金明细、提现信息及申请提现
 * @module api/hjfAssets
 */

import request from '@/utils/request.js';
import {
  getMockAssetsOverview,
  getMockPointsDetail,
  getMockCashDetail,
  getMockWithdrawInfo
} from '@/utils/hjfMockData.js';

/** @type {boolean} 是否使用 Mock 数据（Phase 1 开发为 true，Phase 4 集成改为 false） */
const USE_MOCK = false;

/**
 * Mock 包装：返回与 request 相同形状的 Promise（status + data），带延迟模拟网络
 * @param {*} data - 要返回的响应体
 * @param {number} [delay=300] - 延迟毫秒数
 * @returns {Promise<{ status: number, data: * }>}
 */
function mockResponse(data, delay = 300) {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({ status: 200, data: JSON.parse(JSON.stringify(data)) });
    }, delay);
  });
}

/**
 * 获取资产概览（余额、冻结/可用积分、今日释放、公排总退款等）
 * @returns {Promise<{ status: number, data: Object }>}
 */
export function getAssetsOverview() {
  if (USE_MOCK) return mockResponse(getMockAssetsOverview());
  return request.get('hjf/assets/overview');
}

/**
 * 获取积分明细（分页）
 * @param {Object} [params] - 查询参数，如 page、limit
 * @returns {Promise<{ status: number, data: Object }>}
 */
export function getPointsDetail(params) {
  if (USE_MOCK) return mockResponse(getMockPointsDetail());
  return request.get('hjf/assets/points_detail', params);
}

/**
 * 获取现金明细（分页）
 * @param {Object} [params] - 查询参数，如 page、limit
 * @returns {Promise<{ status: number, data: Object }>}
 */
export function getCashDetail(params) {
  if (USE_MOCK) return mockResponse(getMockCashDetail());
  return request.get('hjf/assets/cash_detail', params);
}

/**
 * 获取提现信息（可提现余额、最低金额、手续费率、渠道列表等）
 * @returns {Promise<{ status: number, data: Object }>}
 */
export function getWithdrawInfo() {
  if (USE_MOCK) return mockResponse(getMockWithdrawInfo());
  return request.get('hjf/assets/withdraw_info');
}

/**
 * 申请提现（POST）
 * @param {Object} data - 提现参数，如 amount、channel、bank_id 等
 * @returns {Promise<{ status: number, data?: Object }>}
 */
export function applyWithdraw(data) {
  if (USE_MOCK) return mockResponse({ success: true, msg: '提现申请已提交' });
  return request.post('hjf/assets/withdraw', data);
}
