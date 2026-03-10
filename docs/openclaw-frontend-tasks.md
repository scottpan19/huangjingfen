# 黄精粉健康商城 · 开发任务清单（Agent 执行版）

> **配套文件**：`docs/frontend-new-pages-spec.md`（开发规范）
> **执行主体**：OpenClaw AI Agent
> **任务总数**：74 个（6 阶段）
> **版本**：V2.0 · 2026年3月

---

## 文档说明

### 任务状态

| 状态 | 标记 | 说明 |
|------|------|------|
| pending | `[ ]` | 待执行 |
| in_progress | `[→]` | 执行中 |
| completed | `[✓]` | 已完成 |
| blocked | `[!]` | 被阻塞 |

### 阶段概览

| Phase | 名称 | 任务数 | 前置依赖 |
|-------|------|--------|----------|
| 0 | 基础设施 | 2 | — |
| 1 | 前端开发（Mock 驱动） | 38 | Phase 0 |
| CP-01 | 前端评审检查点 | 1 | Phase 1 全部完成 |
| 2 | 数据库迁移 | 5 | CP-01 |
| 3 | 后端 API 开发 | 16 | Phase 2 |
| 4 | 前后端集成 | 5 | Phase 3 |
| 5 | 测试 | 8 | Phase 4 |

### 并行策略

- 同一 Stage 内标注"可并行"的任务可同时执行
- 不同 Stage 之间有依赖关系，必须按序执行
- Phase 1 完成后必须通过 CP-01 检查点才能进入 Phase 2

---

## Phase 0: 基础设施（2 tasks）

---

### P0-01: 创建 UniApp Mock 数据文件

| 属性 | 值 |
|------|------|
| Phase | 0 |
| Status | `[ ]` pending |
| Dependencies | 无 |
| Output | `pro_v3.5.1/view/uniapp/utils/hjfMockData.js` [NEW] |

**Agent Prompt:**

```
你正在为黄精粉健康商城项目创建 UniApp 端的 Mock 数据集中管理文件。

### 上下文
- 项目底座: CRMEB Pro v3.5，移动端使用 uni-app (Vue 2)
- 此文件为全新创建 [NEW]
- 所有 UniApp 前端页面的 Mock 数据统一在此文件中定义和导出

### 参考文件
- 开发规范: docs/frontend-new-pages-spec.md 第 7.1 节（完整的 Mock 数据定义）

### 开发规范
- 文件路径: pro_v3.5.1/view/uniapp/utils/hjfMockData.js
- 使用 ES6 命名导出 (export const)
- 按模块分区注释（公排/资产/会员/引导）
- 数据具有真实感：合理的金额(3600)、时间戳、状态分布

### 文件内容
按照 docs/frontend-new-pages-spec.md 第 7.1 节完整复制以下导出变量：
- MOCK_QUEUE_STATUS / MOCK_QUEUE_HISTORY
- MOCK_ASSETS_OVERVIEW / MOCK_POINTS_DETAIL / MOCK_CASH_DETAIL / MOCK_WITHDRAW_INFO
- MOCK_MEMBER_INFO / MOCK_TEAM_DATA / MOCK_TEAM_INCOME
- MOCK_GUIDE_DATA

### 验收标准
- [ ] 文件可被 import 正常引用
- [ ] 所有导出变量名与 spec 文档一致
- [ ] 数据字段完整、类型正确
```

---

### P0-02: 创建 Admin Mock 数据文件

| 属性 | 值 |
|------|------|
| Phase | 0 |
| Status | `[ ]` pending |
| Dependencies | 无 |
| Output | `pro_v3.5.1/view/admin/src/utils/hjfMockData.js` [NEW] |

**Agent Prompt:**

```
你正在为黄精粉健康商城项目创建 Admin 端的 Mock 数据集中管理文件。

### 上下文
- 项目底座: CRMEB Pro v3.5，管理后台使用 iView Admin
- 此文件为全新创建 [NEW]

### 参考文件
- 开发规范: docs/frontend-new-pages-spec.md 第 7.2 节（完整的 Admin Mock 数据定义）

### 开发规范
- 文件路径: pro_v3.5.1/view/admin/src/utils/hjfMockData.js
- 使用 ES6 命名导出

### 文件内容
按照 docs/frontend-new-pages-spec.md 第 7.2 节完整复制以下导出变量：
- MOCK_QUEUE_ORDER_LIST / MOCK_QUEUE_CONFIG / MOCK_QUEUE_FINANCE
- MOCK_POINTS_RELEASE_LOG
- MOCK_MEMBER_LIST / MOCK_MEMBER_CONFIG

### 验收标准
- [ ] 文件可被 import 正常引用
- [ ] 所有导出变量名与 spec 文档一致
```

---

## Phase 1: 前端开发（38 tasks）

---

### Stage 1A: API 模块 + Mock 集成（6 tasks，可并行）

---

### P1A-01: UniApp API — hjfQueue.js

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1A |
| Status | `[ ]` pending |
| Dependencies | P0-01 |
| Output | `pro_v3.5.1/view/uniapp/api/hjfQueue.js` [NEW] |

**Agent Prompt:**

```
你正在为黄精粉健康商城创建公排模块的 UniApp API 文件。

### 上下文
- 项目底座: CRMEB Pro v3.5
- 技术栈: uni-app Vue 2
- 当前使用 Mock 数据 (USE_MOCK = true)

### 参考文件
- API 编写规范: pro_v3.5.1/view/uniapp/api/user.js（import request + export function 模式）
- Mock 数据源: pro_v3.5.1/view/uniapp/utils/hjfMockData.js
- 完整规范: docs/frontend-new-pages-spec.md 第 2.2.1 节

### 开发规范
- 文件路径: pro_v3.5.1/view/uniapp/api/hjfQueue.js
- 导入: import request from "@/utils/request.js"
- 导入 Mock: import { MOCK_QUEUE_STATUS, MOCK_QUEUE_HISTORY } from '@/utils/hjfMockData.js'
- 顶部声明: const USE_MOCK = true
- 包含 mockResponse() 辅助函数（返回 Promise + 300ms 延迟 + JSON 深拷贝）

### 导出函数
1. getQueueStatus() — 获取公排状态
2. getQueueHistory(params) — 获取公排历史记录

### 验收标准
- [ ] USE_MOCK = true 时返回 Mock 数据
- [ ] USE_MOCK = false 时调用 request.get()
- [ ] mockResponse 返回与 request 相同的 { status, data } 结构
```

---

### P1A-02: UniApp API — hjfAssets.js

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1A |
| Status | `[ ]` pending |
| Dependencies | P0-01 |
| Output | `pro_v3.5.1/view/uniapp/api/hjfAssets.js` [NEW] |

**Agent Prompt:**

```
你正在创建资产模块的 UniApp API 文件。

### 参考文件
- API 规范: pro_v3.5.1/view/uniapp/api/user.js
- 完整规范: docs/frontend-new-pages-spec.md 第 3.2.1 节

### 开发规范
- 文件路径: pro_v3.5.1/view/uniapp/api/hjfAssets.js
- USE_MOCK = true + mockResponse() 模式

### 导出函数
1. getAssetsOverview() — 资产总览
2. getPointsDetail(params) — 积分明细
3. getCashDetail(params) — 现金流水明细
4. getWithdrawInfo() — 提现信息

### Mock 导入
MOCK_ASSETS_OVERVIEW, MOCK_POINTS_DETAIL, MOCK_CASH_DETAIL, MOCK_WITHDRAW_INFO

### 验收标准
- [ ] 4 个函数均正常导出
- [ ] Mock 模式下返回正确数据
```

---

### P1A-03: UniApp API — hjfMember.js

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1A |
| Status | `[ ]` pending |
| Dependencies | P0-01 |
| Output | `pro_v3.5.1/view/uniapp/api/hjfMember.js` [NEW] |

**Agent Prompt:**

```
你正在创建会员模块的 UniApp API 文件。

### 参考文件
- API 规范: pro_v3.5.1/view/uniapp/api/user.js
- 完整规范: docs/frontend-new-pages-spec.md 第 3.2.2 节

### 文件路径
pro_v3.5.1/view/uniapp/api/hjfMember.js

### 导出函数
1. getMemberInfo() — 会员信息
2. getTeamData(params) — 团队成员列表
3. getTeamIncome(params) — 推荐收益明细

### Mock 导入
MOCK_MEMBER_INFO, MOCK_TEAM_DATA, MOCK_TEAM_INCOME

### 验收标准
- [ ] 3 个函数均正常导出
- [ ] Mock 模式下返回正确数据
```

---

### P1A-04: Admin API — hjfQueue.js

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1A |
| Status | `[ ]` pending |
| Dependencies | P0-02 |
| Output | `pro_v3.5.1/view/admin/src/api/hjfQueue.js` [NEW] |

**Agent Prompt:**

