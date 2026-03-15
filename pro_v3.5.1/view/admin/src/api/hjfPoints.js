/**
 * 积分管理模块 - Admin API
 * @module api/hjfPoints
 * @description 积分释放日志等管理端接口
 */

import request from '@/plugins/request';
import { MOCK_POINTS_RELEASE_LOG } from '@/utils/hjfMockData.js';

/** @type {boolean} Phase 1 使用 Mock；Phase 4 集成时改为 false */
const USE_MOCK = true;

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
 * 积分释放日志列表（分页）
 * @param {Object} [data] - 查询参数：page、limit、date_range 等
 * @returns {Promise<{ status: number, data: { list: Array, count: number } }>}
 */
export function pointsReleaseLogApi(data) {
  if (USE_MOCK) return mockResponse(MOCK_POINTS_RELEASE_LOG);
  return request({ url: 'hjf/points/release-log', method: 'get', params: data });
}
