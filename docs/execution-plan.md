# 黄精粉健康商城 · 剩余开发任务执行方案

> 基于 PRD_V2.md + openclaw-frontend-tasks.md 的现状分析
> 制定日期：2026-03-15
> 当前分支：claude/hjf-queue-admin-apis-hsymG

---

## 一、现状盘点（已完成 vs 待完成）

### ✅ 已完成的任务

| 阶段 | 任务 | 说明 |
|------|------|------|
| Phase 0 | P0-01, P0-02 | UniApp + Admin Mock 数据文件 |
| Stage 1A | P1A-01~06 | 全部6个 API 模块（uniapp + admin） |
| Stage 1B | P1B-01~04 | 全部4个公共组件（QueueProgress / AssetCard / MemberBadge / RefundNotice） |
| Stage 1C | P1C-01~06 | 全部6个新 UniApp 页面（公排状态/历史/规则 + 资产总览/积分明细 + 引导页） |
| Stage 1D | P1D-02 | 商品详情页：`is_queue_goods` 角标 + 公排提示条 |
| Stage 1D | P1D-03 | 购买确认页：多单拆分提示 + 公排入队说明 |
| Stage 1D | P1D-04 | 支付结果页：公排入队成功提示 + 查看公排入口 |
| Stage 1D | P1D-06 | 提现页：7% 手续费动态计算 + 提示文案 |
| Stage 1E | P1E-01~06 | 全部6个 Admin 新页面（公排订单/财务/配置 + 会员管理/配置 + 积分日志） |
| Stage 1F | P1F-01~07 | 全部路由注册（pages.json + Admin hjfQueue.js 路由模块 + index.js 导入） |
| Stage 1G | P1G-01 | Admin 用户管理列表：`member_level`、`no_assess` 列和筛选项 |

---

### ⏳ 待完成的任务（本方案覆盖范围）

```
Phase 1 尾单（4项）
├── P1D-01  首页：报单商品角标 + 公排入口Banner
├── P1D-05  推荐收益页：积分替换佣金显示
├── P1D-07  个人中心：HjfMemberBadge等级徽章嵌入
└── P1G-02  Admin商品编辑：报单标记 + 积分支付白名单

Phase 2  数据库迁移（5项）
Phase 3  后端 API 开发（16项）
Phase 4  前后端联调集成（5项）
Phase 5  完整测试（8项）
```

---

## 二、执行方案

### 阶段 A：Phase 1 收尾（前端，可立即执行）

> 依赖：无。可在当前 Mock 模式下独立完成。
> 目标：让 Phase 1 所有38个任务全部 `[x]`，解锁 CP-01 评审检查点。

---

#### 任务 A1 — P1D-01：首页报单商品角标

**文件**：`pro_v3.5.1/view/uniapp/pages/index/index.vue`

**改造内容**：
1. 在商品列表卡片的商品名/图片处，检查 `item.is_queue_goods == 1`，叠加渲染 `报单` 角标（红色标签，参考 goods_details 中已有的 `.queue-goods-tag` 样式）。
2. 在首页 Banner 区或活动专区下方，增加"公排进度"快捷入口行（复用 `HjfQueueProgress` 组件缩略版，或仅放文字按钮跳转 `/pages/queue/status`）。
3. 无需新增 API 调用，角标从商品列表字段 `is_queue_goods` 读取即可。

**验收标准**：报单商品卡片右上角出现红色"报单"角标；点击公排入口可跳转公排状态页。

---

#### 任务 A2 — P1D-05：推荐收益页积分替换佣金

**文件**：`pro_v3.5.1/view/uniapp/pages/users/user_spread_money/index.vue`

