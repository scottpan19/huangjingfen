# PHP 设置说明（CRMEB Pro v3.5）

本文档按 [CRMEB 官方：4.PHP设置](https://doc.crmeb.com/pro_s/prov35/31312) 整理，并补充本地/macOS 开发说明。

## 步骤总结

| 步骤 | 内容 |
|------|------|
| 一 | 安装 PHP 扩展：**fileinfo**、**redis**、**Swoole4** |
| 二 | 删除禁用函数中的 **proc_open** |
| 三 | 配置修改：脚本内存限制 **300M 及以上** |
| 四 | 非企业版：添加 `extension = swoole_loader80.so` 并重载配置；**企业版无需此步** |

---

## 步骤详解

### 1. 安装扩展：fileinfo、redis、Swoole4

- **宝塔**：软件商城 → PHP 设置 → 安装扩展 → 勾选 fileinfo、redis、Swoole4 → 安装。
- **macOS (Homebrew)**：
  ```bash
  # PHP 已安装前提下，扩展通常随 PHP 一起或可单独安装
  pecl install redis
  pecl install swoole
  ```
  若使用 `brew install php`，fileinfo 多已内置；需确认 `php.ini` 中已启用：
  ```ini
  extension=fileinfo
  extension=redis
  extension=swoole
  ```

### 2. 删除禁用函数 proc_open

- **宝塔**：PHP 设置 → 禁用函数 → 找到 `proc_open` → 删除 → 保存。
- **本地 php.ini**：打开 `php.ini`，在 `disable_functions` 中移除 `proc_open`（若存在）。  
  - 查找 php.ini 路径：`php --ini` 或 `php -i | grep "Loaded Configuration"`。

### 3. 脚本内存限制 300M 及以上

- **宝塔**：PHP 设置 → 配置修改 → 搜索 `memory_limit` → 改为 `300M` 或更高 → 保存。
- **本地 php.ini**：
  ```ini
  memory_limit = 300M
  ```

### 4. Swoole Loader（非企业版）

- **宝塔**：
  1. 将项目内 `help/swoole-loader/` 下对应 PHP 版本的 `.so` 文件复制到 PHP 扩展目录（如 `/www/server/php/80/lib/php/extensions/...`）。
  2. PHP 设置 → 配置文件 → 在文件末尾添加：
     ```ini
     extension = swoole_loader80.so
     ```
     （文件名按实际版本，如 80 即 PHP 8.0）
  3. 服务 → 重载配置 / 重启 PHP。

- **企业版**：无需配置 swoole_loader，使用开源 Swoole 扩展即可。本项目已做兼容，可仅安装开源 `swoole` 扩展。

---

## 验证与本地启动

### 一键检查（项目根目录执行）

```bash
cd /path/to/pro_v3.5.1
php -v
php -m | grep -E "fileinfo|redis|swoole"   # 应看到三行
php -r "echo 'memory_limit='.ini_get('memory_limit').PHP_EOL;"
php -r "echo in_array('proc_open', array_map('trim', explode(',', ini_get('disable_functions')))) ? 'proc_open 仍被禁用' : 'proc_open 已可用'; echo PHP_EOL;"
```

- **memory_limit** 若小于 300M，请修改 `php.ini` 中 `memory_limit = 300M`，或使用下方启动脚本（脚本会临时指定 300M）。
- **proc_open** 若被禁用，请在 `php.ini` 的 `disable_functions` 中删掉 `proc_open`。

### 启动 API 服务（推荐）

内存不足 300M 时，建议用 `-d memory_limit=300M` 启动：

```bash
cd /path/to/pro_v3.5.1
php -d memory_limit=300M think swoole
```

或使用项目自带脚本（同上效果）：

```bash
./help/start-api.sh
```

成功时终端会看到 `Starting swoole server...`，API 监听在 **http://127.0.0.1:20199**。

### 若仍报「Swoole Loader ext not installed」

部分 CRMEB 加密组件会检查商业扩展 **swoole_loader**。需安装本项目自带的 Loader 后再启动：

1. **查看 PHP 扩展目录**：`php -i | grep "extension_dir"`。
2. **复制对应 .so**（二选一）：
   - **Mac Intel**：`help/swoole_loader_mac/swoole_loader_80_nts.so`
   - **Mac M1/M2**：`help/swoole_loader_mac_m1/swoole_loader_80_nts_arm64.so`
   - **Linux**：`help/swoole_loader/swoole_loader80.so`（按版本选 80/81 等）
3. 在 **php.ini** 末尾添加（文件名与复制的 so 一致）：
   ```ini
   extension = swoole_loader_80_nts.so
   ```
   Mac M1 用：`extension = swoole_loader_80_nts_arm64.so`
4. 重载配置：`brew services restart php` 或重启 PHP-FPM/Web 服务；CLI 直接新开终端再执行 `./help/start-api.sh`。

   **本机已复制 .so**：若已把 `help/swoole_loader_mac/swoole_loader_80_nts.so` 复制到扩展目录，只需在 `php.ini` 或 `conf.d/` 中加入一行（可参考 `help/swoole-loader-local.ini`）后重载 PHP。
5. 或使用 **CRMEB 企业版**（通常无需 swoole_loader）。

---

## 参考

- [4.PHP设置 - CRMEB 文档](https://doc.crmeb.com/pro_s/prov35/31312)
- 项目根目录 `README.md` 中的「环境配置」章节
