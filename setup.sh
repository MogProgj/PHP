#!/usr/bin/env bash
set -e
# install mysqli/pdo_mysql if missing (Codex images vary; this is illustrative)
php -v
# start PHP dev server in background for simple checks (no DB)
nohup php -S 0.0.0.0:8080 -t . >/tmp/php.log 2>&1 &
echo "Dev server on :8080"