**改造内容**：
1. 将列表中"佣金"字样统一替换为"积分"，金额字段从 `money`/`commission` 改为读取 `points`。
2. 展示积分类型标签：`reward_direct`（直推奖励）/ `reward_umbrella`（伞下奖励）。
3. 导入 `import { getTeamIncome } from '@/api/hjfMember.js'`，替换原有 API 调用。
4. 数值格式：整数积分，不保留小数；去掉 `¥` 符号，改为"积分"后缀。

**验收标准**：推荐收益列表显示积分数量而非金额，类型标签正确区分直推/伞下。

---

#### 任务 A3 — P1D-07：个人中心会员等级徽章

**文件**：`pro_v3.5.1/view/uniapp/pages/user/index.vue`

**改造内容**：
1. 引入 `HjfMemberBadge` 组件，在用户头像/昵称旁嵌入等级徽章。
   ```js
   import HjfMemberBadge from '@/components/HjfMemberBadge.vue'
   ```
2. 从 `getMemberInfo` API（已有 Mock）获取 `member_level`，传入组件 `:level` prop。
3. 在资产快捷入口区域已有的 `hjf-nav-row` 基础上，补充"待释放积分"数值预览（展示 `frozen_points`，不换页即可看到大数字）。
4. 已有的公排查询 + 资产入口导航行保持不变，不重复建设。

**验收标准**：昵称旁出现对应等级的彩色徽章；资产行显示待释放积分数。

---

#### 任务 A4 — P1G-02：Admin 商品编辑-报单标记与支付方式

**文件**：`pro_v3.5.1/view/admin/src/pages/product/creatProduct/index.vue`

**改造内容**：
1. 在商品基本信息 Tab 中增加"报单商品"开关（iView `i-switch`），绑定 `formValidate.is_queue_goods`，默认 `false`。
2. 在支付方式 Tab / 销售设置区域，增加复选框组"允许积分支付"（`allow_pay_types`），选项：`待释放积分`、`已释放积分`；报单商品开关开启时，此项置灰并强制清空。
3. 表单提交时将 `is_queue_goods`（0/1）和 `allow_pay_types`（数组序列化）一并提交。
4. 编辑回显时正确反填两个字段。

**验收标准**：新建/编辑商品可设置报单标记；报单商品自动禁用积分支付选项。

---

### 阶段 B：Phase 2 数据库迁移

> 依赖：后端开发环境就绪（ThinkPHP 8 + MySQL 8.0）。
> 建议由后端工程师执行，前端工程师无需等待此阶段。

| 任务 | 操作 | 目标 |
|------|------|------|
| P2-01 | CREATE TABLE | `eb_queue_pool`（公排池，9个字段，含复合索引） |
| P2-02 | CREATE TABLE | `eb_points_release_log`（积分释放日志，7个字段） |
| P2-03 | ALTER TABLE | `eb_user` 增加4字段：`member_level`、`no_assess`、`frozen_points`、`available_points` |
| P2-04 | ALTER TABLE | `eb_store_product` 增加2字段：`is_queue_goods`、`allow_pay_types` |
| P2-05 | INSERT | `eb_system_config` 插入9项系统配置键值对（公排倍数、释放比例、手续费率、各等级门槛和奖励） |

**关键索引（P2-01）**：
```sql
INDEX idx_uid (uid),
INDEX idx_status_add_time (status, add_time),
INDEX idx_queue_no (queue_no),
INDEX idx_trigger_batch (trigger_batch)
```

---

### 阶段 C：Phase 3 后端 API 开发

> 依赖：Phase 2 完成。
> 开发顺序：先核心引擎，再外围接口，最后定时任务。

#### C1 — 公排引擎（优先级最高）

| 任务 | 文件/类 | 内容 |
|------|---------|------|
| P3-01 | `QueuePool` Service | 入队逻辑（写 `eb_queue_pool`，Redis 分布式锁防并发） |
| P3-02 | `QueueRefund` Service | 退款触发逻辑（每入N单检查退款，使用 think-queue 异步处理） |
| P3-03 | `QueueController` | 用户端接口：`GET /hjf/queue/status`、`GET /hjf/queue/history` |
| P3-04 | `AdminQueueController` | Admin接口：`GET /hjf/queue/order`、`GET /hjf/queue/config`、`POST /hjf/queue/config`、`GET /hjf/queue/finance` |

