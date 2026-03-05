# 小程序前端

黄精粉微信小程序前端代码

## 技术栈
- UniApp 3
- Vue 3
- Pinia 状态管理
- uni-ui 组件库

## 目录结构

```
mp/
├── src/
│   ├── pages/          # 页面
│   ├── components/     # 组件
│   ├── static/         # 静态资源
│   ├── utils/          # 工具函数
│   ├── api/            # API 接口
│   └── store/          # Pinia store
├── manifest.json       # 应用配置
├── pages.json          # 页面配置
└── package.json
```

## 开发规范

- 页面命名：kebab-case（如 `my-order.vue`）
- 组件命名：PascalCase（如 `ProductCard.vue`）
- API 封装：`src/api/` 目录，按模块分文件
