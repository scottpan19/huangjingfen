/**
 * 会员管理模块 - Admin API
 * @module api/hjfMember
 * @description 会员列表、会员配置、设置不考核、设置会员等级等管理端接口
 */

import request from '@/plugins/request';
import { MOCK_MEMBER_LIST, MOCK_MEMBER_CONFIG } from '@/utils/hjfMockData.js';

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
 * 会员列表（分页 + 等级/不考核筛选）
 * @param {Object} [data] - 查询参数，如 page、limit、keyword、member_level、no_assess 等
 * @returns {Promise<{ status: number, data: { list: Array, count: number, page: number, limit: number } }>}
 */
export function memberList(data) {
  if (USE_MOCK) return mockResponse(MOCK_MEMBER_LIST);
  return request({ url: 'hjf/member/list', method: 'get', params: data });
}

/**
 * 会员配置（获取等级门槛、直推/伞下奖励等）
 * @returns {Promise<{ status: number, data: Object }>}
 */
export function memberConfig() {
  if (USE_MOCK) return mockResponse(MOCK_MEMBER_CONFIG);
  return request({ url: 'hjf/member/config', method: 'get' });
}

/**
 * 获取会员配置（新接口，供 memberConfig 页面使用）
 * @returns {Promise<{ status: number, data: { levels: Array } }>}
 */
export function memberConfigGetApi() {
  if (USE_MOCK) return mockResponse(MOCK_MEMBER_CONFIG);
  return request({ url: 'hjf/member/config', method: 'get' });
}

/**
 * 保存会员配置
 * @param {{ levels: Array }} data - 包含各等级配置的对象
 * @returns {Promise<{ status: number, data: Object }>}
 */
export function memberConfigSaveApi(data) {
  if (USE_MOCK) return mockResponse({ success: true });
  return request({ url: 'hjf/member/config', method: 'put', data });
}

/**
 * 设置不考核
 * @param {number} uid - 用户 ID
 * @param {number} status - 不考核状态：0 正常考核，1 不考核
 * @returns {Promise<{ status: number, data: Object }>}
 */
export function memberSetNoAssess(uid, status) {
  if (USE_MOCK) return mockResponse({ success: true });
  return request({
    url: `hjf/member/${uid}/no_assess`,
    method: 'put',
    data: { status }
  });
}

/**
 * 设置会员等级
 * @param {number} uid - 用户 ID
 * @param {number} level - 会员等级：0 普通 1 创客 2 云店 3 服务商 4 分公司
 * @returns {Promise<{ status: number, data: Object }>}
 */
export function memberSetLevel(uid, level) {
  if (USE_MOCK) return mockResponse({ success: true });
  return request({
    url: `hjf/member/level/${uid}`,
    method: 'put',
    data: { level }
  });
}

/**
 * 会员列表（分页 + 等级/关键字筛选）—— memberLevel 页面专用别名
 * @param {Object} [params] - 查询参数：page、limit、keyword、member_level 等
 * @returns {Promise<{ status: number, data: { list: Array, count: number } }>}
 */
export function memberListApi(params) {
  if (USE_MOCK) return mockResponse(MOCK_MEMBER_LIST);
  return request({ url: 'hjf/member/list', method: 'get', params });
}

/**
 * 手动调整会员等级
 * @param {{ uid: number, level: number }} data - 用户 ID 与目标等级
 * @returns {Promise<{ status: number, data: Object }>}
 */
export function memberLevelUpdateApi(data) {
  if (USE_MOCK) return mockResponse({ success: true });
  return request({ url: `hjf/member/level/${data.uid}`, method: 'put', data: { level: data.level } });
}