**核心逻辑要点**：
- 支付回调成功后：判断 `is_queue_goods` → 多单拆分 → 逐单调用 `QueuePool::enqueue()` → 检查触发条件
- Redis Key：`hjf:queue:lock`（分布式锁），`hjf:queue:pending_count`（待触发计数）
- 退款写入 `eb_user.now_money`（复用 CRMEB 余额字段），记录 `eb_user_bill`

#### C2 — 积分奖励引擎

| 任务 | 文件/类 | 内容 |
|------|---------|------|
| P3-05 | `PointsReward` Service | 级差计算：按会员等级发放直推/伞下积分，写入 `frozen_points` |
| P3-06 | `PointsRelease` Job | 每日凌晨定时任务：`frozen_points × 0.4‰ → available_points`，写 `eb_points_release_log` |
| P3-07 | `PointsController` | 用户端接口：`GET /hjf/points/detail`（5类型筛选，分页） |
| P3-08 | `AdminPointsController` | Admin接口：`GET /hjf/points/release-log` |

**每日释放公式**：`release_amount = FLOOR(frozen_points × rate / 1000)`，`rate` 取系统配置 `hjf_release_rate`（默认 4）。

#### C3 — 会员等级体系

| 任务 | 文件/类 | 内容 |
|------|---------|------|
| P3-09 | `MemberLevel` Service | 升级判断：直推单数 / 伞下业绩单数达标后自动升级；伞下业绩分离逻辑 |
| P3-10 | `AdminMemberController` | Admin接口：`GET /hjf/member/list`、`PUT /hjf/member/level/:uid`、`GET/POST /hjf/member/config` |

**升级触发时机**：每次订单支付回调完成后，对推荐链上的所有上级异步检查升级条件。

#### C4 — 资产接口

| 任务 | 文件/类 | 内容 |
|------|---------|------|
| P3-11 | `AssetsController` | `GET /hjf/assets/overview`：返回余额 + 积分汇总（复用 `eb_user` 字段） |
| P3-12 | `AssetsController` | `GET /hjf/assets/cash/detail`：现金流水（分页，复用 `eb_user_bill`） |

#### C5 — 路由注册

| 任务 | 内容 |
|------|------|
| P3-13 | `route/api.php`：注册用户端全部 hjf 路由（含鉴权中间件） |
| P3-14 | `route/admin.php`：注册 Admin 端全部 hjf 路由（含权限中间件） |

#### C6 — 单元测试桩

| 任务 | 内容 |
|------|------|
| P3-15 | 公排引擎单元测试：入队/触发退款/分布式锁 |
| P3-16 | 积分计算单元测试：级差计算/每日释放精度（bcmath） |

---

### 阶段 D：Phase 4 前后端联调集成

> 依赖：Phase 2 + Phase 3 完成，测试环境可访问真实 API。

| 任务 | 内容 | 操作 |
|------|------|------|
| P4-01 | 关闭 Mock 开关 | 将所有 `const USE_MOCK = true` 改为 `false`（UniApp + Admin 共8个文件） |
| P4-02 | UniApp 冒烟测试 | 登录 → 查看公排状态 → 资产总览 → 积分明细 → 推荐收益 |
| P4-03 | Admin 冒烟测试 | 公排订单列表 → 公排配置保存 → 会员等级调整 → 积分日志查询 |
| P4-04 | 支付回调联调 | 测试购买报单商品 → 公排入队 → 积分发放 → 等级升级完整链路 |
| P4-05 | 定时任务验证 | 手动触发每日积分释放任务，验证 `release_log` 记录正确 |

