# 黄精粉健康商城 · 前端开发规范文档

> **技术底座**：CRMEB Pro v3.5 二次开发
> **文档版本**：V2.0 · 2026年3月
> **配套文件**：`docs/openclaw-frontend-tasks.md`（Agent 执行任务清单）

---

## 1. 文档说明

### 1.1 目的与读者

本文档为黄精粉健康商城微信小程序的**前端开发规范**，按业务模块组织，每个模块包含四部分：

1. **数据库设计** — 建表 SQL、索引、字段修改
2. **前端开发** — API 模块（含 Mock）、组件、页面、路由注册
3. **后端开发** — Controller / Service / Dao / 路由 / API 契约
4. **测试用例** — 前端渲染、后端接口、E2E 流程

**读者**：AI Agent（OpenClaw）、前端开发、后端开发、测试工程师

### 1.2 技术约定

#### 1.2.1 UniApp（移动端）

| 维度 | 约定 |
|------|------|
| 框架 | Vue 2 Options API (data / methods / computed / watch) |
| 样式 | `<style scoped lang="scss">`，使用 rpx 单位 |
| 主题色 | CSS 变量 `var(--view-theme)` / `var(--view-gradient)` |
| 组件前缀 | `Hjf`（如 HjfQueueProgress、HjfAssetCard） |
| API 模块 | 放 `api/` 目录，导入 `import request from "@/utils/request.js"` |
| 请求方式 | `request.get(url, params)` / `request.post(url, data)` |
| 分页模式 | `page` + `limit`，`onReachBottom()` 上拉加载 |
| 空状态 | 引入 `emptyPage` 组件 |
| 金额展示 | 保留两位小数，元为单位 |

#### 1.2.2 Admin（管理后台）

| 维度 | 约定 |
|------|------|
| UI 框架 | iView Admin（Card / Form / Table / Page） |
| 布局组件 | `BasicLayout`（路由注册时使用） |
| API 模块 | 放 `api/` 目录，导入 `import request from '@/plugins/request'` |
| 请求方式 | `request({ url, method, params/data })` |
| 列表页模式 | `formValidate` 对象 + `columns` 数组 + `getList()` / `pageChange()` |
| 路由命名 | 前缀 `const pre = 'hjf_'`，如 `${pre}queueOrder` |
| 权限标识 | `meta: { auth: ['hjf-queue-order'] }` |

### 1.3 参考文件清单

| 文件路径 | 用途 |
|----------|------|
| `docs/PRD_V2.md` | 业务需求 + 数据库 schema + API 契约 |
| `docs/黄精粉小程序_Figma_UI设计说明文档.md` | 页面编号 (P12-P23) + UI 规范 |
| `pro_v3.5.1/view/uniapp/api/user.js` | UniApp API 模块编写规范 |
| `pro_v3.5.1/view/uniapp/utils/request.js` | HTTP 请求封装（Mock 注入点） |
| `pro_v3.5.1/view/uniapp/pages/users/user_bill/index.vue` | 列表页标准（分组 + 翻页） |
| `pro_v3.5.1/view/uniapp/pages/users/user_money/index.vue` | 资产页渐变卡片模式 |
| `pro_v3.5.1/view/uniapp/pages/user/index.vue` | 个人中心 DIY 架构（改造目标） |
| `pro_v3.5.1/view/uniapp/pages/users/user_cash/index.vue` | 提现页（改造目标） |
| `pro_v3.5.1/view/admin/src/pages/finance/commission/index.vue` | Admin 列表页 (Card+Form+Table+Page) |
| `pro_v3.5.1/view/admin/src/api/finance.js` | Admin API 模块规范 |
| `pro_v3.5.1/view/admin/src/router/modules/finance.js` | Admin 路由注册规范 |

### 1.4 Mock 数据机制说明

#### 1.4.1 设计目标

前端开发阶段（Phase 1）不依赖后端 API，所有页面使用本地 Mock 数据渲染，保证：
- 页面加载后立即可浏览和交互
- Mock 数据具有真实感（合理的金额、时间、状态分布）
- Phase 4 集成时仅需修改 `USE_MOCK = false` 即可切换到真实 API

#### 1.4.2 UniApp Mock 模式

**Mock 数据集中管理**：`pro_v3.5.1/view/uniapp/utils/hjfMockData.js`

**API 模块内 USE_MOCK 开关**：

```javascript
// pro_v3.5.1/view/uniapp/api/hjfQueue.js
import request from "@/utils/request.js";
import { MOCK_QUEUE_STATUS, MOCK_QUEUE_HISTORY } from '@/utils/hjfMockData.js';

const USE_MOCK = true; // Phase 4 时改为 false

/**
 * Mock 包装：返回与 request.get() 相同形状的 Promise
 * 300ms 延迟模拟网络，JSON 深拷贝防止数据突变
 */
function mockResponse(data, delay = 300) {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({ status: 200, data: JSON.parse(JSON.stringify(data)) });
    }, delay);
  });
}

export function getQueueStatus() {
  if (USE_MOCK) return mockResponse(MOCK_QUEUE_STATUS);
  return request.get('hjf/queue/status');
}

export function getQueueHistory(params) {
  if (USE_MOCK) return mockResponse(MOCK_QUEUE_HISTORY);
  return request.get('hjf/queue/history', params);
}
```

#### 1.4.3 Admin Mock 模式

**Mock 数据集中管理**：`pro_v3.5.1/view/admin/src/utils/hjfMockData.js`

```javascript
// pro_v3.5.1/view/admin/src/api/hjfQueue.js
import request from '@/plugins/request';
import { MOCK_QUEUE_ORDER_LIST, MOCK_QUEUE_CONFIG } from '@/utils/hjfMockData.js';

const USE_MOCK = true;

function mockResponse(data, delay = 200) {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({ status: 200, data: JSON.parse(JSON.stringify(data)) });
    }, delay);
  });
}

export function queueOrderListApi(data) {
  if (USE_MOCK) return mockResponse(MOCK_QUEUE_ORDER_LIST);
  return request({ url: 'hjf/queue/order_list', method: 'get', params: data });
}
```

#### 1.4.4 开关切换流程

| 阶段 | USE_MOCK | 说明 |
|------|----------|------|
| Phase 1（前端开发） | `true` | 使用本地 Mock 数据 |
| Phase 4（前后端集成） | `false` | 切换到真实 API |
| 生产环境 | `false` | 可删除 mock 导入（tree-shaking） |

---

## 2. 公排模块

### 2.1 数据库设计

#### 2.1.1 eb_queue_pool（公排池表）[NEW]

