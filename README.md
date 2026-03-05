# 黄精粉微信小程序

黄精粉社交电商小程序 - 多级分销+公排返利模式

## 项目结构

```
huangjingfen/
├── mp/           # 小程序前端 (UniApp 3 + Vue 3)
├── api/          # 后端 API (Spring Boot 3.2)
├── docs/         # 项目文档、PRD、设计稿
└── README.md     # 本文件
```

## 技术栈

| 层级 | 技术 |
|------|------|
| 小程序 | UniApp 3 + Vue 3 |
| 后端 | Spring Boot 3.2 + JDK 17 |
| 数据库 | MySQL 8.0 |
| 缓存 | Redis |
| 定时任务 | XXL-Job |

## 快速开始

### 前端
```bash
cd mp
npm install
npm run dev:mp-weixin
```

### 后端
```bash
cd api
./mvnw spring-boot:run
```

## 文档

- [PRD v1.0](./docs/PRD-v1.0.md) - 产品需求文档

## 仓库地址

http://49.235.131.69:3000/scottpan/huangjingfen