```
你正在创建公排模块的 Admin API 文件。

### 参考文件
- Admin API 规范: pro_v3.5.1/view/admin/src/api/finance.js（import request from '@/plugins/request' + request({url, method, params}) 模式）
- 完整规范: docs/frontend-new-pages-spec.md 第 5.2.1 节

### 文件路径
pro_v3.5.1/view/admin/src/api/hjfQueue.js

### 导出函数
1. queueOrderListApi(data) — 公排订单列表
2. queueConfigGetApi() — 获取公排配置
3. queueConfigSaveApi(data) — 保存公排配置
4. queueFinanceListApi(data) — 公排财务流水

### 注意
- Admin 端使用 request({url, method, params/data}) 模式，不是 request.get()
- Mock 数据从 '@/utils/hjfMockData.js' 导入

### 验收标准
- [ ] 4 个函数均正常导出
- [ ] Admin request 格式正确
```

---

### P1A-05: Admin API — hjfMember.js

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1A |
| Status | `[ ]` pending |
| Dependencies | P0-02 |
| Output | `pro_v3.5.1/view/admin/src/api/hjfMember.js` [NEW] |

**Agent Prompt:**

```
你正在创建会员模块的 Admin API 文件。

### 参考文件
- Admin API 规范: pro_v3.5.1/view/admin/src/api/finance.js
- 完整规范: docs/frontend-new-pages-spec.md 第 5.2.2 节

### 文件路径
pro_v3.5.1/view/admin/src/api/hjfMember.js

### 导出函数
1. memberListApi(data) — 会员管理列表
2. memberLevelUpdateApi(uid, data) — 等级调整
3. memberConfigGetApi() — 获取会员配置
4. memberConfigSaveApi(data) — 保存会员配置

### 验收标准
- [ ] 4 个函数均正常导出
```

---

### P1A-06: Admin API — hjfPoints.js

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1A |
| Status | `[ ]` pending |
| Dependencies | P0-02 |
| Output | `pro_v3.5.1/view/admin/src/api/hjfPoints.js` [NEW] |

**Agent Prompt:**

```
你正在创建积分模块的 Admin API 文件。

### 参考文件
- Admin API 规范: pro_v3.5.1/view/admin/src/api/finance.js
- 完整规范: docs/frontend-new-pages-spec.md 第 5.2.3 节

### 文件路径
pro_v3.5.1/view/admin/src/api/hjfPoints.js

### 导出函数
1. pointsReleaseLogApi(data) — 积分释放日志列表

### 验收标准
- [ ] 函数正常导出
```

---

### Stage 1B: 公共组件（4 tasks，依赖 1A，可并行）

---

### P1B-01: HjfQueueProgress 组件

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1B |
| Status | `[ ]` pending |
| Dependencies | P1A-01 |
| Output | `pro_v3.5.1/view/uniapp/components/HjfQueueProgress.vue` [NEW] |

**Agent Prompt:**

```
你正在创建公排进度组件。

### 上下文
- 技术栈: uni-app Vue 2 Options API
- 此组件为全新创建 [NEW]

### 参考文件
- 组件规范: docs/frontend-new-pages-spec.md 第 2.2.2 节
- 样式参考: pro_v3.5.1/view/uniapp/pages/users/user_money/index.vue（渐变卡片内的统计数据展示）

### 组件功能
- 显示当前批次进度条或环形图（如 2/4）
- 显示下一个退款的排队序号

### Props
- currentCount: Number — 当前批次已入队数
- triggerMultiple: Number — 触发倍数（默认 4）
- nextRefundNo: Number — 下一个退款的 queue_no

### 开发规范
- <style scoped lang="scss">
- 使用 CSS 变量 var(--view-theme) 作为进度条颜色
- rpx 单位

### 验收标准
- [ ] 进度条正确显示 currentCount / triggerMultiple
- [ ] 样式与 CRMEB 一致
```

---

### P1B-02: HjfAssetCard 组件

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1B |
| Status | `[ ]` pending |
| Dependencies | P1A-02 |
| Output | `pro_v3.5.1/view/uniapp/components/HjfAssetCard.vue` [NEW] |

**Agent Prompt:**

```
你正在创建三栏资产展示卡片组件。

### 参考文件
- 组件规范: docs/frontend-new-pages-spec.md 第 3.2.3 节
- 样式参考: pro_v3.5.1/view/uniapp/pages/users/user_money/index.vue（.account 渐变卡片）

### 组件功能
- 渐变背景卡片（linear-gradient 使用 CSS 变量）
- 三栏展示：现金余额(¥) / 待释放积分 / 已释放积分
- 今日预计释放数量

### Props
- nowMoney: String — 现金余额
- frozenPoints: Number — 待释放积分
- availablePoints: Number — 已释放积分
- todayRelease: Number — 今日预计释放

### 验收标准
- [ ] 渐变背景正确渲染
- [ ] 三栏数据正确展示
- [ ] 金额保留两位小数
```

---

### P1B-03: HjfMemberBadge 组件

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1B |
| Status | `[ ]` pending |
| Dependencies | P1A-03 |
| Output | `pro_v3.5.1/view/uniapp/components/HjfMemberBadge.vue` [NEW] |

**Agent Prompt:**

```
你正在创建会员等级徽章组件。

### 参考文件
- 组件规范: docs/frontend-new-pages-spec.md 第 3.2.4 节

### 组件功能
- 显示会员等级图标 + 等级名称
- 支持三种尺寸

### Props
- level: Number — 等级数字 (0-4)
- levelName: String — 等级名称（普通会员/创客/云店/服务商/分公司）
- size: String — 'small' / 'normal' / 'large'

### 等级颜色映射
- 0(普通): 灰色 #999
- 1(创客): 铜色 #CD7F32
- 2(云店): 银色 #C0C0C0
- 3(服务商): 金色 #FFD700
- 4(分公司): 紫色 #8B5CF6

### 验收标准
- [ ] 不同等级显示不同颜色
- [ ] 三种尺寸正确渲染
```

---

### P1B-04: HjfRefundNotice 组件

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1B |
| Status | `[ ]` pending |
| Dependencies | P1A-01 |
| Output | `pro_v3.5.1/view/uniapp/components/HjfRefundNotice.vue` [NEW] |

**Agent Prompt:**

```
你正在创建公排退款通知弹窗组件。

### 参考文件
- 组件规范: docs/frontend-new-pages-spec.md 第 2.2.3 节

### 组件功能
- 弹窗提示：恭喜！您的公排订单已退款
- 显示退款金额和订单号
- 确认按钮关闭弹窗

### Props
- visible: Boolean — 是否显示
- amount: Number — 退款金额
- orderId: String — 订单号

### Events
- @close — 关闭弹窗

### 验收标准
- [ ] 弹窗显示/隐藏正常
- [ ] 金额格式正确（¥3,600.00）
```

---

### Stage 1C: 新 UniApp 页面（6 tasks，依赖 1A+1B，可并行）

---

### P1C-01: P12 公排状态页

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1C |
| Status | `[ ]` pending |
| Dependencies | P1A-01, P1B-01 |
| Output | `pro_v3.5.1/view/uniapp/pages/queue/status.vue` [NEW] |

**Agent Prompt:**

```
你正在为黄精粉健康商城创建公排状态页面。

### 上下文
- 项目底座: CRMEB Pro v3.5
- 技术栈: UniApp Vue2 Options API
- 此页面为全新创建 [NEW]
- 数据来源: api/hjfQueue.js 中的 getQueueStatus()
- 当前使用 Mock 数据 (USE_MOCK=true)

### 参考文件
- 页面布局参考: pro_v3.5.1/view/uniapp/pages/users/user_money/index.vue（渐变卡片头部 + 导航卡片结构）
- 列表翻页参考: pro_v3.5.1/view/uniapp/pages/users/user_bill/index.vue（page/limit/onReachBottom 模式）
- 组件引用: components/HjfQueueProgress.vue
- 完整规范: docs/frontend-new-pages-spec.md 第 2.2.4 节

### 开发规范
- 文件路径: pro_v3.5.1/view/uniapp/pages/queue/status.vue
- 样式: <style scoped lang="scss">
- 组件命名: Hjf 前缀
- 金额展示: 保留两位小数

### 页面功能
1. 顶部渐变卡片: 显示公排池总单数 + 当前进度 (HjfQueueProgress)
2. 我的排队列表: 显示 myOrders 数组
   - 每条: 排队序号 | 金额(¥3600.00) | 状态标签 | 预计等待
   - status=0 → 绿色"排队中"标签
   - status=1 → 灰色"已退款"标签
3. 底部上拉加载更多 + 空状态

### Mock 数据
从 api/hjfQueue.js 的 getQueueStatus() 获取，数据结构见 spec 文档 2.2.4 节

### 验收标准
- [ ] 页面渲染无报错
- [ ] Mock 数据正确展示（总单数、列表、进度条）
- [ ] 状态标签颜色正确
- [ ] 样式与 CRMEB 页面风格一致
```

---

### P1C-02: P13 公排历史页

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1C |
| Status | `[ ]` pending |
| Dependencies | P1A-01 |
| Output | `pro_v3.5.1/view/uniapp/pages/queue/history.vue` [NEW] |