```sql
CREATE TABLE `eb_queue_pool` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uid` INT UNSIGNED NOT NULL COMMENT '用户ID',
  `order_id` VARCHAR(64) NOT NULL COMMENT '原始订单号',
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 3600.00 COMMENT '金额',
  `queue_no` BIGINT UNSIGNED NOT NULL COMMENT '全局排队序号',
  `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0排队中 1已退款',
  `refund_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款时间戳',
  `trigger_batch` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '触发退款的批次号',
  `add_time` INT UNSIGNED NOT NULL COMMENT '入队时间戳',
  PRIMARY KEY (`id`),
  KEY `idx_uid` (`uid`),
  KEY `idx_status_queue_no` (`status`, `queue_no`),
  KEY `idx_trigger_batch` (`trigger_batch`),
  KEY `idx_add_time` (`add_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='公排池表';
```

### 2.2 前端开发

#### 2.2.1 API 模块 — `api/hjfQueue.js` [NEW]

**文件路径**：`pro_v3.5.1/view/uniapp/api/hjfQueue.js`

```javascript
import request from "@/utils/request.js";
import { MOCK_QUEUE_STATUS, MOCK_QUEUE_HISTORY } from '@/utils/hjfMockData.js';

const USE_MOCK = true;

function mockResponse(data, delay = 300) {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({ status: 200, data: JSON.parse(JSON.stringify(data)) });
    }, delay);
  });
}

/** 获取公排状态（我的排队 + 全局进度） */
export function getQueueStatus() {
  if (USE_MOCK) return mockResponse(MOCK_QUEUE_STATUS);
  return request.get('hjf/queue/status');
}

/** 获取公排历史记录 */
export function getQueueHistory(params) {
  if (USE_MOCK) return mockResponse(MOCK_QUEUE_HISTORY);
  return request.get('hjf/queue/history', params);
}
```

#### 2.2.2 组件 — HjfQueueProgress [NEW]

**文件路径**：`pro_v3.5.1/view/uniapp/components/HjfQueueProgress.vue`

**功能**：环形/条形进度条展示当前批次进度（如 2/4）

**Props**：

| Prop | 类型 | 说明 |
|------|------|------|
| currentCount | Number | 当前批次已入队数 |
| triggerMultiple | Number | 触发倍数（默认 4） |
| nextRefundNo | Number | 下一个退款的 queue_no |

**参考模式**：参考 `user_money/index.vue` 的渐变卡片中嵌入统计数据的方式

#### 2.2.3 组件 — HjfRefundNotice [NEW]

**文件路径**：`pro_v3.5.1/view/uniapp/components/HjfRefundNotice.vue`

**功能**：公排退款成功后的弹窗通知（退款金额、已入账到现金余额）

**Props**：

| Prop | 类型 | 说明 |
|------|------|------|
| visible | Boolean | 是否显示 |
| amount | Number | 退款金额 |
| orderId | String | 订单号 |

#### 2.2.4 P12 公排状态页 [NEW]

**文件路径**：`pro_v3.5.1/view/uniapp/pages/queue/status.vue`

**页面结构**：
1. 顶部渐变卡片：公排池总单数 + 当前批次进度（引入 HjfQueueProgress）
2. 我的排队列表：显示 myOrders 数组
   - 每条：排队序号 | 金额(¥3600.00) | 状态标签 | 预计等待
   - status=0 → 绿色"排队中"标签
   - status=1 → 灰色"已退款"标签
3. 底部上拉加载更多

**Mock 数据**（来自 `hjfMockData.js` 的 `MOCK_QUEUE_STATUS`）：

```json
{
  "totalOrders": 156,
  "myOrders": [
    {
      "id": 1,
      "order_id": "HJF202603100001",
      "amount": 3600.00,
      "queue_no": 142,
      "status": 0,
      "refund_time": 0,
      "trigger_batch": 0,
      "add_time": 1741593600,
      "position": 14,
      "estimated_wait": "约3天"
    },
    {
      "id": 2,
      "order_id": "HJF202603080002",
      "amount": 3600.00,
      "queue_no": 98,
      "status": 1,
      "refund_time": 1741507200,
      "trigger_batch": 24,
      "add_time": 1741420800,
      "position": 0,
      "estimated_wait": "已退款"
    }
  ],
  "progress": {
    "current_batch_count": 2,
    "trigger_multiple": 4,
    "next_refund_queue_no": 39
  }
}
```

**参考文件**：`pages/users/user_money/index.vue`（渐变卡片头部）+ `pages/users/user_bill/index.vue`（列表分页）

#### 2.2.5 P13 公排历史记录页 [NEW]

**文件路径**：`pro_v3.5.1/view/uniapp/pages/queue/history.vue`

**页面结构**：
1. Tab 筛选：全部 / 排队中 / 已退款
2. 按日期分组的列表（参考 user_bill 模式）
3. 每条记录：订单号 | 金额 | 退款时间 | 批次号

**Mock 数据**（来自 `MOCK_QUEUE_HISTORY`）：

```json
{
  "list": [
    {
      "id": 1,
      "order_id": "HJF202603050001",
      "amount": 3600.00,
      "queue_no": 45,
      "status": 1,
      "refund_time": 1741334400,
      "trigger_batch": 11,
      "add_time": 1741161600,
      "time_key": "2026-03-07"
    },
    {
      "id": 2,
      "order_id": "HJF202603060002",
      "amount": 3600.00,
      "queue_no": 67,
      "status": 0,
      "refund_time": 0,
      "trigger_batch": 0,
      "add_time": 1741248000,
      "time_key": "2026-03-06"
    }
  ],
  "count": 25,
  "page": 1,
  "limit": 15
}
```

#### 2.2.6 P14 公排规则说明页 [NEW]

**文件路径**：`pro_v3.5.1/view/uniapp/pages/queue/rules.vue`

**页面结构**：纯静态展示页面
1. 公排机制图示（进四退一流程图）
2. 规则条款列表
3. 常见问题 FAQ 手风琴

**无 Mock 数据依赖**（静态页面）

#### 2.2.7 pages.json 路由注册

在 `subPackages` 或 `pages` 中新增：

```json
{
  "path": "pages/queue/status",
  "style": { "navigationBarTitleText": "公排状态" }
},
{
  "path": "pages/queue/history",
  "style": { "navigationBarTitleText": "公排记录" }
},
{
  "path": "pages/queue/rules",
  "style": { "navigationBarTitleText": "公排规则" }
}
```

### 2.3 后端开发

#### 2.3.1 后端文件清单

| 文件 | 类型 | 说明 |
|------|------|------|
| `app/model/queue/QueuePool.php` | Model [NEW] | eb_queue_pool 模型 |
| `app/dao/queue/QueuePoolDao.php` | Dao [NEW] | 数据访问层 |
| `app/services/queue/QueuePoolService.php` | Service [NEW] | 公排核心逻辑 |
| `app/services/queue/QueueRefundService.php` | Service [NEW] | 退款处理 |
| `app/api/controller/v1/queue/QueuePoolController.php` | Controller [NEW] | API 端控制器 |
| `route/api.php` | Route [MOD] | 添加公排路由 |

#### 2.3.2 API 契约

| 方法 | 路径 | 参数 | 返回 |
|------|------|------|------|
| GET | `hjf/queue/status` | — | `{ totalOrders, myOrders[], progress }` |
| GET | `hjf/queue/history` | `page, limit, status` | `{ list[], count, page, limit }` |

### 2.4 测试用例

| 测试类型 | 测试点 | 验收标准 |
|----------|--------|----------|
| 前端渲染 | P12 页面 Mock 数据渲染 | 卡片显示总单数、列表展示排队记录、进度条正确 |
| 前端渲染 | P13 页面 Tab 筛选 | 切换 Tab 后列表正确过滤 |
| 后端接口 | GET /hjf/queue/status | 返回当前用户的排队记录和全局进度 |
| E2E | 购买报单商品 → 入队 → 查看状态 | 支付后公排状态页显示新记录 |
| E2E | 进四退一触发 | 第4单入队后最早1单退款到余额 |

---

## 3. 资产与积分模块

### 3.1 数据库设计

#### 3.1.1 eb_user 新增字段 [MOD]

```sql
ALTER TABLE `eb_user`
  ADD COLUMN `member_level` TINYINT NOT NULL DEFAULT 0 COMMENT '会员等级: 0普通 1创客 2云店 3服务商 4分公司',
  ADD COLUMN `no_assess` TINYINT NOT NULL DEFAULT 0 COMMENT '不考核标记: 0正常 1不考核',
  ADD COLUMN `frozen_points` BIGINT NOT NULL DEFAULT 0 COMMENT '待释放积分',
  ADD COLUMN `available_points` BIGINT NOT NULL DEFAULT 0 COMMENT '已释放积分';
```

#### 3.1.2 eb_points_release_log（积分释放日志表）[NEW]

```sql
CREATE TABLE `eb_points_release_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uid` INT UNSIGNED NOT NULL COMMENT '用户ID',
  `frozen_before` BIGINT NOT NULL COMMENT '释放前待释放积分',
  `release_amount` BIGINT NOT NULL COMMENT '本次释放积分数',
  `frozen_after` BIGINT NOT NULL COMMENT '释放后待释放积分',
  `release_date` DATE NOT NULL COMMENT '释放日期',
  `add_time` INT UNSIGNED NOT NULL COMMENT '记录时间',
  PRIMARY KEY (`id`),
  KEY `idx_uid` (`uid`),
  KEY `idx_release_date` (`release_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分释放日志表';
```

### 3.2 前端开发

#### 3.2.1 API 模块 — `api/hjfAssets.js` [NEW]

**文件路径**：`pro_v3.5.1/view/uniapp/api/hjfAssets.js`

```javascript
import request from "@/utils/request.js";
import {
  MOCK_ASSETS_OVERVIEW, MOCK_POINTS_DETAIL,
  MOCK_CASH_DETAIL, MOCK_WITHDRAW_INFO
} from '@/utils/hjfMockData.js';

const USE_MOCK = true;

function mockResponse(data, delay = 300) {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({ status: 200, data: JSON.parse(JSON.stringify(data)) });
    }, delay);
  });
}

