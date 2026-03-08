# 黄精粉健康商城小程序

> 基于 **CRMEB Pro v3.5.1** 二次开发的社交电商微信小程序，支持多级分销 + 公排返利 + 会员积分体系。 

## 项目简介

黄精粉健康商城是一款健康食品社交电商小程序，核心商品为 3600 元黄精粉套餐。通过「公排进四退一」机制降低用户购买心理门槛，配合 5 级会员积分体系激励用户裂变推广。

### 核心业务特性

- **公排机制**：全局排队队列，每进 N 单退 1 单，退款自动进入用户现金余额
- **会员等级**：普通会员 → 创客 → 云店 → 服务商 → 分公司，5 级晋升体系
- **积分体系**：直推/伞下奖励积分 → 待释放（冻结）→ 每日按千分之四释放 → 可购买普通商品
- **分销裂变**：推荐绑定 + 团队业绩统计 + 级差规则

## 技术栈

| 层级 | 技术 | 说明 |
|------|------|------|
| 后端框架 | ThinkPHP 8.0 + PHP 8.0+ | CRMEB Pro 底座 |
| 高性能服务 | Swoole 4.x | 协程 HTTP 服务器 |
| 数据库 | MySQL 8.0 | InnoDB 引擎，`eb_` 前缀 |
| 缓存/队列 | Redis 7.x | 缓存 + 分布式锁 + 队列驱动 |
| 异步任务 | think-queue | Redis 驱动异步队列 |
| 小程序前端 | UniApp + Vue 3 | 微信小程序 + H5 |
| 管理后台 | Vue 2 + iView + Element UI | 后台管理界面 |
| 容器部署 | Docker | 端口 20199 |

## 项目结构

```
huangjingfen/
├── pro_v3.5.1/                   # CRMEB Pro v3.5.1 主程序
│   ├── app/                      # 后端应用代码
│   │   ├── api/controller/       # 移动端 API 控制器
│   │   ├── adminapi/controller/  # 后台管理 API 控制器
│   │   ├── services/             # 业务逻辑层（含新增 queue/member/points）
│   │   ├── dao/                  # 数据访问层
│   │   ├── model/                # 数据模型
│   │   ├── jobs/                 # 异步队列任务
│   │   └── listener/            # 事件监听器
│   ├── crmeb/                    # CRMEB 核心框架（不修改）
│   ├── config/                   # 配置文件
│   ├── view/
│   │   ├── uniapp/               # 小程序前端（UniApp + Vue 3）
│   │   │   ├── pages/            # 页面（含新增 queue/assets/guide）
│   │   │   ├── api/              # API 模块（含新增 queue/points/member）
│   │   │   └── components/       # 组件（含新增 Hjf* 系列）
│   │   └── admin/                # 管理后台（Vue 2 + iView）
│   │       └── src/
│   │           ├── pages/        # 页面（含新增 queueOrder/queueFinance 等）
│   │           ├── api/          # API 模块（含新增 hjfQueue/hjfMember/hjfPoints）
│   │           └── router/       # 路由配置
│   └── public/                   # Web 入口
├── docs/                         # 项目文档
│   ├── PRD_V2.md                 # 产品需求文档 V2.0
│   ├── 黄精粉小程序_Figma_UI设计说明文档.md
│   ├── OpenClaw_Agent_Config.md  # AI Agent 配置方案
│   ├── frontend-new-pages-spec.md # 前端新页面开发说明
│   └── openclaw-frontend-tasks.md # 前端 Agent 执行计划
├── .openclaw/                    # OpenClaw AI Agent 配置
│   ├── IDENTITY.md               # Agent 身份与心跳配置
│   ├── SOUL.md                   # 技术栈、编码规范、行为准则
│   ├── AGENTS.md                 # Sprint 计划、安全规则、协作规范
│   ├── USER.md                   # 用户信息档案
│   ├── TOOLS.md                  # 开发工具与命令
│   └── PROJECT.md                # 项目路径与结构
└── README.md                     # 本文件
```

## 快速开始

### 环境要求

- PHP 8.0+（需安装 swoole, redis, bcmath 扩展）
- MySQL 8.0+
- Redis 7.0+
- Node.js 18+
- Composer 2.x

### 后端启动

```bash
cd pro_v3.5.1
composer install
php think swoole start
```

### 小程序前端

```bash
cd pro_v3.5.1/view/uniapp
npm install
npm run dev:mp-weixin
# 用微信开发者工具打开 dist/dev/mp-weixin 目录
```

### 管理后台

```bash
cd pro_v3.5.1/view/admin
npm install
npm run build
```

## 二次开发模块

| 模块 | 说明 | Sprint |
|------|------|--------|
| 公排引擎 | 全局排队 + 自动退款 + 并发安全 | Sprint 1 |
| 会员体系 | 5 级会员 + 团队业绩 + 自动晋升 | Sprint 2 |
| 积分体系 | 奖励计算 + 冻结释放 + 积分支付 | Sprint 2 |
| 裂变推荐 | 推荐绑定 + 收益明细 + 提现(7%) | Sprint 3 |
| 后台管理 | 公排管理 + 会员管理 + 财务流水 | Sprint 4 |

## 文档索引

| 文档 | 说明 |
|------|------|
| [PRD v2.0](./docs/PRD_V2.md) | 完整产品需求文档 |
| [Figma UI 设计说明](./docs/黄精粉小程序_Figma_UI设计说明文档.md) | 27 页 UI/交互规范 |
| [前端页面开发说明](./docs/frontend-new-pages-spec.md) | 新开发页面完整规格（6 页面 + 4 组件 + 3 API） |
| [前端 Agent 执行计划](./docs/openclaw-frontend-tasks.md) | 25 个原子化 AI Agent 任务 |
| [OpenClaw 配置方案](./docs/OpenClaw_Agent_Config.md) | Agent 配置体系设计 |

## 仓库地址

http://49.235.131.69:3000/scottpan/huangjingfen
