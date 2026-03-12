/**
 * 黄精粉健康商城 - 会员模块 API
 * 会员信息、团队数据、团队收益
 * @module api/hjfMember
 */

import request from '@/utils/request.js';
import {
  getMockMemberInfo,
  getMockTeamData,
  getMockTeamIncome
} from '@/utils/hjfMockData.js';

/** @type {boolean} Phase 1 前端开发为 true，Phase 4 集成时改为 false */
const USE_MOCK = true;

/**
 * Mock 包装：返回与 request.get() 相同形状的 Promise
 * @param {Object} data - 要返回的响应体数据
 * @param {number} [delay=300] - 模拟网络延迟（毫秒）
 * @returns {Promise<{ status: number, data: Object }>}
 */
function mockResponse(data, delay = 300) {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({ status: 200, data: JSON.parse(JSON.stringify(data)) });
    }, delay);
  });
}

/**
 * 获取会员信息（等级、直推/伞下人数、升级进度等）
 * @returns {Promise<{ status: number, data: Object }>} 会员信息
 */
export function getMemberInfo() {
  if (USE_MOCK) return mockResponse(getMockMemberInfo());
  return request.get('hjf/member/info');
}

/**
 * 获取团队成员列表（直推/伞下成员、分页）
 * @param {Object} [params] - 查询参数
 * @param {number} [params.page] - 页码
 * @param {number} [params.limit] - 每页条数
 * @param {string} [params.type] - 筛选类型（如 direct/umbrella）
 * @returns {Promise<{ status: number, data: Object }>} 团队数据
 */
export function getTeamData(params) {
  if (USE_MOCK) return mockResponse(getMockTeamData());
  return request.get('hjf/member/team', params);
}

/**
 * 获取团队收益明细（直推奖励、伞下奖励等）
 * @param {Object} [params] - 查询参数
 * @param {number} [params.page] - 页码
 * @param {number} [params.limit] - 每页条数
 * @returns {Promise<{ status: number, data: Object }>} 团队收益列表
 */
export function getTeamIncome(params) {
  if (USE_MOCK) return mockResponse(getMockTeamIncome());
  return request.get('hjf/member/income', params);
}
