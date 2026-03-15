/**
 * 黄精粉健康商城 - 公排相关 API
 * 公排状态、公排历史记录
 * @see docs/frontend-new-pages-spec.md 2.2.1
 */

import request from '@/utils/request.js';
import { getMockQueueStatus, getMockQueueHistory } from '@/utils/hjfMockData.js';

/** @type {boolean} 是否使用 Mock 数据（Phase 4 集成时改为 false） */
const USE_MOCK = false;

/**
 * Mock 包装：返回与 request.get() 相同形状的 Promise
 * 300ms 延迟模拟网络，JSON 深拷贝防止数据突变
 * @param {*} data - 要返回的数据
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
 * 获取公排状态（我的排队 + 全局进度）
 * @returns {Promise<{ status: number, data: { totalOrders: number, myOrders: Array, progress: Object } }>}
 */
export function getQueueStatus() {
  if (USE_MOCK) return mockResponse(getMockQueueStatus());
  return request.get('hjf/queue/status');
}

/**
 * 获取公排历史记录（分页）
 * @param {Object} [params] - 查询参数，如 page、limit
 * @returns {Promise<{ status: number, data: { list: Array, count: number, page: number, limit: number } }>}
 */
export function getQueueHistory(params) {
  if (USE_MOCK) return mockResponse(getMockQueueHistory());
  return request.get('hjf/queue/history', params);
}
