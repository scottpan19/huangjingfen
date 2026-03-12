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
const USE_MOCK = true;

/**
 * Mock 包装：返回与 request 相同形状的 Promise（status + data）
 * @param {Object} data - 要返回的响应体
 * @param {number} [delay=200] - 模拟网络延迟（ms）
 * @returns {Promise<{ status: number, data: Object }>}
 */
function mockResponse(data, delay = 200) {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({ status: 200, data: JSON.parse(JSON.stringify(data)) });
    }, delay);
  });
}

/**
 * 公排订单列表（分页 + 筛选）
 * @param {Object} [data] - 查询参数，如 page、limit、keyword、status、date_range 等
 * @returns {Promise<{ status: number, data: { list: Array, count: number, page: number, limit: number } }>}
 */
export function queueOrderList(data) {
  if (USE_MOCK) return mockResponse(MOCK_QUEUE_ORDER_LIST);
  return request({ url: 'hjf/queue/order_list', method: 'get', params: data });
}

/** @alias queueOrderList */
export const queueOrderListApi = queueOrderList;

/**
 * 公排配置（获取）
 * @returns {Promise<{ status: number, data: { trigger_multiple: number, refund_cycle: number, enabled: boolean, release_rate: number, withdraw_fee_rate: number } }>}
 */
export function queueConfig() {
  if (USE_MOCK) return mockResponse(MOCK_QUEUE_CONFIG);
  return request({ url: 'hjf/queue/config', method: 'get' });
}

/** @alias queueConfig */
export const queueConfigGetApi = queueConfig;

/**
 * 公排配置（保存）
 * @param {Object} data - 配置数据，包含 trigger_multiple、refund_cycle、enabled 等字段
 * @returns {Promise<{ status: number, data: Object }>}
 */
export function queueConfigSave(data) {
  if (USE_MOCK) return mockResponse({ message: 'ok' }, 300);
  return request({ url: 'hjf/queue/config', method: 'put', data });
}

/** @alias queueConfigSave */
export const queueConfigSaveApi = queueConfigSave;

/**
 * 公排财务明细（退款流水列表，分页）
 * @param {Object} [data] - 查询参数，如 page、limit、date_range 等
 * @returns {Promise<{ status: number, data: { list: Array, count: number, total_refund: string, page: number, limit: number } }>}
 */
export function queueFinanceList(data) {
  if (USE_MOCK) return mockResponse(MOCK_QUEUE_FINANCE);
  return request({ url: 'hjf/queue/finance', method: 'get', params: data });
}

/** @alias queueFinanceList */
export const queueFinanceListApi = queueFinanceList;