/** 获取资产总览 */
export function getAssetsOverview() {
  if (USE_MOCK) return mockResponse(MOCK_ASSETS_OVERVIEW);
  return request.get('hjf/assets/overview');
}

/** 获取积分明细 */
export function getPointsDetail(params) {
  if (USE_MOCK) return mockResponse(MOCK_POINTS_DETAIL);
  return request.get('hjf/assets/points_detail', params);
}

/** 获取现金流水明细 */
export function getCashDetail(params) {
  if (USE_MOCK) return mockResponse(MOCK_CASH_DETAIL);
  return request.get('hjf/assets/cash_detail', params);
}

/** 获取提现信息（可提现余额、手续费率、最低金额） */
export function getWithdrawInfo() {
  if (USE_MOCK) return mockResponse(MOCK_WITHDRAW_INFO);
  return request.get('hjf/assets/withdraw_info');
}
```

#### 3.2.2 API 模块 — `api/hjfMember.js` [NEW]

**文件路径**：`pro_v3.5.1/view/uniapp/api/hjfMember.js`

```javascript
import request from "@/utils/request.js";
import { MOCK_MEMBER_INFO, MOCK_TEAM_DATA, MOCK_TEAM_INCOME } from '@/utils/hjfMockData.js';

const USE_MOCK = true;

function mockResponse(data, delay = 300) {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({ status: 200, data: JSON.parse(JSON.stringify(data)) });
    }, delay);
  });
}

/** 获取会员信息（等级、团队统计） */
export function getMemberInfo() {
  if (USE_MOCK) return mockResponse(MOCK_MEMBER_INFO);
  return request.get('hjf/member/info');
}

/** 获取团队成员列表 */
export function getTeamData(params) {
  if (USE_MOCK) return mockResponse(MOCK_TEAM_DATA);
  return request.get('hjf/member/team', params);
}

/** 获取推荐收益明细 */
export function getTeamIncome(params) {
  if (USE_MOCK) return mockResponse(MOCK_TEAM_INCOME);
  return request.get('hjf/member/income', params);
}
```

#### 3.2.3 组件 — HjfAssetCard [NEW]

**文件路径**：`pro_v3.5.1/view/uniapp/components/HjfAssetCard.vue`

**功能**：三栏资产展示卡片（现金余额 / 待释放积分 / 已释放积分），渐变背景

**Props**：

| Prop | 类型 | 说明 |
|------|------|------|
| nowMoney | String | 现金余额 |
| frozenPoints | Number | 待释放积分 |
| availablePoints | Number | 已释放积分 |
| todayRelease | Number | 今日预计释放 |

**参考模式**：`pages/users/user_money/index.vue` 中的 `.account` 渐变卡片区域

#### 3.2.4 组件 — HjfMemberBadge [NEW]

**文件路径**：`pro_v3.5.1/view/uniapp/components/HjfMemberBadge.vue`

**功能**：会员等级徽章（图标 + 等级名称）

**Props**：

| Prop | 类型 | 说明 |
|------|------|------|
| level | Number | 等级数字 (0-4) |
| levelName | String | 等级名称 |
| size | String | 'small' / 'normal' / 'large' |

#### 3.2.5 P15 资产总览页 [NEW]

**文件路径**：`pro_v3.5.1/view/uniapp/pages/assets/index.vue`

**页面结构**：
1. HjfAssetCard 三栏资产卡片（渐变背景）
2. 快捷导航卡片：
   - 现金余额 → 提现页
   - 待释放积分 → 积分明细
   - 已释放积分 → 积分明细
   - 公排记录 → P12 公排状态页
3. 今日释放提示：「今日预计释放 X 积分」

**Mock 数据**（来自 `MOCK_ASSETS_OVERVIEW`）：

```json
{
  "now_money": "7200.00",
  "frozen_points": 15000,
  "available_points": 3200,
  "today_release": 6,
  "total_queue_refund": "14400.00",
  "total_points_earned": 18200,
  "member_level": 2,
  "member_level_name": "云店"
}
```

**参考文件**：`pages/users/user_money/index.vue`（整体布局 + 渐变卡片 + 导航卡片）

#### 3.2.6 P18 积分明细页 [NEW]

**文件路径**：`pro_v3.5.1/view/uniapp/pages/assets/points_detail.vue`

**页面结构**：
1. Tab 筛选：全部 / 直推奖励 / 伞下奖励 / 每日释放 / 消费
2. 按日期分组的列表
3. 每条：标题 | 积分数(±) | 状态(待释放/已释放) | 时间

**Mock 数据**（来自 `MOCK_POINTS_DETAIL`）：

```json
{
  "list": [
    {
      "id": 1,
      "title": "直推奖励 - 用户张三购买报单商品",
      "type": "reward_direct",
      "points": 800,
      "pm": 1,
      "status": "frozen",
      "add_time": "2026-03-10 14:30",
      "order_id": "HJF202603100005"
    },
    {
      "id": 2,
      "title": "每日释放 - 待释放积分自动解冻",
      "type": "release",
      "points": 6,
      "pm": 1,
      "status": "released",
      "add_time": "2026-03-10 00:00"
    },
    {
      "id": 3,
      "title": "积分消费 - 购买普通商品",
      "type": "consume",
      "points": 200,
      "pm": 0,
      "status": "released",
      "add_time": "2026-03-09 16:22",
      "order_id": "HJF202603090012"
    }
  ],
  "count": 45,
  "page": 1,
  "limit": 15
}
```

**参考文件**：`pages/users/user_bill/index.vue`（Tab 筛选 + 分组列表 + 上拉加载）

#### 3.2.7 pages.json 路由注册

```json
{
  "path": "pages/assets/index",
  "style": { "navigationBarTitleText": "我的资产" }
},
{
  "path": "pages/assets/points_detail",
  "style": { "navigationBarTitleText": "积分明细" }
}
```

### 3.3 后端开发

#### 3.3.1 后端文件清单

| 文件 | 类型 | 说明 |
|------|------|------|
| `app/model/points/PointsReleaseLog.php` | Model [NEW] | 积分释放日志模型 |
| `app/dao/points/PointsReleaseLogDao.php` | Dao [NEW] | 数据访问层 |
| `app/services/points/PointsReleaseService.php` | Service [NEW] | 每日释放逻辑 |
| `app/services/points/PointsPayService.php` | Service [NEW] | 积分支付逻辑 |
| `app/services/member/MemberLevelService.php` | Service [NEW] | 等级升级逻辑 |
| `app/services/member/MemberRewardService.php` | Service [NEW] | 积分奖励计算 |
| `app/services/member/MemberTeamService.php` | Service [NEW] | 团队业绩统计 |
| `app/api/controller/v1/user/HjfAssetsController.php` | Controller [NEW] | 资产 API |
| `app/api/controller/v1/user/HjfMemberController.php` | Controller [NEW] | 会员 API |

#### 3.3.2 API 契约

| 方法 | 路径 | 参数 | 返回 |
|------|------|------|------|
| GET | `hjf/assets/overview` | — | `{ now_money, frozen_points, available_points, today_release, member_level, member_level_name }` |
| GET | `hjf/assets/points_detail` | `page, limit, type` | `{ list[], count, page, limit }` |
| GET | `hjf/assets/cash_detail` | `page, limit` | `{ list[], count, page, limit }` |
| GET | `hjf/assets/withdraw_info` | — | `{ now_money, min_extract, fee_rate, extract_bank[] }` |
| GET | `hjf/member/info` | — | `{ member_level, member_level_name, direct_count, umbrella_count, umbrella_orders }` |
| GET | `hjf/member/team` | `page, limit, type` | `{ members[], count, page, limit }` |
| GET | `hjf/member/income` | `page, limit` | `{ list[], count, page, limit }` |

### 3.4 测试用例

| 测试类型 | 测试点 | 验收标准 |
|----------|--------|----------|
| 前端渲染 | P15 资产总览 Mock 渲染 | 三栏卡片显示正确金额和积分 |
| 前端渲染 | P18 积分明细 Tab 筛选 | 切换 Tab 后列表过滤正确 |
| 后端接口 | 积分每日释放定时任务 | frozen_points × 0.4‰ 正确转入 available_points |
| 后端接口 | 会员等级升级 | 直推满3单自动升为创客 |
| E2E | 推荐购买 → 积分到账 → 每日释放 → 积分消费 | 全流程积分流转正确 |

---

## 4. 新用户引导模块

### 4.1 数据库设计

无新表。引导状态可通过 `eb_user` 的首次登录时间判断。

### 4.2 前端开发

#### 4.2.1 P23 新用户引导页 [NEW]

**文件路径**：`pro_v3.5.1/view/uniapp/pages/guide/hjf_intro.vue`

**页面结构**：
1. 轮播引导（3-4 屏）
   - 第1屏：平台介绍（健康商城概览）
   - 第2屏：公排规则图示（进四退一）
   - 第3屏：会员等级与积分说明
   - 第4屏：「立即开始」按钮 → 跳转首页
2. 底部指示器 + 跳过按钮

**Mock 数据**（来自 `MOCK_GUIDE_DATA`）：

```json
{
  "slides": [
    {
      "title": "欢迎来到黄精粉健康商城",
      "desc": "健康好物，品质生活",
      "image": "/static/images/guide/slide1.png"
    },
    {
      "title": "公排返利机制",
      "desc": "购买报单商品自动进入公排，每进4单退1单全额返还",
      "image": "/static/images/guide/slide2.png"
    },
    {
      "title": "会员积分体系",
      "desc": "推荐好友即获积分奖励，积分每日自动释放",
      "image": "/static/images/guide/slide3.png"
    }
  ]
}
```

#### 4.2.2 pages.json 路由注册

```json
{
  "path": "pages/guide/hjf_intro",
  "style": { "navigationBarTitleText": "", "navigationStyle": "custom" }
}
```

### 4.3 后端开发

无后端接口需求。

### 4.4 测试用例

| 测试类型 | 测试点 | 验收标准 |
|----------|--------|----------|
| 前端渲染 | P23 轮播引导 | 3 屏正常滑动，指示器同步 |
| 前端交互 | 跳过/立即开始 | 正确跳转至首页 |

---

## 5. 管理后台新增页面

### 5.1 数据库设计

共享模块 2-3 的表（eb_queue_pool、eb_points_release_log、eb_user 字段）。

### 5.2 前端开发

#### 5.2.1 Admin API — `api/hjfQueue.js` [NEW]

**文件路径**：`pro_v3.5.1/view/admin/src/api/hjfQueue.js`

```javascript
import request from '@/plugins/request';
import {
  MOCK_QUEUE_ORDER_LIST, MOCK_QUEUE_CONFIG,
  MOCK_QUEUE_FINANCE
} from '@/utils/hjfMockData.js';

