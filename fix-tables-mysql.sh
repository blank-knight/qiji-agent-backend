#!/bin/bash
# 奇计后端 - 线上 MySQL 补建缺失表（幂等，可重复执行）
# 用法: 在站点根目录执行  bash fix-tables-mysql.sh
# 依赖: .env 已配置 type=mysql 及连接信息；mysql 客户端
# 背景: fa_admin_log / fa_attachment 两表只在本地 SQLite 手工建过(71f50ff)，
#       sql/qiji_admin.sql 遗漏了它们 → 线上 MySQL 部署后缺表。
#       症状: 个人资料页/管理日志 500，头像上传报错。
# DDL 存放在 sql/fix-missing-tables.sql（程序化生成并校验）

set -u
cd "$(dirname "$0")"
if [ ! -f application/database.php ] || [ ! -f sql/fix-missing-tables.sql ]; then
    echo "[错误] 请在站点根目录执行（需要 application/ 和 sql/ 目录）"
    exit 1
fi

ENV_FILE=".env"
if [ ! -f "$ENV_FILE" ]; then
    echo "[错误] 未找到 .env"
    exit 1
fi

# 从 .env 的 [database] 段读取连接信息
get_env() {
    # 取 [database] 段内指定 key（简单解析: 先定位段，再找 key）
    awk -v key="$1" '
        /^\[/ { indb = ($0 ~ /\[database\]/) }
        indb && $0 ~ "^"key"[[:space:]]*=" { gsub(/^[^=]*=[[:space:]]*/,""); gsub(/^[[:space:]]+|[[:space:]]+$/,""); print; exit }
    ' "$ENV_FILE"
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
    echo "[错误] .env 中 database/username 未配置"
    exit 1
fi

MYSQL_CMD=(mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME")

echo "目标库: ${DB_USER}@${DB_HOST}:${DB_PORT}/${DB_NAME}"
echo "=== 执行前表数量: $("${MYSQL_CMD[@]}" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';" 2>/dev/null) ==="

# 用临时 defaults 文件避免密码进 ps 列表（-p"$PASS" 会暴露在参数里）
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

echo "=== 补建前已有目标表: ==="
mysql --defaults-extra-file="$MYTMP" "$DB_NAME" -N -e "
SELECT table_name FROM information_schema.tables
WHERE table_schema='$DB_NAME' AND table_name IN ('fa_admin_log','fa_attachment','fa_user_token');" 2>/dev/null

mysql --defaults-extra-file="$MYTMP" "$DB_NAME" < sql/fix-missing-tables.sql || {
    echo "[错误] 建表 SQL 执行失败，请把上方报错发给开发者"
    exit 1
}

echo "=== 执行后表清单 ==="
mysql --defaults-extra-file="$MYTMP" "$DB_NAME" -e "SHOW TABLES;"
echo ""
echo "=== 完成。表清单应包含 fa_admin_log / fa_attachment ==="
