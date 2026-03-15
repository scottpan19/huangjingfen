# 黄精粉健康商城二次开发发布部署方案

> **环境**：云服务器已运行原始 CRMEB Pro v3.5（Docker + Nginx + Swoole + Redis + MySQL 5.7）
> **分支**：`claude/hjf-queue-admin-apis-hsymG`
> **变更范围**：44个文件（后端PHP新增30个文件 + 修改3个 + 前端JS/Vue 11个）

---

## 一、环境确认（部署前检查）

### 1.1 服务器环境要求

| 项目 | 要求 | 确认方式 |
|------|------|---------|
| PHP | ≥ 8.0，扩展：bcmath、mbstring、redis、curl | `php -v && php -m` |
| MySQL | ≥ 5.7（已兼容，8.0 同样支持） | `mysql --version` |
| Redis | ≥ 5.0 | `redis-cli --version` |
| Node.js | ≥ 14（编译 Admin 前端用） | `node -v` |
| npm | ≥ 6 | `npm -v` |
| Composer | ≥ 2 | `composer --version` |
| Swoole | ≥ 4.8 | `php --ri swoole` |

### 1.2 目录结构确认

```
/var/www/               # 项目根目录（Dockerfile WORKDIR）
├── public/             # Nginx 静态文件根目录
├── view/admin/dist/    # Admin 构建产物（已有）
├── view/uniapp/        # UniApp 源码（H5 构建产物需放入 public/）
└── .env                # 生产环境配置（不可覆盖）
```

---

## 二、部署前备份（强制执行）

### 2.1 备份数据库

```bash
# 在服务器上执行
BACKUP_DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u root -p \
  --single-transaction \
  --routines \
  --triggers \
  crmeb_db > /backup/db_before_hjf_${BACKUP_DATE}.sql

echo "备份完成：/backup/db_before_hjf_${BACKUP_DATE}.sql"
```

### 2.2 备份当前代码

```bash
# 备份关键修改文件（仅涉及本次二次开发的文件）
cd /var/www
tar -czf /backup/code_before_hjf_${BACKUP_DATE}.tar.gz \
  app/listener/order/Pay.php \
  route/api.php \
  route/admin.php \
  config/console.php \
  view/admin/dist/
```

> ⚠️ **回滚依赖此备份，务必确认备份文件大小 > 0**

---

## 三、代码同步

### 3.1 方案：Git Pull（推荐）

服务器上的代码仓库已关联 Git remote，直接拉取目标分支：

```bash
cd /var/www

# 确认当前分支
git status
git branch

# 拉取最新代码
git fetch origin claude/hjf-queue-admin-apis-hsymG
git checkout claude/hjf-queue-admin-apis-hsymG
git pull origin claude/hjf-queue-admin-apis-hsymG
```

### 3.2 方案：rsync 上传（备选，服务器无 Git 时使用）

```bash
# 在本地开发机执行
rsync -avz --progress \
  --exclude='.git' \
  --exclude='vendor/' \
  --exclude='view/admin/node_modules/' \
  --exclude='view/uniapp/node_modules/' \
  --exclude='.env' \
  --exclude='runtime/' \
  --exclude='public/uploads/' \
  ./pro_v3.5.1/ \
  user@your-server:/var/www/
```

> ⚠️ 注意 `--exclude='.env'`，生产环境的 `.env` 不能被开发环境覆盖

### 3.3 验证变更文件（同步后确认）

```bash
cd /var/www

# 确认以下新目录/文件存在
ls app/controller/admin/v1/hjf/    # 应有 MemberController.php / QueueController.php / PointsController.php
ls app/controller/api/v1/hjf/      # 应有 AssetsController.php / PointsController.php / QueueController.php
ls app/services/hjf/               # 应有5个 Services 文件
ls app/jobs/hjf/                   # 应有4个 Job 文件
ls database/hjf_migration.sql      # 迁移脚本
```

---

## 四、数据库迁移

> ⚠️ **在备份完成后、服务重启前执行，避免新代码访问不存在的表**

### 4.1 执行迁移脚本

```bash
cd /var/www

# 执行迁移（脚本已做幂等处理，重复执行安全）
mysql -u root -p crmeb_db < database/hjf_migration.sql
```

### 4.2 验证迁移结果