const USE_MOCK = true;

function mockResponse(data, delay = 200) {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({ status: 200, data: JSON.parse(JSON.stringify(data)) });
    }, delay);
  });
}

/** 公排订单列表 */
export function queueOrderListApi(data) {
  if (USE_MOCK) return mockResponse(MOCK_QUEUE_ORDER_LIST);
  return request({ url: 'hjf/queue/order_list', method: 'get', params: data });
}

/** 公排参数配置-获取 */
export function queueConfigGetApi() {
  if (USE_MOCK) return mockResponse(MOCK_QUEUE_CONFIG);
  return request({ url: 'hjf/queue/config', method: 'get' });
}

/** 公排参数配置-保存 */
export function queueConfigSaveApi(data) {
  if (USE_MOCK) return mockResponse({ success: true });
  return request({ url: 'hjf/queue/config', method: 'put', data });
}

/** 公排财务流水 */
export function queueFinanceListApi(data) {
  if (USE_MOCK) return mockResponse(MOCK_QUEUE_FINANCE);
  return request({ url: 'hjf/queue/finance', method: 'get', params: data });
}
```

#### 5.2.2 Admin API — `api/hjfMember.js` [NEW]

**文件路径**：`pro_v3.5.1/view/admin/src/api/hjfMember.js`

```javascript
import request from '@/plugins/request';
import { MOCK_MEMBER_LIST, MOCK_MEMBER_CONFIG } from '@/utils/hjfMockData.js';

const USE_MOCK = true;

function mockResponse(data, delay = 200) {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({ status: 200, data: JSON.parse(JSON.stringify(data)) });
    }, delay);
  });
}

/** 会员管理列表 */
export function memberListApi(data) {
  if (USE_MOCK) return mockResponse(MOCK_MEMBER_LIST);
  return request({ url: 'hjf/member/list', method: 'get', params: data });
}

/** 会员等级调整 */
export function memberLevelUpdateApi(uid, data) {
  if (USE_MOCK) return mockResponse({ success: true });
  return request({ url: `hjf/member/level/${uid}`, method: 'put', data });
}

/** 会员配置-获取 */
export function memberConfigGetApi() {
  if (USE_MOCK) return mockResponse(MOCK_MEMBER_CONFIG);
  return request({ url: 'hjf/member/config', method: 'get' });
}

/** 会员配置-保存 */
export function memberConfigSaveApi(data) {
  if (USE_MOCK) return mockResponse({ success: true });
  return request({ url: 'hjf/member/config', method: 'put', data });
}
```

#### 5.2.3 Admin API — `api/hjfPoints.js` [NEW]

**文件路径**：`pro_v3.5.1/view/admin/src/api/hjfPoints.js`

```javascript
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