**Agent Prompt:**

```
你正在创建公排历史记录页面。

### 参考文件
- 列表页模式: pro_v3.5.1/view/uniapp/pages/users/user_bill/index.vue（Tab 筛选 + 按日期分组 + 上拉加载）
- 完整规范: docs/frontend-new-pages-spec.md 第 2.2.5 节

### 文件路径
pro_v3.5.1/view/uniapp/pages/queue/history.vue

### 页面功能
1. Tab 筛选: 全部 / 排队中(status=0) / 已退款(status=1)
2. 按日期分组的列表（使用 time_key 字段分组）
3. 每条记录: 订单号 | 金额 | 退款时间 | 批次号
4. 上拉加载更多 (onReachBottom)

### Mock 数据
从 api/hjfQueue.js 的 getQueueHistory() 获取

### 验收标准
- [ ] Tab 切换正常过滤
- [ ] 按日期分组展示
- [ ] 上拉加载更多正常
```

---

### P1C-03: P14 公排规则页

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1C |
| Status | `[ ]` pending |
| Dependencies | 无 |
| Output | `pro_v3.5.1/view/uniapp/pages/queue/rules.vue` [NEW] |

**Agent Prompt:**

```
你正在创建公排规则说明页面（纯静态页面）。

### 参考文件
- 完整规范: docs/frontend-new-pages-spec.md 第 2.2.6 节
- 业务规则: docs/PRD_V2.md 第 3.1 节（公排机制规则）

### 文件路径
pro_v3.5.1/view/uniapp/pages/queue/rules.vue

### 页面功能
1. 公排机制图示（进四退一流程图，可用 CSS 绘制简单流程）
2. 规则条款列表（有序列表）
3. 常见问题 FAQ 手风琴（点击展开/折叠）

### 内容要点
- 每笔报单商品订单 3600 元，付款后进入公排池
- 全局按付款时间排序，每进 4 单退 1 单
- 退款金额进入现金余额，可提现（7%手续费）
- 一次买多单拆分为独立订单

### 验收标准
- [ ] 规则内容完整准确
- [ ] FAQ 手风琴交互正常
- [ ] 无需后端 API
```

---

### P1C-04: P15 资产总览页

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1C |
| Status | `[ ]` pending |
| Dependencies | P1A-02, P1B-02 |
| Output | `pro_v3.5.1/view/uniapp/pages/assets/index.vue` [NEW] |

**Agent Prompt:**

```
你正在创建资产总览页面。

### 参考文件
- 页面布局: pro_v3.5.1/view/uniapp/pages/users/user_money/index.vue（渐变卡片 + 导航卡片 + 推荐区）
- 完整规范: docs/frontend-new-pages-spec.md 第 3.2.5 节

### 文件路径
pro_v3.5.1/view/uniapp/pages/assets/index.vue

### 页面功能
1. HjfAssetCard 三栏资产卡片（渐变背景）
2. 快捷导航卡片:
   - 现金余额 → 提现页 (pages/users/user_cash/index)
   - 待释放积分 → 积分明细 (pages/assets/points_detail)
   - 已释放积分 → 积分明细
   - 公排记录 → 公排状态页 (pages/queue/status)
3. 今日释放提示

### Mock 数据
从 api/hjfAssets.js 的 getAssetsOverview() 获取

### 验收标准
- [ ] 三栏资产正确展示
- [ ] 导航卡片点击跳转正确
- [ ] 渐变背景与 CRMEB 风格一致
```

---

### P1C-05: P18 积分明细页

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1C |
| Status | `[ ]` pending |
| Dependencies | P1A-02 |
| Output | `pro_v3.5.1/view/uniapp/pages/assets/points_detail.vue` [NEW] |

**Agent Prompt:**

```
你正在创建积分明细页面。

### 参考文件
- 列表页模式: pro_v3.5.1/view/uniapp/pages/users/user_bill/index.vue（Tab 筛选 + 分组列表 + 翻页）
- 完整规范: docs/frontend-new-pages-spec.md 第 3.2.6 节

### 文件路径
pro_v3.5.1/view/uniapp/pages/assets/points_detail.vue

### 页面功能
1. Tab 筛选: 全部 / 直推奖励(reward_direct) / 伞下奖励(reward_umbrella) / 每日释放(release) / 消费(consume)
2. 每条: 标题 | 积分数(± 前缀) | 状态(待释放/已释放) | 时间
3. pm=1 显示绿色 +，pm=0 显示红色 -
4. 上拉加载更多

### Mock 数据
从 api/hjfAssets.js 的 getPointsDetail() 获取

### 验收标准
- [ ] Tab 筛选正确过滤
- [ ] 积分收支方向颜色正确
- [ ] 按日期分组展示
```

---

### P1C-06: P23 新用户引导页

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1C |
| Status | `[ ]` pending |
| Dependencies | P0-01 |
| Output | `pro_v3.5.1/view/uniapp/pages/guide/hjf_intro.vue` [NEW] |

**Agent Prompt:**

```
你正在创建新用户引导页面。

### 参考文件
- 完整规范: docs/frontend-new-pages-spec.md 第 4.2.1 节

### 文件路径
pro_v3.5.1/view/uniapp/pages/guide/hjf_intro.vue

### 页面功能
1. 轮播引导（swiper 组件，3 屏）
   - 第1屏: 平台介绍
   - 第2屏: 公排规则图示
   - 第3屏: 会员积分说明 + "立即开始"按钮
2. 底部指示器
3. 右上角"跳过"按钮
4. navigationStyle: custom（自定义导航栏）

### Mock 数据
从 hjfMockData.js 的 MOCK_GUIDE_DATA 获取幻灯片配置

### 验收标准
- [ ] 轮播滑动正常
- [ ] 跳过/立即开始按钮跳转首页
- [ ] 自定义导航栏适配刘海屏
```

---

### Stage 1D: 改造 UniApp 页面（7 tasks，依赖 1A+1B）

---

### P1D-01: 首页改造

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1D |
| Status | `[ ]` pending |
| Dependencies | P1A-01 |
| Output | `pro_v3.5.1/view/uniapp/pages/index/index.vue` [MOD] |

**Agent Prompt:**

```
你正在改造首页，在商品卡片上增加报单商品角标。

### 参考文件
- 改造规范: docs/frontend-new-pages-spec.md 第 6.1.1 节
- 原始文件: pro_v3.5.1/view/uniapp/pages/index/index.vue

### 改动点
1. 在商品卡片 template 中根据 is_queue_goods 字段显示"参与公排"角标
2. 新增 .queue-badge 样式（绝对定位右上角，绿色/金色背景）

### 注意事项
- 首页使用 DIY 架构，商品卡片可能通过组件渲染
- 不要修改 DIY 核心逻辑，仅在商品卡片层增加角标判断
- 保持现有功能完整不变

### 验收标准
- [ ] 报单商品显示角标
- [ ] 普通商品不显示角标
- [ ] 原有功能不受影响
```

---

### P1D-02: 商品详情改造

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1D |
| Status | `[ ]` pending |
| Dependencies | P1A-01 |
| Output | 商品详情页 [MOD] |

**Agent Prompt:**

```
你正在改造商品详情页，增加支付方式选择功能。

### 参考文件
- 改造规范: docs/frontend-new-pages-spec.md 第 6.1.2 节

### 改动点
1. 购买弹窗中增加支付方式选择区域（单选按钮组）
2. data() 新增 payMethod: 'wechat'
3. 提交订单时传递 pay_type 参数
4. 报单商品显示"公排商品"标签
5. 报单商品不显示积分支付选项

### 验收标准
- [ ] 报单商品只显示微信/支付宝/余额
- [ ] 普通商品根据 allow_pay_types 显示
- [ ] 选择支付方式后正确传参
```

---

### P1D-03: 购买流程改造

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1D |
| Status | `[ ]` pending |
| Dependencies | P1A-01 |
| Output | 订单确认页 + 支付成功页 [MOD] |

**Agent Prompt:**

```
你正在改造购买流程，增加多单拆分提示和公排入队提示。

### 参考文件
- 改造规范: docs/frontend-new-pages-spec.md 第 6.1.3 节

### 改动点
1. 订单确认页: 报单商品数量>1 时显示"将拆分为 N 个独立公排订单"提示
2. 支付成功页: 报单商品支付成功后显示"已加入公排"提示 + 跳转公排状态按钮

### 验收标准
- [ ] 多单提示正确显示
- [ ] 支付成功后公排提示正确
- [ ] 普通商品不显示公排相关提示
```

---

### P1D-04: 我的订单改造

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1D |
| Status | `[ ]` pending |
| Dependencies | P1A-01 |
| Output | 订单详情页 [MOD] |

**Agent Prompt:**

```
你正在改造订单详情页，增加公排状态展示。

### 参考文件
- 改造规范: docs/frontend-new-pages-spec.md 第 6.1.4 节

### 改动点
1. 订单详情 template 新增公排状态区域（排队序号/状态/预计等待）
2. data() 新增 queueInfo 对象
3. 调用 getQueueStatus() 获取该订单的公排信息（Mock 阶段用固定数据）

### 验收标准
- [ ] 报单商品订单显示公排状态
- [ ] 普通商品订单不显示公排区域
```