```bash
mysql -u root -p crmeb_db << 'SQL'
-- 确认新表存在
SELECT TABLE_NAME FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('eb_queue_pool', 'eb_points_release_log');

-- 确认 eb_user 新字段
SELECT COLUMN_NAME FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'eb_user'
  AND COLUMN_NAME IN ('member_level','no_assess','frozen_points','available_points');

-- 确认 eb_store_product 新字段
SELECT COLUMN_NAME FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'eb_store_product'
  AND COLUMN_NAME IN ('is_queue_goods','allow_pay_types');

-- 确认 eb_store_order 新字段
SELECT COLUMN_NAME FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'eb_store_order'
  AND COLUMN_NAME = 'is_queue_goods';

-- 确认系统配置写入（应返回15行）
SELECT menu_name, value FROM eb_system_config
  WHERE menu_name LIKE 'hjf_%' ORDER BY sort;
SQL
```

预期输出：
- 2张新表存在
- eb_user 4个新字段
- eb_store_product 2个新字段
- eb_store_order 1个新字段
- 15条 hjf_ 配置记录

---

## 五、后端依赖更新

### 5.1 检查 composer.json 是否有新依赖

```bash
cd /var/www
# 本次二次开发未新增 composer 依赖，但确认执行一次 install 确保一致性
composer install --no-dev --optimize-autoloader
```

### 5.2 清除 ThinkPHP 缓存

```bash
cd /var/www

# 清除路由缓存（新增了 hjf 路由，必须清除）
php think clear:cache

# 手动清除 runtime 缓存目录
rm -rf runtime/cache/*
rm -rf runtime/temp/*

# 如果使用了路由缓存
rm -f runtime/route.php
```

---

## 六、Admin 前端编译

Admin 前端新增了6个页面（公排/会员/积分管理）+ 3个 API 文件，需重新编译。

### 6.1 安装依赖并构建

```bash
cd /var/www/view/admin

# 安装依赖（如 node_modules 已存在可跳过）
npm install

# 生产编译
npm run build
```

编译产物输出到 `view/admin/dist/`

### 6.2 将构建产物部署到 Nginx 静态目录

```bash
# 根据服务器实际配置，将 dist/ 内容同步到 Nginx 服务的目录
# 如果 Nginx 直接指向 view/admin/dist/，则无需额外操作

# 若 Nginx 配置的静态目录是 /var/www/public/admin/
cp -r /var/www/view/admin/dist/* /var/www/public/admin/
```

> 具体路径以服务器 Nginx 配置中 `root` 指令为准。

---

## 七、UniApp H5 前端编译

UniApp 前端新增了约10个页面（公排/资产/积分/会员/引导），需重新编译 H5 版本。

### 方案 A：使用 HBuilderX（推荐，图形化操作）

1. 打开 HBuilderX，导入项目 `view/uniapp/`
2. 菜单 → 发行 → 网站 PC-Web 或 H5（勾选 H5）
3. 编译后将 `unpackage/dist/build/h5/` 内容上传到服务器 `public/` 对应目录

### 方案 B：使用 uni-app CLI（无图形界面服务器适用）

```bash
# 安装 @dcloudio/vue-cli-plugin-uni（如已安装可跳过）
cd /var/www/view/uniapp
npm install

# 编译 H5
npx vue-cli-service uni-build --platform h5

# 将产物部署到服务器 public 目录
cp -r dist/build/h5/* /var/www/public/h5/
```

> UniApp 小程序版本（微信/支付宝）需在对应开发者工具中重新上传，不在本次服务器部署范围内。

---

## 八、Swoole 服务重启

ThinkPHP + Swoole 需要重启才能加载新的 PHP 文件（PHP-Swoole 常驻内存，不像 PHP-FPM 每次请求重载）。

### 8.1 确认当前 Swoole 进程

```bash
ps aux | grep "php think" | grep -v grep
# 或
ps aux | grep swoole | grep -v grep
```

### 8.2 优雅重启（推荐，不中断现有连接）

```bash
# 找到主进程 PID
SWOOLE_PID=$(ps aux | grep "php think" | grep -v grep | awk '{print $2}' | head -1)

# 发送 USR1 信号优雅重启（仅适用于 Swoole Server 模式）
kill -USR1 $SWOOLE_PID
echo "已发送优雅重启信号，PID=${SWOOLE_PID}"
```

### 8.3 完全重启（若优雅重启不生效）

```bash
# Docker 环境
docker restart crmeb-app    # 容器名以实际为准
# 或
docker-compose restart app

# 非 Docker 环境
cd /var/www
php think stop               # 停止 Swoole
sleep 2
php think start              # 启动 Swoole
```

### 8.4 验证服务启动

```bash
curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:20199/api/ping
# 预期：200 或 404（非 500/502 即表示服务正常运行）
```

---

## 九、队列 Worker 重启

think-queue 的队列 Worker 也是常驻进程，新增的 `HjfOrderPayJob` 等 Job 类需要重启 Worker 才能加载。

### 9.1 确认当前 Worker 进程