/** 积分释放日志列表 */
export function pointsReleaseLogApi(data) {
  if (USE_MOCK) return mockResponse(MOCK_POINTS_RELEASE_LOG);
  return request({ url: 'hjf/points/release_log', method: 'get', params: data });
}
```

#### 5.2.4 公排订单管理页 [NEW]

**文件路径**：`pro_v3.5.1/view/admin/src/pages/hjf/queueOrder/index.vue`

**页面结构**（参考 `finance/commission/index.vue`）：
1. 搜索区：用户昵称/ID + 状态筛选 + 日期范围
2. 数据表格：用户信息 | 订单号 | 金额 | 排队序号 | 状态 | 退款时间 | 入队时间

**Mock 数据**（来自 `MOCK_QUEUE_ORDER_LIST`）：

```json
{
  "list": [
    {
      "id": 1,
      "uid": 10086,
      "nickname": "王五",
      "phone": "138****8888",
      "order_id": "HJF202603100001",
      "amount": 3600.00,
      "queue_no": 142,
      "status": 0,
      "status_text": "排队中",
      "refund_time": "",
      "trigger_batch": 0,
      "add_time": "2026-03-10 10:00:00"
    },
    {
      "id": 2,
      "uid": 10087,
      "nickname": "张三",
      "phone": "139****9999",
      "order_id": "HJF202603080002",
      "amount": 3600.00,
      "queue_no": 98,
      "status": 1,
      "status_text": "已退款",
      "refund_time": "2026-03-09 12:00:00",
      "trigger_batch": 24,
      "add_time": "2026-03-08 09:30:00"
    }
  ],
  "count": 156,
  "page": 1,
  "limit": 20
}
```

**Table Columns**：

```javascript
columns: [
  { title: '用户', slot: 'user', minWidth: 200 },
  { title: '订单号', key: 'order_id', minWidth: 180 },
  { title: '金额', key: 'amount', minWidth: 100 },
  { title: '排队序号', key: 'queue_no', minWidth: 100 },
  { title: '状态', slot: 'status', minWidth: 100 },
  { title: '退款时间', key: 'refund_time', minWidth: 160 },
  { title: '入队时间', key: 'add_time', minWidth: 160 }
]
```

#### 5.2.5 公排财务流水页 [NEW]

**文件路径**：`pro_v3.5.1/view/admin/src/pages/hjf/queueFinance/index.vue`

**页面结构**：与公排订单类似，展示退款流水记录（批次号、退款金额、用户信息、时间）

**Mock 数据**（来自 `MOCK_QUEUE_FINANCE`）：

```json
{
  "list": [
    {
      "id": 1,
      "uid": 10085,
      "nickname": "赵六",
      "trigger_batch": 24,
      "amount": 3600.00,
      "queue_no": 39,
      "refund_time": "2026-03-09 12:00:00"
    }
  ],
  "count": 39,
  "total_refund": "140400.00",
  "page": 1,
  "limit": 20
}
```

#### 5.2.6 积分释放日志页 [NEW]

**文件路径**：`pro_v3.5.1/view/admin/src/pages/hjf/pointsLog/index.vue`

**页面结构**：
1. 搜索区：用户昵称/ID + 日期范围
2. 顶部统计：今日释放总量 / 今日释放用户数
3. 数据表格：用户 | 释放前 | 释放量 | 释放后 | 日期

**Mock 数据**（来自 `MOCK_POINTS_RELEASE_LOG`）：

```json
{
  "list": [
    {
      "id": 1,
      "uid": 10086,
      "nickname": "王五",
      "frozen_before": 15000,
      "release_amount": 6,
      "frozen_after": 14994,
      "release_date": "2026-03-10",
      "add_time": "2026-03-10 00:01:23"
    }
  ],
  "count": 500,
  "page": 1,
  "limit": 20,
  "statistics": {
    "total_released_today": 2450,
    "total_users_released": 320
  }
}
```

#### 5.2.7 公排参数配置页 [NEW]

**文件路径**：`pro_v3.5.1/view/admin/src/pages/hjf/queueConfig/index.vue`

**页面结构**：表单页面（非列表页）
- 公排触发倍数（InputNumber，默认 4）
- 积分日释放比例（InputNumber，千分之 X，默认 4）
- 提现手续费率（InputNumber，百分比，默认 7）
- 保存按钮

**Mock 数据**（来自 `MOCK_QUEUE_CONFIG`）：

```json
{
  "trigger_multiple": 4,
  "release_rate": 4,
  "withdraw_fee_rate": 7
}
```

#### 5.2.8 会员等级配置页 [NEW]

**文件路径**：`pro_v3.5.1/view/admin/src/pages/hjf/memberConfig/index.vue`

**页面结构**：表单页面
- 各等级升级门槛（创客/云店/服务商/分公司）
- 各等级直推奖励积分
- 各等级伞下奖励积分
- 保存按钮

**Mock 数据**（来自 `MOCK_MEMBER_CONFIG`）：

```json
{
  "chuangke_threshold": 3,
  "yundian_threshold": 30,
  "fuwushang_threshold": 100,
  "fengongsi_threshold": 1000,
  "chuangke_direct_reward": 500,
  "yundian_direct_reward": 800,
  "yundian_umbrella_reward": 300,
  "fuwushang_direct_reward": 1000,
  "fuwushang_umbrella_reward": 200,
  "fengongsi_direct_reward": 1300,
  "fengongsi_umbrella_reward": 300
}
```

#### 5.2.9 会员管理页 [NEW]

**文件路径**：`pro_v3.5.1/view/admin/src/pages/hjf/memberLevel/index.vue`

**页面结构**：
1. 搜索区：昵称/ID + 等级筛选 + 不考核筛选
2. 数据表格：用户信息 | 等级 | 直推数 | 伞下业绩 | 待释放积分 | 已释放积分 | 余额 | 操作
3. 操作列：调整等级 / 设置不考核

**Mock 数据**（来自 `MOCK_MEMBER_LIST`）：

```json
{
  "list": [
    {
      "uid": 10086,
      "nickname": "王五",
      "phone": "138****8888",
      "avatar": "",
      "member_level": 2,
      "member_level_name": "云店",
      "no_assess": 0,
      "direct_count": 8,
      "umbrella_orders": 42,
      "frozen_points": 15000,
      "available_points": 3200,
      "now_money": "7200.00",
      "spread_uid": 10001,
      "spread_nickname": "推荐人A"
    }
  ],
  "count": 1200,
  "page": 1,
  "limit": 20
}
```

#### 5.2.10 Admin 路由注册

**新建文件**：`pro_v3.5.1/view/admin/src/router/modules/hjfQueue.js`

```javascript
import BasicLayout from '@/layouts/basic-layout';