---

### P1D-05: 推荐页改造

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1D |
| Status | `[ ]` pending |
| Dependencies | P1A-03 |
| Output | `pro_v3.5.1/view/uniapp/pages/users/user_spread_money/index.vue` [MOD] |

**Agent Prompt:**

```
你正在改造推荐收益页面，将佣金替换为积分奖励。

### 参考文件
- 改造规范: docs/frontend-new-pages-spec.md 第 6.1.5 节
- 原始文件: pro_v3.5.1/view/uniapp/pages/users/user_spread_money/index.vue

### 改动点
1. 页面标题: "推广佣金" → "推荐收益"
2. 金额展示: 佣金金额(¥) → 积分数量（不带¥符号）
3. API 调用: 替换为 getTeamIncome()
4. 列表项: 显示积分来源(直推/伞下) + 积分状态(待释放)

### 验收标准
- [ ] 标题显示"推荐收益"
- [ ] 列表显示积分而非金额
- [ ] Mock 数据正确展示
```

---

### P1D-06: 提现页改造

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1D |
| Status | `[ ]` pending |
| Dependencies | P1A-02 |
| Output | `pro_v3.5.1/view/uniapp/pages/users/user_cash/index.vue` [MOD] |

**Agent Prompt:**

```
你正在改造提现页面，增加7%手续费实时计算。

### 参考文件
- 改造规范: docs/frontend-new-pages-spec.md 第 6.1.6 节
- 原始文件: pro_v3.5.1/view/uniapp/pages/users/user_cash/index.vue

### 改动点
1. 提现金额输入区域下方新增: 「手续费：¥XX.XX（7%）| 实际到账：¥XX.XX」
2. computed 新增: feeAmount = 输入金额 × 0.07, actualAmount = 输入金额 × 0.93
3. 提交逻辑传递 fee_rate 参数

### 验收标准
- [ ] 输入 1000 → 显示手续费 ¥70.00 / 实际到账 ¥930.00
- [ ] 金额低于最低提现额时提示
- [ ] 手续费实时更新
```

---

### P1D-07: 个人中心改造

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1D |
| Status | `[ ]` pending |
| Dependencies | P1A-03, P1B-03 |
| Output | `pro_v3.5.1/view/uniapp/pages/user/index.vue` [MOD] |

**Agent Prompt:**

```
你正在改造个人中心页面，增加会员等级徽章和资产入口。

### 参考文件
- 改造规范: docs/frontend-new-pages-spec.md 第 6.1.7 节
- 原始文件: pro_v3.5.1/view/uniapp/pages/user/index.vue（DIY 架构）

### 改动点
1. 用户信息区: 引入 HjfMemberBadge 显示会员等级
2. DIY 菜单数据: 增加"我的资产"、"公排查询"导航项
3. import: 导入 getMemberInfo() 和 HjfMemberBadge
4. onShow: 合并调用 getMemberInfo() 获取等级信息

### 注意事项
- 个人中心使用 DIY 架构（数据驱动），需理解 diyData 结构
- 使用 provide/inject 模式传递方法
- 不要破坏现有的 DIY 菜单结构

### 验收标准
- [ ] 会员等级徽章正确显示
- [ ] "我的资产"点击跳转 pages/assets/index
- [ ] "公排查询"点击跳转 pages/queue/status
- [ ] 原有 DIY 功能不受影响
```

---

### Stage 1E: Admin 新页面（6 tasks，依赖 1A，可并行）

---

### P1E-01: 公排订单管理页

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1E |
| Status | `[ ]` pending |
| Dependencies | P1A-04 |
| Output | `pro_v3.5.1/view/admin/src/pages/hjf/queueOrder/index.vue` [NEW] |

**Agent Prompt:**

```
你正在创建公排订单管理页面。

### 参考文件
- 列表页模式: pro_v3.5.1/view/admin/src/pages/finance/commission/index.vue（Card + Form + Table + Page 模式）
- API: pro_v3.5.1/view/admin/src/api/hjfQueue.js
- 完整规范: docs/frontend-new-pages-spec.md 第 5.2.4 节

### 文件路径
pro_v3.5.1/view/admin/src/pages/hjf/queueOrder/index.vue

### 页面功能
1. 搜索区: 用户昵称/ID + 状态筛选(全部/排队中/已退款) + 日期范围
2. 数据表格: columns 见 spec 5.2.4 节
3. 分页组件
4. 导出功能（可选）

### formValidate 结构
{ nickname: '', status: '', start_time: '', end_time: '', page: 1, limit: 20 }

### 验收标准
- [ ] 表格正确展示 Mock 数据
- [ ] 搜索筛选正常工作
- [ ] 分页正常
```

---

### P1E-02: 公排财务流水页

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1E |
| Status | `[ ]` pending |
| Dependencies | P1A-04 |
| Output | `pro_v3.5.1/view/admin/src/pages/hjf/queueFinance/index.vue` [NEW] |

**Agent Prompt:**

```
你正在创建公排财务流水页面。

### 参考文件
- 列表页模式: pro_v3.5.1/view/admin/src/pages/finance/commission/index.vue
- 完整规范: docs/frontend-new-pages-spec.md 第 5.2.5 节

### 文件路径
pro_v3.5.1/view/admin/src/pages/hjf/queueFinance/index.vue

### 页面功能
1. 搜索区: 用户昵称/ID + 日期范围
2. 顶部统计: 退款总额
3. 数据表格: 用户 | 批次号 | 金额 | 排队序号 | 退款时间

### 验收标准
- [ ] 表格展示退款流水
- [ ] 统计数据正确
```

---

### P1E-03: 积分释放日志页

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1E |
| Status | `[ ]` pending |
| Dependencies | P1A-06 |
| Output | `pro_v3.5.1/view/admin/src/pages/hjf/pointsLog/index.vue` [NEW] |

**Agent Prompt:**

```
你正在创建积分释放日志页面。

### 参考文件
- 列表页模式: pro_v3.5.1/view/admin/src/pages/finance/commission/index.vue
- 完整规范: docs/frontend-new-pages-spec.md 第 5.2.6 节

### 文件路径
pro_v3.5.1/view/admin/src/pages/hjf/pointsLog/index.vue

### 页面功能
1. 搜索区: 用户昵称/ID + 日期范围
2. 顶部统计: 今日释放总量 / 今日释放用户数
3. 数据表格: 用户 | 释放前 | 释放量 | 释放后 | 日期

### 验收标准
- [ ] 表格正确展示
- [ ] 顶部统计 statistics 字段正确显示
```

---

### P1E-04: 公排参数配置页

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1E |
| Status | `[ ]` pending |
| Dependencies | P1A-04 |
| Output | `pro_v3.5.1/view/admin/src/pages/hjf/queueConfig/index.vue` [NEW] |

**Agent Prompt:**

```
你正在创建公排参数配置页面（表单页，非列表页）。

### 参考文件
- 完整规范: docs/frontend-new-pages-spec.md 第 5.2.7 节

### 文件路径
pro_v3.5.1/view/admin/src/pages/hjf/queueConfig/index.vue

### 页面功能
1. 表单项:
   - 公排触发倍数 (InputNumber, 默认 4)
   - 积分日释放比例 (InputNumber, 千分之 X, 默认 4)
   - 提现手续费率 (InputNumber, 百分比, 默认 7)
2. 保存按钮 → 调用 queueConfigSaveApi()
3. 页面加载时调用 queueConfigGetApi() 填充表单

### 验收标准
- [ ] 表单正确加载 Mock 配置数据
- [ ] 保存按钮调用正确 API
- [ ] 数值输入合法性校验
```

---

### P1E-05: 会员等级配置页

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1E |
| Status | `[ ]` pending |
| Dependencies | P1A-05 |
| Output | `pro_v3.5.1/view/admin/src/pages/hjf/memberConfig/index.vue` [NEW] |

**Agent Prompt:**

```
你正在创建会员等级配置页面（表单页）。

### 参考文件
- 完整规范: docs/frontend-new-pages-spec.md 第 5.2.8 节

### 文件路径
pro_v3.5.1/view/admin/src/pages/hjf/memberConfig/index.vue

### 页面功能
1. 分区表单:
   - 升级门槛区: 创客/云店/服务商/分公司 各自的门槛值
   - 直推奖励区: 各等级直推积分奖励
   - 伞下奖励区: 各等级伞下积分奖励
2. 保存按钮 → 调用 memberConfigSaveApi()
3. 页面加载时调用 memberConfigGetApi()

### 验收标准
- [ ] 表单正确加载 Mock 配置
- [ ] 所有等级配置项可编辑
```

---

### P1E-06: 会员管理页

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1E |
| Status | `[ ]` pending |
| Dependencies | P1A-05 |
| Output | `pro_v3.5.1/view/admin/src/pages/hjf/memberLevel/index.vue` [NEW] |