```bash
ps aux | grep "queue:work\|queue:listen" | grep -v grep
```

### 9.2 重启 Worker

```bash
# 停止现有 Worker（发送 SIGTERM 允许当前任务执行完毕）
pkill -f "queue:work"
pkill -f "queue:listen"

sleep 3  # 等待当前任务完成

# 重新启动 Worker（后台运行）
cd /var/www
nohup php think queue:work \
  --queue CRMEB_PRO \
  --tries 3 \
  --sleep 3 \
  >> /var/log/crmeb_queue.log 2>&1 &

# 如有批量队列，额外启动
nohup php think queue:work \
  --queue CRMEB_PRO_BATCH \
  --tries 3 \
  --sleep 3 \
  >> /var/log/crmeb_queue_batch.log 2>&1 &

echo "Worker 已启动，PID: $!"
```

### 9.3 建议使用 Supervisor 管理 Worker 进程

```ini
; /etc/supervisor/conf.d/crmeb-queue.conf
[program:crmeb-queue-main]
command=php /var/www/think queue:work --queue=CRMEB_PRO --tries=3 --sleep=3
directory=/var/www
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/crmeb-queue.log

[program:crmeb-queue-batch]
command=php /var/www/think queue:work --queue=CRMEB_PRO_BATCH --tries=3 --sleep=3
directory=/var/www
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/crmeb-queue-batch.log
```

```bash
supervisorctl reread && supervisorctl update
supervisorctl restart crmeb-queue-main crmeb-queue-batch
```

---

## 十、定时任务注册

新增的每日积分释放任务需要注册到 cron。

```bash
# 编辑 crontab
crontab -e

# 添加以下行（每天凌晨 00:01 执行）
1 0 * * * cd /var/www && php think hjf:release-points >> /var/log/hjf_release.log 2>&1
```

验证注册：

```bash
crontab -l | grep hjf
# 应输出刚刚添加的行
```

---

## 十一、部署后冒烟验证

### 11.1 接口健康检查

```bash
# 设置环境变量（替换为真实 token）
export SERVER=https://your-domain.com
export USER_TOKEN="Bearer <用户token>"
export ADMIN_TOKEN="Bearer <管理员token>"

# 公排状态接口
curl -H "Authorization: $USER_TOKEN" $SERVER/api/hjf/queue/status
# 预期：{"status":200,"data":{...}}

# 资产总览接口
curl -H "Authorization: $USER_TOKEN" $SERVER/api/hjf/assets/overview
# 预期：{"status":200,"data":{"now_money":...,"frozen_points":...}}

# Admin 会员列表
curl -H "Authorization: $ADMIN_TOKEN" $SERVER/adminapi/hjf/member/list
# 预期：{"status":200,"data":{"list":[...],"count":...}}
```

### 11.2 运行 PHPUnit 冒烟测试

```bash
cd /var/www

# 设置环境变量
export HJF_API_BASE="https://your-domain.com/api"
export HJF_ADMIN_BASE="https://your-domain.com/adminapi"
export HJF_TOKEN="<用户token>"
export HJF_ADMIN_TOKEN="<管理员token>"

# 运行冒烟测试（跳过需要手动验证的 P4-05）
./vendor/bin/phpunit tests/hjf/SmokeTest.php --verbose
```

### 11.3 验证积分释放命令

```bash
cd /var/www

# 手动触发一次积分释放，验证命令可运行
php think hjf:release-points

# 预期输出：
# [HjfReleasePoints] 开始执行积分释放...
# [HjfReleasePoints] 完成：处理 X 人，共释放 X 积分，日期 2026-03-xx
```

### 11.4 验证支付回调入口（P4-04）

```bash
# 查看 Pay.php 已挂载
grep -n "HjfOrderPayJob" /var/www/app/listener/order/Pay.php
# 应输出包含 HjfOrderPayJob::dispatch 的行
```

---

## 十二、回滚方案

若部署后出现问题，按以下顺序回滚：

### 12.1 代码回滚（5分钟内可完成）

```bash
cd /var/www

# Git 回滚到上一个稳定版本
git stash || git reset --hard HEAD~1

# 或直接还原备份
tar -xzf /backup/code_before_hjf_${BACKUP_DATE}.tar.gz -C /var/www/
```

### 12.2 数据库回滚

数据库新增的表和字段不影响原有功能运行，**非必要不需要回滚**。

若确需回滚（建议先尝试仅回滚代码）：

