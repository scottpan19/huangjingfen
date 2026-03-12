import request from '@/plugins/request';
import { MOCK_POINTS_RELEASE_LOG } from '@/utils/hjfMockData.js';

const USE_MOCK = true;

function mockResponse(data, delay = 200) {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({ status: 200, data: JSON.parse(JSON.stringify(data)) });
    }, delay);
  });
}

export function pointsReleaseLogApi(data) {
  if (USE_MOCK) return mockResponse(MOCK_POINTS_RELEASE_LOG);
  return request({ url: 'hjf/points/release_log', method: 'get', params: data });
}