**Agent Prompt:**

```
你正在创建会员管理页面。

### 参考文件
- 列表页模式: pro_v3.5.1/view/admin/src/pages/finance/commission/index.vue
- 完整规范: docs/frontend-new-pages-spec.md 第 5.2.9 节

### 文件路径
pro_v3.5.1/view/admin/src/pages/hjf/memberLevel/index.vue

### 页面功能
1. 搜索区: 昵称/ID + 等级筛选(下拉) + 不考核筛选
2. 数据表格: 用户信息 | 等级 | 直推数 | 伞下业绩 | 待释放积分 | 已释放积分 | 余额 | 操作
3. 操作列: 调整等级(弹窗选择) / 设置不考核(确认弹窗)
4. 弹窗: 等级选择 Select + 确认按钮

### 验收标准
- [ ] 表格正确展示会员列表
- [ ] 等级筛选正常
- [ ] 等级调整弹窗可用
```

---

### Stage 1F: 路由注册（7 tasks）

---

### P1F-01: UniApp pages.json — 公排模块

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1F |
| Status | `[ ]` pending |
| Dependencies | P1C-01, P1C-02, P1C-03 |
| Output | `pro_v3.5.1/view/uniapp/pages.json` [MOD] |

**Agent Prompt:**

```
在 pages.json 中注册公排模块的 3 个页面路由。

### 新增路由
参考 docs/frontend-new-pages-spec.md 第 2.2.7 节:
- pages/queue/status (公排状态)
- pages/queue/history (公排记录)
- pages/queue/rules (公排规则)

### 注意
- 可以在 subPackages 中创建新的分包，或在 pages 数组中直接添加
- 保持与现有路由格式一致

### 验收标准
- [ ] 三个页面路由可正常访问
```

---

### P1F-02: UniApp pages.json — 资产模块

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1F |
| Status | `[ ]` pending |
| Dependencies | P1C-04, P1C-05 |
| Output | `pro_v3.5.1/view/uniapp/pages.json` [MOD] |

**Agent Prompt:**

```
在 pages.json 中注册资产模块的 2 个页面路由。

### 新增路由
参考 docs/frontend-new-pages-spec.md 第 3.2.7 节:
- pages/assets/index (我的资产)
- pages/assets/points_detail (积分明细)

### 验收标准
- [ ] 两个页面路由可正常访问
```

---

### P1F-03: UniApp pages.json — 引导页

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1F |
| Status | `[ ]` pending |
| Dependencies | P1C-06 |
| Output | `pro_v3.5.1/view/uniapp/pages.json` [MOD] |

**Agent Prompt:**

```
在 pages.json 中注册引导页路由。

### 新增路由
参考 docs/frontend-new-pages-spec.md 第 4.2.2 节:
- pages/guide/hjf_intro (新用户引导, navigationStyle: custom)

### 验收标准
- [ ] 引导页路由可正常访问
- [ ] 自定义导航栏生效
```

---

### P1F-04: Admin 路由 — hjfQueue.js

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1F |
| Status | `[ ]` pending |
| Dependencies | P1E-01, P1E-02, P1E-04 |
| Output | `pro_v3.5.1/view/admin/src/router/modules/hjfQueue.js` [NEW] |

**Agent Prompt:**

```
创建 Admin 端 HJF 模块的路由文件。

### 参考文件
- 路由规范: pro_v3.5.1/view/admin/src/router/modules/finance.js（BasicLayout + pre 前缀 + auth 权限）
- 完整规范: docs/frontend-new-pages-spec.md 第 5.2.10 节

### 文件路径
pro_v3.5.1/view/admin/src/router/modules/hjfQueue.js

### 路由定义
按照 spec 5.2.10 节完整定义，包含 6 个子路由:
- hjf/queue/order (公排订单)
- hjf/queue/finance (公排财务)
- hjf/queue/config (公排配置)
- hjf/points/log (积分日志)
- hjf/member/config (会员配置)
- hjf/member/level (会员管理)

### 验收标准
- [ ] 路由格式与现有 finance.js 一致
- [ ] 使用 BasicLayout + 动态 import
```

---

### P1F-05: Admin 路由模块注册

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1F |
| Status | `[ ]` pending |
| Dependencies | P1F-04 |
| Output | `pro_v3.5.1/view/admin/src/router/modules/index.js` [MOD] |

**Agent Prompt:**

```
在 Admin 路由入口文件中导入并注册 hjfQueue 路由模块。

### 参考文件
- 路由入口: pro_v3.5.1/view/admin/src/router/modules/index.js（查看现有路由模块如何注册）

### 改动点
1. 导入 hjfQueue 模块: import hjfQueue from './hjfQueue'
2. 在导出的路由数组中加入 hjfQueue

### 验收标准
- [ ] HJF 路由模块正常加载
- [ ] 菜单中可见 HJF 相关页面
```

---

### P1F-06: Admin finance.js 路由追加（如需要）

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1F |
| Status | `[ ]` pending |
| Dependencies | P1E-03 |
| Output | 评估是否需要修改 `router/modules/finance.js` [评估] |

**Agent Prompt:**

```
评估是否需要在现有 finance.js 路由中追加积分日志页路由。

### 判断依据
- 如果 P1F-04 已经将所有 HJF 页面（包括积分日志）放在统一的 hjfQueue.js 路由模块中，则此任务无需执行
- 如果积分日志更适合放在 finance 模块下，则在 finance.js 的 children 中追加子路由

### 验收标准
- [ ] 确认积分日志页路由可访问
```

---

### P1F-07: Admin user.js 路由追加（如需要）

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1F |
| Status | `[ ]` pending |
| Dependencies | P1E-06 |
| Output | 评估是否需要修改 `router/modules/user.js` [评估] |

**Agent Prompt:**

```
评估是否需要在现有 user.js 路由中追加会员管理页路由。

### 判断依据
- 如果 P1F-04 已经将会员管理放在 hjfQueue.js 路由模块中，则此任务无需执行
- 如果会员管理更适合放在 user 模块下，则追加

### 验收标准
- [ ] 确认会员管理页路由可访问
```

---

### Stage 1G: Admin 改造页面（2 tasks）

---

### P1G-01: 用户管理改造

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1G |
| Status | `[ ]` pending |
| Dependencies | P1A-05 |
| Output | `pro_v3.5.1/view/admin/src/pages/user/list/index.vue` [MOD] |

**Agent Prompt:**

```
你正在改造 Admin 用户管理页面，增加等级调整和不考核标记。

### 参考文件
- 改造规范: docs/frontend-new-pages-spec.md 第 6.2.1 节
- 原始文件: pro_v3.5.1/view/admin/src/pages/user/list/index.vue

### 改动点
1. columns 新增: "会员等级"列（slot 渲染等级名称）
2. columns 新增: "不考核"列（Tag 标记）
3. 操作列新增: "调整等级"按钮（弹窗 Select）
4. 操作列新增: "设置不考核"开关
5. import 新增: memberLevelUpdateApi
6. methods 新增: handleLevelChange / handleNoAssess

### 注意事项
- 不要删除现有的操作按钮
- 等级弹窗使用 iView Modal + Select 组件

### 验收标准
- [ ] 等级列正确显示
- [ ] 调整等级弹窗可用
- [ ] 原有功能不受影响
```

---

### P1G-02: 商品管理改造

| 属性 | 值 |
|------|------|
| Phase | 1 |
| Stage | 1G |
| Status | `[ ]` pending |
| Dependencies | P1A-04 |
| Output | 商品编辑页面 [MOD] |

**Agent Prompt:**

```
你正在改造 Admin 商品管理页面，增加报单商品标记和支付方式配置。

### 参考文件
- 改造规范: docs/frontend-new-pages-spec.md 第 6.2.2 节

### 改动点
1. 商品编辑表单新增: "报单商品"开关 (is_queue_goods, Switch 组件)
2. 商品编辑表单新增: "支付方式"多选框组 (allow_pay_types, CheckboxGroup)
3. 保存逻辑: 传递 is_queue_goods + allow_pay_types
4. 商品列表新增: "报单"标记列

### 规则
- 当 is_queue_goods=1 时，自动禁用积分支付选项

### 验收标准
- [ ] 报单商品开关可切换
- [ ] 支付方式多选框正确联动
- [ ] 商品列表显示报单标记
```

---

## ★ CHECKPOINT CP-01: 前端评审确认 ★

| 属性 | 值 |
|------|------|
| Phase | CP |
| Status | `[ ]` pending |
| Dependencies | Phase 1 全部完成 |
| Gate | 用户确认后才能进入 Phase 2 |

**检查清单**：

- [ ] **UniApp 新页面**：P12/P13/P14/P15/P18/P23 均可用 Mock 数据浏览
- [ ] **UniApp 改造页面**：首页角标、商品详情支付方式、订单公排状态、推荐积分、提现手续费、个人中心徽章 均正常显示
- [ ] **Admin 新页面**：6 个管理页面均可用 Mock 数据浏览
- [ ] **Admin 改造页面**：用户管理等级列、商品管理报单标记 均正常
- [ ] **路由**：所有新页面路由可正常访问
- [ ] **组件**：HjfQueueProgress / HjfAssetCard / HjfMemberBadge / HjfRefundNotice 均正常渲染

