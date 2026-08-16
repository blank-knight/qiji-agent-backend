#!/bin/bash
# 奇计后端 - 线上 MySQL 补菜单/权限表结构与种子（幂等，可重复执行）
# 用法: 在站点根目录执行  bash fix-menu-seeds-mysql.sh
# 依赖: .env 已配置 type=mysql；mysql 客户端
# 背景: 线上 fa_auth_rule 是 13 列老结构(缺 menutype/extend/url)，最新代码需要 15 列；
#       种子仅 15 行老菜单，缺控制台/代理/统计/版本/权限等全部业务菜单。
#       fa_admin_rule / fa_admin_group 两表线上缺失。
# 策略: fa_auth_rule 先 RENAME 备份再重建 15 列 + 33 行本地权威种子(可回滚)；
#       其余表建表+种子。SQL 在 sql/fix-menu-seeds.sql（程序化生成并校验）。

set -u
cd "$(dirname "$0")"
if [ ! -f application/database.php ] || [ ! -f sql/fix-menu-seeds.sql ]; then
    echo "[错误] 请在站点根目录执行（需要 application/ 和 sql/ 目录）"
    exit 1
fi
if [ ! -f .env ]; then
    echo "[错误] 未找到 .env"
    exit 1
fi

get_env() {
    awk -v key="$1" '
        /^\[/ { indb = ($0 ~ /\[database\]/) }
        indb && $0 ~ "^"key"[[:space:]]*=" { gsub(/^[^=]*=[[:space:]]*/,""); gsub(/^[[:space:]]+|[[:space:]]+$/,""); print; exit }
    ' .env
}

DB_TYPE=$(get_env type)
if [ "$DB_TYPE" != "mysql" ]; then
    echo "[错误] .env 中 database.type=$DB_TYPE（非 mysql）"
    exit 1
fi
DB_HOST=$(get_env hostname); DB_HOST=${DB_HOST:-127.0.0.1}
DB_NAME=$(get_env database)
DB_USER=$(get_env username)
DB_PASS=$(get_env password)
DB_PORT=$(get_env hostport); DB_PORT=${DB_PORT:-3306}
if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ]; then
    echo "[错误] .env database/username 未配置"
    exit 1
fi

MYTMP=$(mktemp /tmp/qiji-mysql-XXXXXX.cnf)
trap 'rm -f "$MYTMP"' EXIT
cat > "$MYTMP" <<CNFEOF
[client]
host=$DB_HOST
port=$DB_PORT
user=$DB_USER
password=$DB_PASS
CNFEOF
chmod 600 "$MYTMP"

echo "目标库: ${DB_USER}@${DB_HOST}:${DB_PORT}/${DB_NAME}"

# 执行前快照
echo "=== 执行前: 菜单数 / auth_rule列数 ==="
mysql --defaults-extra-file="$MYTMP" "$DB_NAME" -N -e "
SELECT CONCAT('fa_auth_rule 行数: ', COUNT(*)) FROM fa_auth_rule;
SELECT CONCAT('fa_auth_rule 列数: ', COUNT(*)) FROM information_schema.columns
WHERE table_schema='$DB_NAME' AND table_name='fa_auth_rule';" 2>/dev/null

mysql --defaults-extra-file="$MYTMP" "$DB_NAME" < sql/fix-menu-seeds.sql || {
    echo "[错误] 种子 SQL 执行失败，请把上方报错发给开发者"
    exit 1
}

echo "=== 执行后验证 ==="
mysql --defaults-extra-file="$MYTMP" "$DB_NAME" -N -e "
SELECT CONCAT('fa_auth_rule 行数: ', COUNT(*)) FROM fa_auth_rule;
SELECT CONCAT('fa_auth_rule 列数: ', COUNT(*)) FROM information_schema.columns
WHERE table_schema='$DB_NAME' AND table_name='fa_auth_rule';
SELECT CONCAT('fa_admin_rule 行数: ', COUNT(*)) FROM fa_admin_rule;
SELECT CONCAT('fa_admin_group 行数: ', COUNT(*)) FROM fa_admin_group;
SELECT CONCAT('一级菜单: ', GROUP_CONCAT(title ORDER BY weigh DESC SEPARATOR ' | ')) FROM fa_auth_rule WHERE ismenu=1 AND pid=0;" 2>/dev/null

echo ""
echo "=== 完成。左侧菜单应恢复: 控制台/代理管理/用户管理/统计中心/版本管理/权限管理/常规管理 ==="
echo "=== 如需回滚: 见 sql/fix-menu-seeds.sql 尾部注释 ==="
