/**
 * 公排模块 - Admin API
 * @module api/hjfQueue
 * @description 公排订单列表、公排配置、公排财务明细等管理端接口
 */

import request from '@/plugins/request';
import {
  MOCK_QUEUE_ORDER_LIST,
  MOCK_QUEUE_CONFIG,
  MOCK_QUEUE_FINANCE
} from '@/utils/hjfMockData.js';

/** @type {boolean} Phase 1 使用 Mock；Phase 4 集成时改为 false */
const USE_MOCK = false;

/**
 * Mock 包装：300ms 延迟，返回 { data: ..., status: 200 }
 * @param {Object} data - 要返回的响应体
 * @returns {Promise<{ status: number, data: Object }>}
 */
function mockResponse(data) {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({ status: 200, data: JSON.parse(JSON.stringify(data)) });
    }, 300);
  });
}

/**
 * 公排订单列表（分页 + 筛选）
 * @param {Object} [data] - 查询参数，如 page、limit、keyword、status 等
 * @returns {Promise<{ status: number, data: { list: Array, count: number } }>}
 */
export function queueOrderListApi(data) {
  if (USE_MOCK) return mockResponse(MOCK_QUEUE_ORDER_LIST);
  return request({ url: 'hjf/queue/order', method: 'get', params: data });
}

/**
 * 公排配置（获取）
 * @returns {Promise<{ status: number, data: Object }>}
 */
export function queueConfigGetApi() {
  if (USE_MOCK) return mockResponse(MOCK_QUEUE_CONFIG);
  return request({ url: 'hjf/queue/config', method: 'get' });
}

/**
 * 公排配置（保存）
 * @param {Object} data - 配置数据
 * @returns {Promise<{ status: number, data: Object }>}
 */
export function queueConfigSaveApi(data) {
  if (USE_MOCK) return mockResponse({ message: 'ok' });
  return request({ url: 'hjf/queue/config', method: 'post', data });
}

/**
 * 公排财务明细（退款流水列表，分页）
 * @param {Object} [data] - 查询参数，如 page、limit、date_range 等
 * @returns {Promise<{ status: number, data: { list: Array, count: number, total_refund: string } }>}
 */
export function queueFinanceListApi(data) {
  if (USE_MOCK) return mockResponse(MOCK_QUEUE_FINANCE);
  return request({ url: 'hjf/queue/finance', method: 'get', params: data });
}
