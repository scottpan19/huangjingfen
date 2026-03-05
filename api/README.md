# 后端 API

黄精粉小程序后端服务

## 技术栈
- Spring Boot 3.2
- JDK 17
- MyBatis-Plus
- MySQL 8.0
- Redis
- XXL-Job

## 目录结构

```
api/
├── src/
│   ├── main/
│   │   ├── java/
│   │   │   └── com/huangjingfen/
│   │   │       ├── config/       # 配置类
│   │   │       ├── controller/   # 控制器
│   │   │       ├── service/      # 服务层
│   │   │       ├── mapper/       # 数据访问层
│   │   │       ├── entity/       # 实体类
│   │   │       └── job/          # 定时任务
│   │   └── resources/
│   │       ├── mapper/           # XML映射文件
│   │       └── application.yml
│   └── test/
└── pom.xml
```

## 开发规范

- 包名：`com.huangjingfen`
- 数据库表名：`hjf_` 前缀（如 `hjf_user`）
- API 前缀：`/api/v1/`