```bash
mysql -u root -p crmeb_db << 'SQL'
-- 删除新表
DROP TABLE IF EXISTS eb_queue_pool;
DROP TABLE IF EXISTS eb_points_release_log;

-- 删除 eb_user 新字段
ALTER TABLE eb_user
  DROP COLUMN IF EXISTS member_level,
  DROP COLUMN IF EXISTS no_assess,
  DROP COLUMN IF EXISTS frozen_points,
  DROP COLUMN IF EXISTS available_points;

-- 删除 eb_store_product 新字段
ALTER TABLE eb_store_product
  DROP COLUMN IF EXISTS is_queue_goods,
  DROP COLUMN IF EXISTS allow_pay_types;

-- 删除 eb_store_order 新字段
ALTER TABLE eb_store_order
  DROP COLUMN IF EXISTS is_queue_goods;

-- 删除系统配置
DELETE FROM eb_system_config WHERE menu_name LIKE 'hjf_%';
SQL
```

### 12.3 重启服务（回滚后执行）

```bash
# 重启 Swoole
docker restart crmeb-app   # 或 kill -USR1 + 重启

# 重启 Worker
supervisorctl restart crmeb-queue-main crmeb-queue-batch
```

---

## 十三、部署检查清单

```
部署前
□ 数据库已备份（验证备份文件大小 > 0）
□ 关键代码已备份（Pay.php / route/ / admin dist）
□ 服务器 PHP/MySQL/Redis 版本确认符合要求

代码同步
□ git pull 成功 / rsync 上传完成
□ app/services/hjf/ 目录存在，含5个文件
□ app/jobs/hjf/ 目录存在，含4个文件
□ database/hjf_migration.sql 存在

数据库迁移
□ hjf_migration.sql 执行无 ERROR
□ eb_queue_pool 表存在
□ eb_points_release_log 表存在
□ eb_user.member_level 字段存在
□ eb_store_order.is_queue_goods 字段存在
□ eb_system_config 含15条 hjf_ 配置

后端部署
□ composer install 成功
□ php think clear:cache 执行
□ runtime/cache/ 已清空
□ Swoole 重启成功（http 200 响应）
□ Queue Worker 重启成功

前端部署
□ Admin npm run build 成功，dist/ 已更新
□ UniApp H5 编译并部署到 public/

定时任务
□ crontab 已添加 hjf:release-points（每日 00:01）

冒烟验证
□ /api/hjf/queue/status 返回 200
□ /api/hjf/assets/overview 返回 200
□ /adminapi/hjf/member/list 返回 200
□ php think hjf:release-points 命令可正常运行
□ PHPUnit SmokeTest 通过（或仅 skip，无 FAIL）
```

---

## 附录：变更文件清单（本次二次开发共44个文件）

### 后端新增文件（34个）

| 目录 | 文件 | 说明 |
|------|------|------|
| app/controller/api/v1/hjf/ | QueueController, PointsController, AssetsController | 用户端3个接口控制器 |
| app/controller/admin/v1/hjf/ | QueueController, MemberController, PointsController | Admin端3个接口控制器 |
| app/services/hjf/ | QueuePoolServices, PointsRewardServices, PointsReleaseServices, MemberLevelServices, HjfAssetsServices | 5个业务服务 |
| app/jobs/hjf/ | HjfOrderPayJob, QueueRefundJob, PointsReleaseJob, MemberLevelCheckJob | 4个异步Job |
| app/dao/hjf/ | QueuePoolDao, PointsReleaseLogDao | 2个DAO |
| app/model/hjf/ | QueuePool, PointsReleaseLog | 2个Model |
| app/command/ | HjfReleasePoints | 定时任务命令 |
| database/ | hjf_migration.sql | 数据库迁移脚本 |
| tests/hjf/ | 5个测试文件 | 单元测试+冒烟测试 |

### 后端修改文件（3个）

| 文件 | 修改内容 |
|------|---------|
| app/listener/order/Pay.php | 新增公排入队钩子（is_queue_goods=1 时派发 HjfOrderPayJob） |
| route/api.php | 注册用户端 hjf 路由组 |
| route/admin.php | 注册 Admin 端 hjf 路由组 |
| config/console.php | 注册 hjf:release-points 命令 |

### 前端文件（11个）

| 文件 | 修改内容 |
|------|---------|
| view/uniapp/api/hjfQueue.js 等3个 | USE_MOCK → false |
| view/admin/src/api/hjfQueue.js 等3个 | USE_MOCK → false |
| view/uniapp/main.js | 注册 HjfMemberBadge 全局组件 |
| view/uniapp/pages/goodList.vue | 公排商品角标 |
| view/uniapp/pages/user/index.vue | 会员等级徽章 |
| view/uniapp/pages/user_spread_money/index.vue | 积分替换佣金 |
| view/uniapp/pages/*/（新增约10个页面） | 公排/资产/积分/引导页 |