**确认后**：用户在此处标记 `[✓]`，解锁 Phase 2。

---

## Phase 2: 数据库迁移（5 tasks，依赖 CP-01）

---

### P2-01: 创建 eb_queue_pool 表

| 属性 | 值 |
|------|------|
| Phase | 2 |
| Status | `[ ]` pending |
| Dependencies | CP-01 |
| Output | `database/migrations/20260307_create_eb_queue_pool.php` [NEW] |

**Agent Prompt:**

```
创建公排池数据库表。

### SQL
参考 docs/frontend-new-pages-spec.md 第 2.1.1 节的建表 SQL。

### 索引
- idx_uid(uid)
- idx_status_queue_no(status, queue_no)
- idx_trigger_batch(trigger_batch)
- idx_add_time(add_time)

### 验收标准
- [ ] 迁移文件可正常执行
- [ ] 表结构与 spec 一致
```

---

### P2-02: 创建 eb_points_release_log 表

| 属性 | 值 |
|------|------|
| Phase | 2 |
| Status | `[ ]` pending |
| Dependencies | CP-01 |
| Output | `database/migrations/20260307_create_eb_points_release_log.php` [NEW] |

**Agent Prompt:**

```
创建积分释放日志表。

### SQL
参考 docs/frontend-new-pages-spec.md 第 3.1.2 节的建表 SQL。

### 验收标准
- [ ] 迁移文件可正常执行
```

---

### P2-03: ALTER eb_user 添加 4 字段

| 属性 | 值 |
|------|------|
| Phase | 2 |
| Status | `[ ]` pending |
| Dependencies | CP-01 |
| Output | `database/migrations/20260307_alter_eb_user_add_hjf_fields.php` [NEW] |

**Agent Prompt:**

```
为 eb_user 表添加 4 个新字段。

### SQL
参考 docs/frontend-new-pages-spec.md 第 3.1.1 节:
- member_level TINYINT DEFAULT 0
- no_assess TINYINT DEFAULT 0
- frozen_points BIGINT DEFAULT 0
- available_points BIGINT DEFAULT 0

### 验收标准
- [ ] ALTER 语句正常执行
- [ ] 现有数据不受影响
```

---

### P2-04: INSERT eb_system_config 配置项

| 属性 | 值 |
|------|------|
| Phase | 2 |
| Status | `[ ]` pending |
| Dependencies | CP-01 |
| Output | `database/migrations/20260307_insert_eb_system_config_hjf.php` [NEW] |

**Agent Prompt:**

```
向 eb_system_config 表插入 HJF 配置项。

### 配置项
参考 docs/PRD_V2.md 第 5.7 节:
- hjf_trigger_multiple = 4
- hjf_release_rate = 4
- hjf_withdraw_fee_rate = 7
- hjf_chuangke_threshold = 3
- hjf_yundian_threshold = 30
- hjf_fuwushang_threshold = 100
- hjf_fengongsi_threshold = 1000
- 各等级奖励积分配置

### 验收标准
- [ ] 配置项正确插入
- [ ] 不影响现有配置
```

---

### P2-05: ALTER eb_store_product 添加 is_queue_goods

| 属性 | 值 |
|------|------|
| Phase | 2 |
| Status | `[ ]` pending |
| Dependencies | CP-01 |
| Output | `database/migrations/20260307_alter_eb_store_product.php` [NEW] |

**Agent Prompt:**

```
为 eb_store_product 表添加报单商品标记字段。

### SQL
ALTER TABLE eb_store_product
  ADD COLUMN is_queue_goods TINYINT NOT NULL DEFAULT 0 COMMENT '报单商品标记: 0否 1是',
  ADD COLUMN allow_pay_types VARCHAR(255) NOT NULL DEFAULT 'wechat,alipay' COMMENT '允许的支付方式';

### 验收标准
- [ ] 字段正确添加
- [ ] 默认值合理
```

---

## Phase 3: 后端 API 开发（16 tasks，依赖 Phase 2）

---

### P3-01: QueuePool Model

| 属性 | 值 |
|------|------|
| Phase | 3 |
| Status | `[ ]` pending |
| Dependencies | P2-01 |
| Output | `app/model/queue/QueuePool.php` [NEW] |

**Agent Prompt:**

```
创建 QueuePool 模型。参考 CRMEB 现有 Model 模式（继承 BaseModel，定义 $table, $fillable 等）。
表名: eb_queue_pool
```

---

### P3-02: QueuePoolDao

| 属性 | 值 |
|------|------|
| Phase | 3 |
| Status | `[ ]` pending |
| Dependencies | P3-01 |
| Output | `app/dao/queue/QueuePoolDao.php` [NEW] |

**Agent Prompt:**

```
创建 QueuePool 数据访问层。参考 CRMEB 现有 Dao 模式。
方法: getList / getByUid / getByStatus / getNextRefund / incrementQueueNo
```

---

### P3-03: QueuePoolService

| 属性 | 值 |
|------|------|
| Phase | 3 |
| Status | `[ ]` pending |
| Dependencies | P3-02 |
| Output | `app/services/queue/QueuePoolService.php` [NEW] |

**Agent Prompt:**

```
创建公排核心业务服务。

### 方法
- addToQueue(uid, orderId, amount) — 入队
- checkAndTriggerRefund() — 检查并触发退款（进N退1逻辑）
- getQueueStatus(uid) — 获取用户排队状态
- getQueueHistory(uid, page, limit) — 获取历史记录

### 技术要点
- 使用 Redis 分布式锁 (hjf:queue:refund:lock)
- Redis 计数器 (hjf:queue:counter) 全局排队序号
- 退款通过 think-queue 异步处理
- 所有金额用 bcmath 计算
```

---

### P3-04: QueueRefundService

| 属性 | 值 |
|------|------|
| Phase | 3 |
| Status | `[ ]` pending |
| Dependencies | P3-03 |
| Output | `app/services/queue/QueueRefundService.php` [NEW] |

**Agent Prompt:**

```
创建退款处理服务。
- processRefund(queuePoolId) — 执行退款（更新状态 + 增加 now_money）
- 使用数据库事务
- 退款后触发会员升级检查
```

---

### P3-05: QueuePoolController (API 端)

| 属性 | 值 |
|------|------|
| Phase | 3 |
| Status | `[ ]` pending |
| Dependencies | P3-03 |
| Output | `app/api/controller/v1/queue/QueuePoolController.php` [NEW] |

**Agent Prompt:**

```
创建公排 API 控制器。
- GET status — 调用 QueuePoolService::getQueueStatus()
- GET history — 调用 QueuePoolService::getQueueHistory()
路由路径: hjf/queue/status, hjf/queue/history
```

---

### P3-06: MemberLevelService

| 属性 | 值 |
|------|------|
| Phase | 3 |
| Status | `[ ]` pending |
| Dependencies | P2-03 |
| Output | `app/services/member/MemberLevelService.php` [NEW] |

**Agent Prompt:**

```
创建会员等级服务。
- checkAndUpgrade(uid) — 检查升级条件并执行升级
- getLevelInfo(uid) — 获取等级信息
- updateLevel(uid, level) — 手动调整等级(Admin)
- setNoAssess(uid, flag) — 设置不考核标记
升级条件参考 PRD_V2.md 第 3.2.1 节
```

---

### P3-07: MemberRewardService

| 属性 | 值 |
|------|------|
| Phase | 3 |
| Status | `[ ]` pending |
| Dependencies | P3-06 |
| Output | `app/services/member/MemberRewardService.php` [NEW] |

**Agent Prompt:**

```
创建积分奖励计算服务。
- calculateReward(orderId, buyerUid) — 计算并发放推荐奖励
- 逐级向上遍历推荐链，按等级发放固定积分
- 级差逻辑: 上级只获得与下级等级差额的积分
- 积分入账为 frozen_points（待释放）
```

---

### P3-08: MemberTeamService

| 属性 | 值 |
|------|------|
| Phase | 3 |
| Status | `[ ]` pending |
| Dependencies | P3-06 |
| Output | `app/services/member/MemberTeamService.php` [NEW] |

**Agent Prompt:**

```
创建团队业绩统计服务。
- getDirectCount(uid) — 直推人数
- getUmbrellaCount(uid) — 伞下总人数
- getUmbrellaOrders(uid) — 伞下总单数（含级别隔离逻辑）
- getTeamMembers(uid, page, limit) — 团队成员列表
```

---

### P3-09: PointsReleaseService

| 属性 | 值 |
|------|------|
| Phase | 3 |
| Status | `[ ]` pending |
| Dependencies | P2-02 |
| Output | `app/services/points/PointsReleaseService.php` [NEW] |

**Agent Prompt:**