const pre = 'hjf_';
export default {
  path: '/admin/hjf',
  name: 'hjf',
  header: 'hjf',
  meta: {
    auth: ['admin-hjf']
  },
  component: BasicLayout,
  children: [
    {
      path: 'queue/order',
      name: `${pre}queueOrder`,
      meta: { auth: ['hjf-queue-order'], title: '公排订单' },
      component: () => import('@/pages/hjf/queueOrder/index')
    },
    {
      path: 'queue/finance',
      name: `${pre}queueFinance`,
      meta: { auth: ['hjf-queue-finance'], title: '公排财务' },
      component: () => import('@/pages/hjf/queueFinance/index')
    },
    {
      path: 'queue/config',
      name: `${pre}queueConfig`,
      meta: { auth: ['hjf-queue-config'], title: '公排配置' },
      component: () => import('@/pages/hjf/queueConfig/index')
    },
    {
      path: 'points/log',
      name: `${pre}pointsLog`,
      meta: { auth: ['hjf-points-log'], title: '积分日志' },
      component: () => import('@/pages/hjf/pointsLog/index')
    },
    {
      path: 'member/config',
      name: `${pre}memberConfig`,
      meta: { auth: ['hjf-member-config'], title: '会员配置' },
      component: () => import('@/pages/hjf/memberConfig/index')
    },
    {
      path: 'member/level',
      name: `${pre}memberLevel`,
      meta: { auth: ['hjf-member-level'], title: '会员管理' },
      component: () => import('@/pages/hjf/memberLevel/index')
    }
  ]
};
```

**路由注册入口**：需在 `router/modules/index.js` 中导入此模块。

### 5.3 后端开发

#### 5.3.1 后端文件清单

| 文件 | 类型 | 说明 |
|------|------|------|
| `app/adminapi/controller/v1/hjf/QueueOrderController.php` | Controller [NEW] | 公排订单管理 |
| `app/adminapi/controller/v1/hjf/QueueConfigController.php` | Controller [NEW] | 公排配置 |
| `app/adminapi/controller/v1/hjf/QueueFinanceController.php` | Controller [NEW] | 公排财务 |
| `app/adminapi/controller/v1/hjf/PointsLogController.php` | Controller [NEW] | 积分日志 |
| `app/adminapi/controller/v1/hjf/MemberLevelController.php` | Controller [NEW] | 会员管理 |
| `app/adminapi/controller/v1/hjf/MemberConfigController.php` | Controller [NEW] | 会员配置 |
| `route/adminapi.php` | Route [MOD] | 添加管理后台路由 |

#### 5.3.2 Admin API 契约

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `hjf/queue/order_list` | 公排订单列表（分页 + 筛选） |
| GET | `hjf/queue/config` | 获取公排配置 |
| PUT | `hjf/queue/config` | 保存公排配置 |
| GET | `hjf/queue/finance` | 公排财务流水（分页） |
| GET | `hjf/points/release_log` | 积分释放日志（分页 + 统计） |
| GET | `hjf/member/list` | 会员列表（分页 + 等级筛选） |
| PUT | `hjf/member/level/{uid}` | 调整会员等级 |
| GET | `hjf/member/config` | 获取会员配置 |
| PUT | `hjf/member/config` | 保存会员配置 |

### 5.4 测试用例

| 测试类型 | 测试点 | 验收标准 |
|----------|--------|----------|
| 前端渲染 | 6 个管理页面 Mock 渲染 | 表格/表单正常显示 |
| 后端接口 | 配置保存/读取 | 配置项正确持久化到 eb_system_config |
| 后端接口 | 等级调整 | 手动调整后 member_level 正确更新 |
| 权限测试 | 无权限用户访问 | 返回 403 |

---

## 6. 改造复用页面

### 6.1 UniApp 改造（7 项）

---

#### 6.1.1 首页改造 [MOD]

**原始文件**：`pro_v3.5.1/view/uniapp/pages/index/index.vue`

**改造目的**：在商品推荐区的报单商品卡片上增加「参与公排」角标

**改动点清单**：

| # | 改动位置 | 类型 | 说明 |
|---|----------|------|------|
| 1 | 商品卡片 template | 新增 | 根据 `is_queue_goods` 字段显示绿色/金色角标 |
| 2 | style | 新增 | `.queue-badge` 角标样式（绝对定位右上角） |

**Mock 数据**：在商品列表数据中增加 `is_queue_goods: 1` 字段标记

```json
{ "id": 101, "store_name": "黄精粉套餐", "price": "3600.00", "is_queue_goods": 1 }
```

**测试用例**：商品列表中报单商品显示角标，普通商品不显示

---

#### 6.1.2 商品详情改造 [MOD]

**原始文件**：`pro_v3.5.1/view/uniapp/pages/goods_details/index.vue`（或 subPackage 路径）

**改造目的**：支持多种支付方式选择（现金余额/待释放积分/已释放积分），报单商品不显示积分支付选项

**改动点清单**：

| # | 改动位置 | 类型 | 说明 |
|---|----------|------|------|
| 1 | 购买弹窗 template | 新增 | 支付方式选择区域（单选按钮组） |
| 2 | data() | 新增 | `payMethod: 'wechat'` 选中的支付方式 |
| 3 | 提交订单方法 | 修改 | 传递 `pay_type` 参数（wechat/balance/frozen_points/available_points） |
| 4 | 商品信息区域 | 新增 | 报单商品显示「公排商品」标签 |

**Mock 数据**：商品详情增加 `is_queue_goods` 和 `allow_pay_types` 字段

```json
{
  "is_queue_goods": 1,
  "allow_pay_types": ["wechat", "alipay", "balance"],
  "store_name": "黄精粉套餐",
  "price": "3600.00"
}
```

**测试用例**：
- 报单商品：只显示微信/支付宝/余额支付，不显示积分支付
- 普通商品：根据 allow_pay_types 显示对应支付方式

---

#### 6.1.3 购买流程改造 [MOD]

**涉及文件**：订单确认页 + 支付回调处理

**改造目的**：多单拆分 + 公排入队逻辑（主要在后端，前端仅需传参）

**改动点清单**：

| # | 改动位置 | 类型 | 说明 |
|---|----------|------|------|
| 1 | 订单确认页 | 修改 | 购买报单商品时数量>1 提示"将拆分为 N 个独立公排订单" |
| 2 | 支付成功页 | 新增 | 报单商品支付成功后显示"已加入公排"提示 + 跳转公排状态 |

**测试用例**：购买 2 单报单商品 → 生成 2 笔独立订单 → 各自进入公排

---

#### 6.1.4 我的订单改造 [MOD]

**原始文件**：订单列表/订单详情页

**改造目的**：在订单详情中增加公排状态展示

**改动点清单**：

| # | 改动位置 | 类型 | 说明 |
|---|----------|------|------|
| 1 | 订单详情 template | 新增 | 公排状态区域：排队序号 / 状态 / 预计等待 |
| 2 | 订单详情 data | 新增 | `queueInfo` 对象 |
| 3 | API 调用 | 新增 | 调用 `getQueueStatus()` 获取该订单的公排信息 |

**Mock 数据**：

```json
{
  "queueInfo": {
    "queue_no": 142,
    "status": 0,
    "position": 14,
    "estimated_wait": "约3天"
  }
}
```

**测试用例**：报单商品订单详情显示公排状态，普通商品订单不显示

---

#### 6.1.5 推荐页改造 [MOD]

**原始文件**：`pro_v3.5.1/view/uniapp/pages/users/user_spread_money/index.vue`（或对应推荐收益页）

**改造目的**：将"佣金"概念替换为"积分奖励"，展示积分而非金额

**改动点清单**：

| # | 改动位置 | 类型 | 说明 |
|---|----------|------|------|
| 1 | 页面标题 | 修改 | "推广佣金" → "推荐收益" |
| 2 | 金额展示 | 修改 | 佣金金额 → 积分数量（不带 ¥ 符号） |
| 3 | API 调用 | 修改 | 替换为 `getTeamIncome()` |
| 4 | 列表项 | 修改 | 显示积分来源（直推/伞下）+ 积分类型（待释放） |

**Mock 数据**：使用 `MOCK_TEAM_INCOME`（见第 7 章）

**测试用例**：收益列表显示积分数量而非佣金金额

---

#### 6.1.6 提现页改造 [MOD]

**原始文件**：`pro_v3.5.1/view/uniapp/pages/users/user_cash/index.vue`

**改造目的**：手续费率改为 7%，实时计算并展示

**改动点清单**：

| # | 改动位置 | 类型 | 说明 |
|---|----------|------|------|
| 1 | 提现金额输入区域 | 新增 | 实时显示「手续费：¥XX.XX（7%）| 实际到账：¥XX.XX」 |
| 2 | computed | 新增 | `feeAmount` 和 `actualAmount` 计算属性 |
| 3 | 提交逻辑 | 修改 | 传递 fee_rate 参数确认手续费 |

**Mock 数据**：使用 `MOCK_WITHDRAW_INFO`

```json
{
  "now_money": "7200.00",
  "min_extract": 100,
  "fee_rate": 7,
  "extract_bank": ["微信零钱", "支付宝", "银行卡"]
}
```

**测试用例**：
- 输入提现金额 1000 → 显示「手续费：¥70.00 | 实际到账：¥930.00」
- 金额低于最低提现额 → 提示错误

---

#### 6.1.7 个人中心改造 [MOD]

**原始文件**：`pro_v3.5.1/view/uniapp/pages/user/index.vue`

**改造目的**：增加会员等级徽章展示 + 资产入口导航

**改动点清单**：

| # | 改动位置 | 类型 | 说明 |
|---|----------|------|------|
| 1 | 用户信息区 | 新增 | 引入 HjfMemberBadge 显示会员等级 |
| 2 | DIY 菜单数据 | 新增 | 增加"我的资产"、"公排查询"导航项 |
| 3 | import | 新增 | 导入 `getMemberInfo()` 和 HjfMemberBadge 组件 |
| 4 | onShow/methods | 修改 | 合并调用 `getMemberInfo()` 获取等级信息 |

**Mock 数据**：使用 `MOCK_MEMBER_INFO`

```json
{
  "member_level": 2,
  "member_level_name": "云店",
  "direct_count": 8,
  "umbrella_count": 35,
  "umbrella_orders": 42
}
```

**测试用例**：
- 个人中心显示会员等级徽章
- 点击"我的资产"跳转到 P15 资产总览页
- 点击"公排查询"跳转到 P12 公排状态页

---

### 6.2 Admin 改造（2 项）

---

#### 6.2.1 用户管理改造 [MOD]

**原始文件**：`pro_v3.5.1/view/admin/src/pages/user/list/index.vue`

**改造目的**：增加会员等级调整和不考核标记功能

**改动点清单**：

| # | 改动位置 | 类型 | 说明 |
|---|----------|------|------|
| 1 | columns | 新增 | 增加"会员等级"列（slot 渲染等级名称 + 徽章） |
| 2 | columns | 新增 | 增加"不考核"列（Tag 标记） |
| 3 | 操作列 | 新增 | 增加"调整等级"按钮（弹窗选择等级） |
| 4 | 操作列 | 新增 | 增加"设置不考核"开关 |
| 5 | import | 新增 | 导入 `memberLevelUpdateApi` |
| 6 | methods | 新增 | `handleLevelChange(uid, level)` / `handleNoAssess(uid, flag)` |

**Mock 数据**：用户列表数据中增加 `member_level`、`member_level_name`、`no_assess` 字段

**测试用例**：
- 用户列表显示等级和不考核标记
- 点击调整等级后 member_level 更新
- 设置不考核后 no_assess 标记变更

---

#### 6.2.2 商品管理改造 [MOD]

**原始文件**：商品编辑页面（CRMEB 商品管理模块）

**改造目的**：增加报单商品标记和支付方式配置

**改动点清单**：

| # | 改动位置 | 类型 | 说明 |
|---|----------|------|------|
| 1 | 商品编辑表单 | 新增 | 「报单商品」开关 (is_queue_goods) |
| 2 | 商品编辑表单 | 新增 | 「支付方式」多选框组 (allow_pay_types) |
| 3 | 保存逻辑 | 修改 | 传递 is_queue_goods + allow_pay_types 字段 |
| 4 | 商品列表 | 新增 | 「报单」标记列 |

**Mock 数据**：商品数据增加 `is_queue_goods` 和 `allow_pay_types` 字段

**测试用例**：
- 商品编辑页可勾选报单商品
- 报单商品自动禁用积分支付选项
- 商品列表显示报单标记

---

## 7. Mock 数据总文件

### 7.1 UniApp Mock 数据文件

**文件路径**：`pro_v3.5.1/view/uniapp/utils/hjfMockData.js`

```javascript
/**
 * 黄精粉健康商城 - UniApp Mock 数据集中管理
 * Phase 1 前端开发使用，Phase 4 集成后可移除
 */