**Mock 关闭检查清单**：
```
uniapp/api/hjfQueue.js      USE_MOCK → false
uniapp/api/hjfAssets.js     USE_MOCK → false
uniapp/api/hjfMember.js     USE_MOCK → false
admin/src/api/hjfQueue.js   USE_MOCK → false
admin/src/api/hjfMember.js  USE_MOCK → false
admin/src/api/hjfPoints.js  USE_MOCK → false
```

---

### 阶段 E：Phase 5 完整测试

> 依赖：Phase 4 联调通过。

| 任务 | 类型 | 内容 |
|------|------|------|
| P5-01 | 前端渲染测试 | 所有新页面在3个Mock场景(A/B/C)下截图验收 |
| P5-02 | 后端接口测试 | 用 Postman/Apifox 验证所有 P3 接口的响应格式和边界值 |
| P5-03 | 公排边界测试 | 精确触发：第4单入队时退款到第1单；多人同时入队（并发锁） |
| P5-04 | 积分精度测试 | bcmath 计算：`1000000 × 4 / 1000 = 4000`（无浮点误差） |
| P5-05 | 会员升级测试 | 直推3单后自动升级创客；伞下30单升云店；业绩分离逻辑 |
| P5-06 | 并发压测 | 1000并发用户同时访问公排状态页；公排入队 200 TPS |
| P5-07 | E2E 全流程 | 新用户注册 → 引导页 → 购买报单商品 → 等待公排退款 → 申请提现 |
| P5-08 | 回归测试 | CRMEB 原有功能（登录/商品/订单/支付）未被改造影响 |

---

## 三、执行优先级与分工建议

```
立即可执行（无依赖，Agent 可直接实施）
├── A1  首页报单角标           ← 最简单，约30分钟
├── A2  推荐收益页积分替换      ← 约45分钟
├── A3  个人中心等级徽章        ← 约30分钟
└── A4  Admin商品编辑改造       ← 约60分钟

等待后端就绪（并行推进）
├── B   数据库迁移              ← DBA/后端工程师
├── C   后端API开发             ← 后端工程师（C1优先）
└── D   联调集成                ← 前后端协作

最终验收
└── E   完整测试                ← 测试工程师
```

**关键路径**：`A1~A4 完成` → `CP-01 评审` → `B+C 并行` → `D 联调` → `E 测试`

---

## 四、风险点与注意事项

| 风险 | 描述 | 应对措施 |
|------|------|---------|
| 公排并发竞争 | 多单同时入队可能重复触发退款 | Redis `SET NX EX` 分布式锁，退款前二次检查状态 |
| 积分浮点误差 | `3600 × 0.4‰` 在 PHP 中存在精度问题 | 全程使用 `bcmath`：`bcmul($points, '4', 0)` → `bcdiv(..., '1000', 0)` |
| 伞下业绩分离 | 下级升级后业绩需从上级扣除 | 升级事件写入消息队列，异步重算上级业绩；加数据库事务 |
| Admin 路由权限 | hjf 新路由需配置到角色权限表 | P3-14 后端路由注册时同步写 `eb_system_menus` |
| CRMEB 原生字段冲突 | `eb_user` 新增字段可能影响原有查询 | ALTER TABLE 使用 `DEFAULT 0`，不破坏现有 NULL 约束 |

---

## 五、当前可立即下达的指令（Agent 参考）

按优先级排序，每条指令对应一个独立任务，完成后 `git commit` 即可：

```
1. feat(P1D-01): 首页报单商品角标与公排快捷入口
   文件: pages/index/index.vue

2. feat(P1D-05): 推荐收益页积分替换佣金
   文件: pages/users/user_spread_money/index.vue

3. feat(P1D-07): 个人中心嵌入HjfMemberBadge等级徽章
   文件: pages/user/index.vue

4. feat(P1G-02): Admin商品编辑报单标记与积分支付配置
   文件: admin/src/pages/product/creatProduct/index.vue
```