```
创建积分每日释放服务。
- dailyRelease() — 定时任务入口，遍历所有 frozen_points > 0 的用户
- 释放量 = frozen_points × 0.4‰ (取整)
- 从 frozen_points 扣减，加到 available_points
- 写入 eb_points_release_log
- 使用 Redis 锁防止重复执行
```

---

### P3-10: PointsPayService

| 属性 | 值 |
|------|------|
| Phase | 3 |
| Status | `[ ]` pending |
| Dependencies | P3-09 |
| Output | `app/services/points/PointsPayService.php` [NEW] |

**Agent Prompt:**

```
创建积分支付服务。
- payWithFrozenPoints(uid, amount) — 待释放积分支付（仅普通商品）
- payWithAvailablePoints(uid, amount) — 已释放积分支付（仅普通商品）
- 检查余额是否充足
- 使用数据库事务
```

---

### P3-11: Admin QueueOrderController

| 属性 | 值 |
|------|------|
| Phase | 3 |
| Status | `[ ]` pending |
| Dependencies | P3-03 |
| Output | `app/adminapi/controller/v1/hjf/QueueOrderController.php` [NEW] |

**Agent Prompt:**

```
创建 Admin 公排订单控制器。
- GET order_list — 分页+筛选
```

---

### P3-12: Admin QueueConfigController

| 属性 | 值 |
|------|------|
| Phase | 3 |
| Status | `[ ]` pending |
| Dependencies | P3-03 |
| Output | `app/adminapi/controller/v1/hjf/QueueConfigController.php` [NEW] |

**Agent Prompt:**

```
创建 Admin 公排配置控制器。
- GET config — 读取 eb_system_config
- PUT config — 更新配置
```

---

### P3-13: Admin QueueFinanceController

| 属性 | 值 |
|------|------|
| Phase | 3 |
| Status | `[ ]` pending |
| Dependencies | P3-04 |
| Output | `app/adminapi/controller/v1/hjf/QueueFinanceController.php` [NEW] |

**Agent Prompt:**

```
创建 Admin 公排财务控制器。
- GET finance — 退款流水列表（分页+统计）
```

---

### P3-14: Admin MemberLevelController

| 属性 | 值 |
|------|------|
| Phase | 3 |
| Status | `[ ]` pending |
| Dependencies | P3-06 |
| Output | `app/adminapi/controller/v1/hjf/MemberLevelController.php` [NEW] |

**Agent Prompt:**

```
创建 Admin 会员管理控制器。
- GET list — 会员列表（分页+等级筛选）
- PUT level/{uid} — 调整等级
- GET/PUT config — 会员配置
```

---

### P3-15: API 路由注册

| 属性 | 值 |
|------|------|
| Phase | 3 |
| Status | `[ ]` pending |
| Dependencies | P3-05 |
| Output | `route/api.php` [MOD] |

**Agent Prompt:**

```
在 API 路由文件中注册公排和资产相关路由。

### 路由
- GET hjf/queue/status → QueuePoolController@status
- GET hjf/queue/history → QueuePoolController@history
- GET hjf/assets/overview → HjfAssetsController@overview
- GET hjf/assets/points_detail → HjfAssetsController@pointsDetail
- GET hjf/assets/cash_detail → HjfAssetsController@cashDetail
- GET hjf/assets/withdraw_info → HjfAssetsController@withdrawInfo
- GET hjf/member/info → HjfMemberController@info
- GET hjf/member/team → HjfMemberController@team
- GET hjf/member/income → HjfMemberController@income
```

---

### P3-16: Admin 路由注册

| 属性 | 值 |
|------|------|
| Phase | 3 |
| Status | `[ ]` pending |
| Dependencies | P3-11, P3-12, P3-13, P3-14 |
| Output | `route/adminapi.php` [MOD] |

**Agent Prompt:**

```
在 Admin 路由文件中注册 HJF 管理路由。

### 路由
参考 docs/frontend-new-pages-spec.md 第 5.3.2 节的 Admin API 契约表。
```

---

## Phase 4: 前后端集成（5 tasks，依赖 Phase 3）

---

### P4-01: UniApp 关闭 USE_MOCK

| 属性 | 值 |
|------|------|
| Phase | 4 |
| Status | `[ ]` pending |
| Dependencies | P3-15 |
| Output | `api/hjfQueue.js`, `api/hjfAssets.js`, `api/hjfMember.js` [MOD] |

**Agent Prompt:**

```
将所有 UniApp HJF API 文件中的 USE_MOCK 改为 false。
文件: hjfQueue.js, hjfAssets.js, hjfMember.js
将 const USE_MOCK = true 改为 const USE_MOCK = false
```

---

### P4-02: Admin 关闭 USE_MOCK

| 属性 | 值 |
|------|------|
| Phase | 4 |
| Status | `[ ]` pending |
| Dependencies | P3-16 |
| Output | `api/hjfQueue.js`, `api/hjfMember.js`, `api/hjfPoints.js` [MOD] |

**Agent Prompt:**

```
将所有 Admin HJF API 文件中的 USE_MOCK 改为 false。
文件: hjfQueue.js, hjfMember.js, hjfPoints.js
将 const USE_MOCK = true 改为 const USE_MOCK = false
```

---

### P4-03: UniApp 联调冒烟测试

| 属性 | 值 |
|------|------|
| Phase | 4 |
| Status | `[ ]` pending |
| Dependencies | P4-01 |
| Output | 测试报告 |

**Agent Prompt:**

```
对 UniApp 端所有 HJF 页面进行联调冒烟测试。

### 测试清单
- [ ] P12 公排状态页: 调用真实 API 返回数据正确
- [ ] P13 公排历史页: 分页和筛选正常
- [ ] P15 资产总览页: 三栏数据正确
- [ ] P18 积分明细页: 列表和筛选正常
- [ ] 改造页面: 首页角标、商品详情支付方式、个人中心徽章正常
```

---

### P4-04: Admin 联调冒烟测试

| 属性 | 值 |
|------|------|
| Phase | 4 |
| Status | `[ ]` pending |
| Dependencies | P4-02 |
| Output | 测试报告 |

**Agent Prompt:**

```
对 Admin 端所有 HJF 页面进行联调冒烟测试。

### 测试清单
- [ ] 公排订单列表: 分页和搜索正常
- [ ] 公排配置: 读取和保存正常
- [ ] 积分日志: 列表和统计正常
- [ ] 会员管理: 等级调整正常
```

---

### P4-05: 端到端流程验证

| 属性 | 值 |
|------|------|
| Phase | 4 |
| Status | `[ ]` pending |
| Dependencies | P4-03, P4-04 |
| Output | 测试报告 |

**Agent Prompt:**

```
执行完整的端到端流程验证。

### 测试流程
1. 用户注册 → 绑定推荐关系
2. 购买报单商品(3600元) → 订单生成 → 公排入队
3. 查看公排状态 → 排队序号正确
4. 模拟第4单 → 触发退款 → 余额增加
5. 推荐人获得积分奖励 → frozen_points 增加
6. 次日释放 → available_points 增加
7. 使用积分购买普通商品 → available_points 扣减
8. 提现 → 扣除7%手续费
```

---

## Phase 5: 测试（8 tasks，依赖 Phase 4）

---

### P5-01: QueuePool 单元测试

| 属性 | 值 |
|------|------|
| Phase | 5 |
| Status | `[ ]` pending |
| Dependencies | P4-05 |
| Output | `tests/Unit/QueuePoolServiceTest.php` [NEW] |

**Agent Prompt:**

```
编写公排核心逻辑单元测试。
- 测试入队: 队列序号递增
- 测试进4退1: 第4单触发退款
- 测试退款金额: 正确入账 now_money
- 测试并发: Redis 锁防重复
```

---

### P5-02: MemberReward 单元测试

| 属性 | 值 |
|------|------|
| Phase | 5 |
| Status | `[ ]` pending |
| Dependencies | P4-05 |
| Output | `tests/Unit/MemberRewardServiceTest.php` [NEW] |

**Agent Prompt:**

```
编写会员积分奖励单元测试。
- 测试直推奖励: 按等级发放正确积分
- 测试级差逻辑: 上级不获得同等级下级的团队奖励
- 测试积分入账: 正确写入 frozen_points
```

---

### P5-03: PointsRelease 单元测试

| 属性 | 值 |
|------|------|
| Phase | 5 |
| Status | `[ ]` pending |
| Dependencies | P4-05 |
| Output | `tests/Unit/PointsReleaseServiceTest.php` [NEW] |

**Agent Prompt:**

```
编写积分释放定时任务单元测试。
- 测试释放计算: 15000 × 0.4‰ = 6
- 测试余额变化: frozen_points 减少, available_points 增加
- 测试日志: eb_points_release_log 正确写入
- 测试幂等: 同一天不重复释放
```

---

### P5-04: 公排并发测试

| 属性 | 值 |
|------|------|
| Phase | 5 |
| Status | `[ ]` pending |
| Dependencies | P5-01 |
| Output | `tests/Feature/QueuePoolFlowTest.php` [NEW] |