// ========== 公排模块 ==========

export const MOCK_QUEUE_STATUS = {
  totalOrders: 156,
  myOrders: [
    {
      id: 1,
      order_id: 'HJF202603100001',
      amount: 3600.00,
      queue_no: 142,
      status: 0,
      refund_time: 0,
      trigger_batch: 0,
      add_time: 1741593600,
      position: 14,
      estimated_wait: '约3天'
    },
    {
      id: 2,
      order_id: 'HJF202603080002',
      amount: 3600.00,
      queue_no: 98,
      status: 1,
      refund_time: 1741507200,
      trigger_batch: 24,
      add_time: 1741420800,
      position: 0,
      estimated_wait: '已退款'
    },
    {
      id: 3,
      order_id: 'HJF202603070003',
      amount: 3600.00,
      queue_no: 85,
      status: 0,
      refund_time: 0,
      trigger_batch: 0,
      add_time: 1741334400,
      position: 46,
      estimated_wait: '约12天'
    }
  ],
  progress: {
    current_batch_count: 2,
    trigger_multiple: 4,
    next_refund_queue_no: 39
  }
};

export const MOCK_QUEUE_HISTORY = {
  list: [
    {
      id: 1,
      order_id: 'HJF202603050001',
      amount: 3600.00,
      queue_no: 45,
      status: 1,
      refund_time: 1741334400,
      trigger_batch: 11,
      add_time: 1741161600,
      time_key: '2026-03-07'
    },
    {
      id: 2,
      order_id: 'HJF202603060002',
      amount: 3600.00,
      queue_no: 67,
      status: 0,
      refund_time: 0,
      trigger_batch: 0,
      add_time: 1741248000,
      time_key: '2026-03-06'
    },
    {
      id: 3,
      order_id: 'HJF202603040003',
      amount: 3600.00,
      queue_no: 33,
      status: 1,
      refund_time: 1741248000,
      trigger_batch: 8,
      add_time: 1741075200,
      time_key: '2026-03-04'
    }
  ],
  count: 25,
  page: 1,
  limit: 15
};

// ========== 资产模块 ==========

export const MOCK_ASSETS_OVERVIEW = {
  now_money: '7200.00',
  frozen_points: 15000,
  available_points: 3200,
  today_release: 6,
  total_queue_refund: '14400.00',
  total_points_earned: 18200,
  member_level: 2,
  member_level_name: '云店'
};

export const MOCK_POINTS_DETAIL = {
  list: [
    {
      id: 1,
      title: '直推奖励 - 用户张三购买报单商品',
      type: 'reward_direct',
      points: 800,
      pm: 1,
      status: 'frozen',
      add_time: '2026-03-10 14:30',
      order_id: 'HJF202603100005'
    },
    {
      id: 2,
      title: '每日释放 - 待释放积分自动解冻',
      type: 'release',
      points: 6,
      pm: 1,
      status: 'released',
      add_time: '2026-03-10 00:00',
      release_date: '2026-03-10'
    },
    {
      id: 3,
      title: '积分消费 - 购买普通商品',
      type: 'consume',
      points: 200,
      pm: 0,
      status: 'released',
      add_time: '2026-03-09 16:22',
      order_id: 'HJF202603090012'
    },
    {
      id: 4,
      title: '伞下奖励 - 用户李四购买报单商品',
      type: 'reward_umbrella',
      points: 300,
      pm: 1,
      status: 'frozen',
      add_time: '2026-03-09 10:15',
      order_id: 'HJF202603090003'
    },
    {
      id: 5,
      title: '每日释放 - 待释放积分自动解冻',
      type: 'release',
      points: 6,
      pm: 1,
      status: 'released',
      add_time: '2026-03-09 00:00',
      release_date: '2026-03-09'
    }
  ],
  count: 45,
  page: 1,
  limit: 15
};

export const MOCK_CASH_DETAIL = {
  list: [
    {
      id: 1,
      title: '公排退款 - 订单HJF202603050001',
      amount: '3600.00',
      pm: 1,
      add_time: '2026-03-07 12:00',
      order_id: 'HJF202603050001'
    },
    {
      id: 2,
      title: '提现 - 微信零钱',
      amount: '930.00',
      pm: 0,
      add_time: '2026-03-06 15:30',
      remark: '手续费¥70.00'
    },
    {
      id: 3,
      title: '购物消费',
      amount: '299.00',
      pm: 0,
      add_time: '2026-03-05 09:20',
      order_id: 'HJF202603050010'
    }
  ],
  count: 12,
  page: 1,
  limit: 15
};

export const MOCK_WITHDRAW_INFO = {
  now_money: '7200.00',
  min_extract: 100,
  fee_rate: 7,
  extract_bank: ['微信零钱', '支付宝', '银行卡'],
  bank_list: [
    { bank_name: '中国工商银行', bank_code: '1234****5678' }
  ]
};

// ========== 会员模块 ==========

export const MOCK_MEMBER_INFO = {
  member_level: 2,
  member_level_name: '云店',
  direct_count: 8,
  umbrella_count: 35,
  umbrella_orders: 42,
  next_level_name: '服务商',
  next_level_require: 100,
  progress_percent: 42
};

export const MOCK_TEAM_DATA = {
  direct_count: 8,
  umbrella_count: 35,
  umbrella_orders: 42,
  members: [
    {
      uid: 10087,
      nickname: '张三',
      avatar: '/static/images/default_avatar.png',
      member_level: 1,
      member_level_name: '创客',
      join_time: '2026-02-15',
      direct_orders: 5,
      is_direct: true
    },
    {
      uid: 10088,
      nickname: '李四',
      avatar: '/static/images/default_avatar.png',
      member_level: 0,
      member_level_name: '普通会员',
      join_time: '2026-03-01',
      direct_orders: 1,
      is_direct: false,
      parent_nickname: '张三'
    },
    {
      uid: 10089,
      nickname: '王五',
      avatar: '/static/images/default_avatar.png',
      member_level: 2,
      member_level_name: '云店',
      join_time: '2026-01-20',
      direct_orders: 12,
      is_direct: true
    }
  ],
  page: 1,
  count: 35
};

