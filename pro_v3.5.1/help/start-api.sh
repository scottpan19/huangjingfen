#!/usr/bin/env bash
# 按 help/PHP-Setup.md 要求，以 memory_limit=300M 启动 Swoole API 服务
# 用法：在项目根目录执行 ./help/start-api.sh，或先 cd pro_v3.5.1 再执行

set -e
cd "$(dirname "$0")/.."
php -d memory_limit=300M think swoole
