# 黄精粉小程序 UniApp 运行说明

## 推荐方式：使用 HBuilderX 运行到浏览器（H5）

本 CRMEB 项目的小程序端按 HBuilderX + uni-app 标准目录设计，**推荐使用 HBuilderX 进行开发与预览**：

1. 安装 [HBuilderX](https://www.dcloud.io/hbuilderx.html)（正式版或 App 开发版）。
2. 用 HBuilderX 打开本目录：`pro_v3.5.1/view/uniapp`（即当前 package.json 所在目录）。
3. 在菜单栏选择 **运行 → 运行到浏览器 → 选择 Chrome（或其他浏览器）**。
4. 等待编译完成后，会自动打开浏览器，即可查看 Mock 演示效果。

在 H5 预览下可看到：
- 首页 / 个人中心右下角的 **演示控制面板**（紫色悬浮按钮）
- 切换场景 A/B/C、快捷跳转、退款弹窗等 Mock 演示功能

---

## 命令行方式（可选）

已在 `package.json` 中配置脚本与依赖，满足以下条件时可尝试命令行启动 H5：

- 需在 **本目录**（`view/uniapp`）下执行。
- 若使用 Vue CLI 5，可能与当前 uni-app 插件的 webpack 规则不兼容，出现 `No matching use for foo.js` 或 `reading 'use'` 等错误，此时请改用 HBuilderX。

### 启动 H5 开发服务

```bash
cd pro_v3.5.1/view/uniapp

# 设置环境变量并启动（Mac/Linux）
UNI_CLI_CONTEXT=$(pwd) UNI_PLATFORM=h5 UNI_INPUT_DIR=$(pwd) npx vue-cli-service uni-serve --platform h5

# 或使用 npm 脚本（需已安装 cross-env）
npm run dev:h5
```

默认端口：**8080**（在 `vue.config.js` 的 `devServer.port` 中配置）。  
启动成功后，在浏览器访问：**http://localhost:8080**。

### 微信小程序开发

使用 HBuilderX 打开本目录后，选择 **运行 → 运行到小程序模拟器 → 微信开发者工具**，并先在本地打开微信开发者工具。

---

## Mock 演示说明

- 所有 HJF 相关接口当前使用 **Mock 数据**（`utils/hjfMockData.js`），无需后端即可演示。
- 右下角 **演示控制面板** 可切换场景 A（新用户）/ B（活跃用户）/ C（VIP），并支持快捷跳转、退款弹窗、重置引导等。
- 完整演示路线与验收点见：**docs/mock-demo-walkthrough.md**。
