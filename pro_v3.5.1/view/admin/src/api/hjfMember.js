/**
 * 会员管理模块 - Admin API
 * @module api/hjfMember
 * @description 会员列表、会员等级调整、会员配置等管理端接口
 */

import request from '@/plugins/request';
import { MOCK_MEMBER_LIST, MOCK_MEMBER_CONFIG } from '@/utils/hjfMockData.js';

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
 * 会员列表（分页 + 等级/关键字筛选）
 * @param {Object} [data] - 查询参数：page、limit、keyword、member_level 等
 * @returns {Promise<{ status: number, data: { list: Array, count: number } }>}
 */
export function memberListApi(data) {
  if (USE_MOCK) return mockResponse(MOCK_MEMBER_LIST);
  return request({ url: 'hjf/member/list', method: 'get', params: data });
}

/**
 * 手动调整会员等级
 * @param {number} uid - 用户 ID
 * @param {Object} data - 包含 level 字段的对象
 * @returns {Promise<{ status: number, data: Object }>}
 */
export function memberLevelUpdateApi(uid, data) {
  if (USE_MOCK) return mockResponse({ success: true });
  return request({ url: `hjf/member/level/${uid}`, method: 'put', data });
}

/**
 * 会员配置（获取等级门槛、直推/伞下奖励等）
 * @returns {Promise<{ status: number, data: { levels: Array } }>}
 */
export function memberConfigGetApi() {
  if (USE_MOCK) return mockResponse(MOCK_MEMBER_CONFIG);
  return request({ url: 'hjf/member/config', method: 'get' });
}

/**
 * 保存会员配置
 * @param {Object} data - 包含各等级配置的对象
 * @returns {Promise<{ status: number, data: Object }>}
 */
export function memberConfigSaveApi(data) {
  if (USE_MOCK) return mockResponse({ success: true });
  return request({ url: 'hjf/member/config', method: 'post', data });
}