export const MOCK_TEAM_INCOME = {
  list: [
    {
      id: 1,
      title: '直推奖励',
      from_uid: 10087,
      from_nickname: '张三',
      order_id: 'HJF202603100005',
      points: 800,
      type: 'direct',
      add_time: '2026-03-10 14:30'
    },
    {
      id: 2,
      title: '伞下奖励(级差)',
      from_uid: 10088,
      from_nickname: '李四',
      order_id: 'HJF202603090003',
      points: 300,
      type: 'umbrella',
      add_time: '2026-03-09 10:15'
    },
    {
      id: 3,
      title: '直推奖励',
      from_uid: 10089,
      from_nickname: '王五',
      order_id: 'HJF202603080010',
      points: 800,
      type: 'direct',
      add_time: '2026-03-08 16:45'
    }
  ],
  count: 22,
  page: 1,
  limit: 15
};

// ========== 引导模块 ==========

export const MOCK_GUIDE_DATA = {
  slides: [
    {
      title: '欢迎来到黄精粉健康商城',
      desc: '健康好物，品质生活',
      image: '/static/images/guide/slide1.png'
    },
    {
      title: '公排返利机制',
      desc: '购买报单商品自动进入公排，每进4单退1单全额返还',
      image: '/static/images/guide/slide2.png'
    },
    {
      title: '会员积分体系',
      desc: '推荐好友即获积分奖励，积分每日自动释放',
      image: '/static/images/guide/slide3.png'
    }
  ]
};
```

### 7.2 Admin Mock 数据文件

**文件路径**：`pro_v3.5.1/view/admin/src/utils/hjfMockData.js`

```javascript
/**
 * 黄精粉健康商城 - Admin Mock 数据集中管理
 * Phase 1 前端开发使用，Phase 4 集成后可移除
 */

// ========== 公排管理 ==========

export const MOCK_QUEUE_ORDER_LIST = {
  list: [
    {
      id: 1,
      uid: 10086,
      nickname: '王五',
      phone: '138****8888',
      order_id: 'HJF202603100001',
      amount: 3600.00,
      queue_no: 142,
      status: 0,
      status_text: '排队中',
      refund_time: '',
      trigger_batch: 0,
      add_time: '2026-03-10 10:00:00'
    },
    {
      id: 2,
      uid: 10087,
      nickname: '张三',
      phone: '139****9999',
      order_id: 'HJF202603080002',
      amount: 3600.00,
      queue_no: 98,
      status: 1,
      status_text: '已退款',
      refund_time: '2026-03-09 12:00:00',
      trigger_batch: 24,
      add_time: '2026-03-08 09:30:00'
    },
    {
      id: 3,
      uid: 10088,
      nickname: '李四',
      phone: '137****7777',
      order_id: 'HJF202603090003',
      amount: 3600.00,
      queue_no: 120,
      status: 0,
      status_text: '排队中',
      refund_time: '',
      trigger_batch: 0,
      add_time: '2026-03-09 14:20:00'
    }
  ],
  count: 156,
  page: 1,
  limit: 20
};

export const MOCK_QUEUE_CONFIG = {
  trigger_multiple: 4,
  release_rate: 4,
  withdraw_fee_rate: 7
};

export const MOCK_QUEUE_FINANCE = {
  list: [
    {
      id: 1,
      uid: 10085,
      nickname: '赵六',
      phone: '136****6666',
      trigger_batch: 24,
      amount: 3600.00,
      queue_no: 39,
      refund_time: '2026-03-09 12:00:00'
    },
    {
      id: 2,
      uid: 10082,
      nickname: '孙七',
      phone: '135****5555',
      trigger_batch: 23,
      amount: 3600.00,
      queue_no: 35,
      refund_time: '2026-03-08 16:30:00'
    }
  ],
  count: 39,
  total_refund: '140400.00',
  page: 1,
  limit: 20
};

// ========== 积分管理 ==========

export const MOCK_POINTS_RELEASE_LOG = {
  list: [
    {
      id: 1,
      uid: 10086,
      nickname: '王五',
      phone: '138****8888',
      frozen_before: 15000,
      release_amount: 6,
      frozen_after: 14994,
      release_date: '2026-03-10',
      add_time: '2026-03-10 00:01:23'
    },
    {
      id: 2,
      uid: 10087,
      nickname: '张三',
      phone: '139****9999',
      frozen_before: 8500,
      release_amount: 3,
      frozen_after: 8497,
      release_date: '2026-03-10',
      add_time: '2026-03-10 00:01:24'
    }
  ],
  count: 500,
  page: 1,
  limit: 20,
  statistics: {
    total_released_today: 2450,
    total_users_released: 320
  }
};

// ========== 会员管理 ==========

export const MOCK_MEMBER_LIST = {
  list: [
    {
      uid: 10086,
      nickname: '王五',
      phone: '138****8888',
      avatar: '',
      member_level: 2,
      member_level_name: '云店',
      no_assess: 0,
      direct_count: 8,
      umbrella_orders: 42,
      frozen_points: 15000,
      available_points: 3200,
      now_money: '7200.00',
      spread_uid: 10001,
      spread_nickname: '推荐人A'
    },
    {
      uid: 10087,
      nickname: '张三',
      phone: '139****9999',
      avatar: '',
      member_level: 1,
      member_level_name: '创客',
      no_assess: 0,
      direct_count: 5,
      umbrella_orders: 8,
      frozen_points: 8500,
      available_points: 1200,
      now_money: '3600.00',
      spread_uid: 10086,
      spread_nickname: '王五'
    },
    {
      uid: 10090,
      nickname: '钱八',
      phone: '134****4444',
      avatar: '',
      member_level: 0,
      member_level_name: '普通会员',
      no_assess: 1,
      direct_count: 0,
      umbrella_orders: 0,
      frozen_points: 0,
      available_points: 0,
      now_money: '0.00',
      spread_uid: 10087,
      spread_nickname: '张三'
    }
  ],
  count: 1200,
  page: 1,
  limit: 20
};

export const MOCK_MEMBER_CONFIG = {
  chuangke_threshold: 3,
  yundian_threshold: 30,
  fuwushang_threshold: 100,
  fengongsi_threshold: 1000,
  chuangke_direct_reward: 500,
  yundian_direct_reward: 800,
  yundian_umbrella_reward: 300,
  fuwushang_direct_reward: 1000,
  fuwushang_umbrella_reward: 200,
  fengongsi_direct_reward: 1300,
  fengongsi_umbrella_reward: 300
};
```

---

## 附录：页面编号映射

| 页面编号 | 页面名称 | 类型 | 文件路径 |
|----------|----------|------|----------|
| P12 | 公排状态页 | NEW | `pages/queue/status.vue` |
| P13 | 公排历史记录 | NEW | `pages/queue/history.vue` |
| P14 | 公排规则说明 | NEW | `pages/queue/rules.vue` |
| P15 | 资产总览 | NEW | `pages/assets/index.vue` |
| P18 | 积分明细 | NEW | `pages/assets/points_detail.vue` |
| P23 | 新用户引导 | NEW | `pages/guide/hjf_intro.vue` |
| P01 | 首页 | MOD | `pages/index/index.vue` |
| P06 | 商品详情 | MOD | `pages/goods_details/index.vue` |
| P08 | 确认订单 | MOD | 订单确认页 |
| P10 | 订单列表 | MOD | 订单列表页 |
| P11 | 订单详情 | MOD | 订单详情页 |
| P04 | 个人中心 | MOD | `pages/user/index.vue` |
| P16 | 提现页 | MOD | `pages/users/user_cash/index.vue` |
| P19 | 推荐收益 | MOD | `pages/users/user_spread_money/index.vue` |