**Agent Prompt:**

```
编写公排并发场景测试。
- 模拟 10 个用户同时下单
- 验证队列序号不重复
- 验证退款不重复触发
- 验证 Redis 锁正确工作
```

---

### P5-05: 完整购买流程 E2E

| 属性 | 值 |
|------|------|
| Phase | 5 |
| Status | `[ ]` pending |
| Dependencies | P5-01, P5-02, P5-03 |
| Output | `tests/Feature/OrderPayWithQueueTest.php` [NEW] |

**Agent Prompt:**

```
编写完整购买流程 E2E 测试。
1. 创建报单商品 (is_queue_goods=1)
2. 用户A购买1单 → 入队(queue_no=1)
3. 用户B购买1单 → 入队(queue_no=2), 用户A的推荐人获得积分
4. 用户C购买2单 → 入队(queue_no=3,4), 触发退款(queue_no=1)
5. 验证用户A的 now_money += 3600
6. 验证各级推荐人积分正确
```

---

### P5-06: Admin CRUD 冒烟测试

| 属性 | 值 |
|------|------|
| Phase | 5 |
| Status | `[ ]` pending |
| Dependencies | P4-04 |
| Output | 测试报告 |

**Agent Prompt:**

```
Admin 后台管理功能冒烟测试。
- 公排订单列表: 搜索/分页
- 公排配置: 读取/修改/保存
- 会员管理: 列表/等级调整/不考核设置
- 积分日志: 列表/日期筛选
```

---

### P5-07: 积分释放定时任务测试

| 属性 | 值 |
|------|------|
| Phase | 5 |
| Status | `[ ]` pending |
| Dependencies | P5-03 |
| Output | 测试报告 |

**Agent Prompt:**

```
积分释放定时任务集成测试。
- 创建 100 个用户，各有不同 frozen_points
- 执行 dailyRelease()
- 验证: 每个用户 frozen_points 正确减少, available_points 正确增加
- 验证: eb_points_release_log 记录数 = 有积分的用户数
- 验证: 执行时间 < 5 秒
```

---

### P5-08: 提现流程测试

| 属性 | 值 |
|------|------|
| Phase | 5 |
| Status | `[ ]` pending |
| Dependencies | P4-05 |
| Output | 测试报告 |

**Agent Prompt:**

```
提现流程集成测试。
- 用户余额 7200, 提现 1000
- 验证: 手续费 = 1000 × 7% = 70
- 验证: 实际到账 = 930
- 验证: now_money = 7200 - 1000 = 6200
- 边界测试: 低于最低提现额拒绝, 超过余额拒绝
```

---

## 依赖关系图

```
Phase 0
  P0-01 ──┐
  P0-02 ──┤
          │
Phase 1   ▼
  1A (6) ←── P0-01/P0-02
    │
  1B (4) ←── 1A
    │
  1C (6) ←── 1A + 1B
  1D (7) ←── 1A + 1B
  1E (6) ←── 1A
    │
  1F (7) ←── 1C + 1E
  1G (2) ←── 1A
    │
  CP-01 ←── ALL Phase 1
    │
Phase 2 (5) ←── CP-01
    │
Phase 3 (16) ←── Phase 2
    │
Phase 4 (5) ←── Phase 3
    │
Phase 5 (8) ←── Phase 4
```

---

## 附录：任务索引

| 编号 | 名称 | Phase | Stage | 类型 |
|------|------|-------|-------|------|
| P0-01 | UniApp Mock 数据文件 | 0 | — | NEW |
| P0-02 | Admin Mock 数据文件 | 0 | — | NEW |
| P1A-01 | UniApp api/hjfQueue.js | 1 | 1A | NEW |
| P1A-02 | UniApp api/hjfAssets.js | 1 | 1A | NEW |
| P1A-03 | UniApp api/hjfMember.js | 1 | 1A | NEW |
| P1A-04 | Admin api/hjfQueue.js | 1 | 1A | NEW |
| P1A-05 | Admin api/hjfMember.js | 1 | 1A | NEW |
| P1A-06 | Admin api/hjfPoints.js | 1 | 1A | NEW |
| P1B-01 | HjfQueueProgress | 1 | 1B | NEW |
| P1B-02 | HjfAssetCard | 1 | 1B | NEW |
| P1B-03 | HjfMemberBadge | 1 | 1B | NEW |
| P1B-04 | HjfRefundNotice | 1 | 1B | NEW |
| P1C-01 | P12 公排状态页 | 1 | 1C | NEW |
| P1C-02 | P13 公排历史页 | 1 | 1C | NEW |
| P1C-03 | P14 公排规则页 | 1 | 1C | NEW |
| P1C-04 | P15 资产总览页 | 1 | 1C | NEW |
| P1C-05 | P18 积分明细页 | 1 | 1C | NEW |
| P1C-06 | P23 新用户引导页 | 1 | 1C | NEW |
| P1D-01 | 首页改造 | 1 | 1D | MOD |
| P1D-02 | 商品详情改造 | 1 | 1D | MOD |
| P1D-03 | 购买流程改造 | 1 | 1D | MOD |
| P1D-04 | 我的订单改造 | 1 | 1D | MOD |
| P1D-05 | 推荐页改造 | 1 | 1D | MOD |
| P1D-06 | 提现页改造 | 1 | 1D | MOD |
| P1D-07 | 个人中心改造 | 1 | 1D | MOD |
| P1E-01 | 公排订单管理页 | 1 | 1E | NEW |
| P1E-02 | 公排财务流水页 | 1 | 1E | NEW |
| P1E-03 | 积分释放日志页 | 1 | 1E | NEW |
| P1E-04 | 公排参数配置页 | 1 | 1E | NEW |
| P1E-05 | 会员等级配置页 | 1 | 1E | NEW |
| P1E-06 | 会员管理页 | 1 | 1E | NEW |
| P1F-01 | pages.json 公排路由 | 1 | 1F | MOD |
| P1F-02 | pages.json 资产路由 | 1 | 1F | MOD |
| P1F-03 | pages.json 引导路由 | 1 | 1F | MOD |
| P1F-04 | Admin hjfQueue 路由 | 1 | 1F | NEW |
| P1F-05 | Admin 路由模块注册 | 1 | 1F | MOD |
| P1F-06 | Admin finance 路由评估 | 1 | 1F | 评估 |
| P1F-07 | Admin user 路由评估 | 1 | 1F | 评估 |
| P1G-01 | 用户管理改造 | 1 | 1G | MOD |
| P1G-02 | 商品管理改造 | 1 | 1G | MOD |
| CP-01 | 前端评审检查点 | CP | — | GATE |
| P2-01 | eb_queue_pool 建表 | 2 | — | DDL |
| P2-02 | eb_points_release_log 建表 | 2 | — | DDL |
| P2-03 | eb_user 加字段 | 2 | — | DDL |
| P2-04 | eb_system_config 配置 | 2 | — | DML |
| P2-05 | eb_store_product 加字段 | 2 | — | DDL |
| P3-01 | QueuePool Model | 3 | — | NEW |
| P3-02 | QueuePoolDao | 3 | — | NEW |
| P3-03 | QueuePoolService | 3 | — | NEW |
| P3-04 | QueueRefundService | 3 | — | NEW |
| P3-05 | QueuePoolController | 3 | — | NEW |
| P3-06 | MemberLevelService | 3 | — | NEW |
| P3-07 | MemberRewardService | 3 | — | NEW |
| P3-08 | MemberTeamService | 3 | — | NEW |
| P3-09 | PointsReleaseService | 3 | — | NEW |
| P3-10 | PointsPayService | 3 | — | NEW |
| P3-11 | Admin QueueOrderController | 3 | — | NEW |
| P3-12 | Admin QueueConfigController | 3 | — | NEW |
| P3-13 | Admin QueueFinanceController | 3 | — | NEW |
| P3-14 | Admin MemberLevelController | 3 | — | NEW |
| P3-15 | API 路由注册 | 3 | — | MOD |
| P3-16 | Admin 路由注册 | 3 | — | MOD |
| P4-01 | UniApp 关闭 USE_MOCK | 4 | — | MOD |
| P4-02 | Admin 关闭 USE_MOCK | 4 | — | MOD |
| P4-03 | UniApp 联调冒烟 | 4 | — | TEST |
| P4-04 | Admin 联调冒烟 | 4 | — | TEST |
| P4-05 | 端到端验证 | 4 | — | TEST |
| P5-01 | QueuePool 单元测试 | 5 | — | TEST |
| P5-02 | MemberReward 单元测试 | 5 | — | TEST |
| P5-03 | PointsRelease 单元测试 | 5 | — | TEST |
| P5-04 | 公排并发测试 | 5 | — | TEST |
| P5-05 | 完整购买流程 E2E | 5 | — | TEST |
| P5-06 | Admin CRUD 冒烟 | 5 | — | TEST |
| P5-07 | 积分释放定时任务测试 | 5 | — | TEST |
| P5-08 | 提现流程测试 | 5 | — | TEST |
