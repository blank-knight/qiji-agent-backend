#!/usr/bin/env bash
# +----------------------------------------------------------------------
# | 冒烟测试脚本 - 每次发布前运行
# |
# | 用途：30秒内自动验证核心链路，防止"改了新功能，改坏老功能"
# | 覆盖：三角色登录 / 核心页面 / 权限隔离 / 越权拦截 / 客户端API
# |
# | 用法：
# |   ./smoke-test.sh              # 测试 http://127.0.0.1:8082
# |   BASE_URL=http://xx ./smoke-test.sh
# |
# | 退出码：0=全部通过  1=有失败（阻止发布）
# +----------------------------------------------------------------------

set -u

BASE_URL="${BASE_URL:-http://127.0.0.1:8082}"
TMPDIR_TEST="$(mktemp -d)"
trap 'rm -rf "$TMPDIR_TEST"' EXIT

# 测试账号（与数据库中保持一致）
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASS="${ADMIN_PASS:-123456}"
TIEPAI_USER="${TIEPAI_USER:-tiepai2}"
TIEPAI_PASS="${TIEPAI_PASS:-123456}"
AGENT_USER="${AGENT_USER:-agent1}"
AGENT_PASS="${AGENT_PASS:-123456}"
CLIENT_USER="${CLIENT_USER:-13800138000}"
CLIENT_PASS="${CLIENT_PASS:-123456}"

PASS=0
FAIL=0
FAILED_ITEMS=()

GREEN='\033[0;32m'; RED='\033[0;31m'; YELLOW='\033[1;33m'; NC='\033[0m'

ok()   { PASS=$((PASS+1)); echo -e "  ${GREEN}[PASS]${NC} $1"; }
bad()  { FAIL=$((FAIL+1)); FAILED_ITEMS+=("$1"); echo -e "  ${RED}[FAIL]${NC} $1"; }
info() { echo -e "${YELLOW}==>${NC} $1"; }

# ---------------------------------------------------------------
# 后台登录：成功返回 {"code":1,...}，失败返回 code:0 或页面错误
# 参数：$1=账号 $2=密码 $3=cookie文件
# ---------------------------------------------------------------
admin_login() {
    local user="$1" pass="$2" ck="$3"
    curl -s --noproxy '*' -c "$ck" -o /dev/null "$BASE_URL/admin.php/index/login.html"
    curl -s --noproxy '*' -c "$ck" -b "$ck" -X POST \
        -d "username=$user&password=$pass" \
        "$BASE_URL/admin.php/index/login" 2>/dev/null \
        | grep -o '"code":[0-9]*' | head -1 | grep -q '"code":1'
}

# ---------------------------------------------------------------
# 页面检查：登录态下访问页面
# 参数：$1=描述 $2=cookie文件 $3=URL路径
# ---------------------------------------------------------------
check_page() {
    local desc="$1" ck="$2" path="$3"
    local http
    http=$(curl -s --noproxy '*' -b "$ck" -o /dev/null -w "%{http_code}" \
        -H "Sec-Fetch-Dest: iframe" "$BASE_URL$path")
    if [ "$http" = "200" ]; then
        ok "$desc (HTTP $http)"
    else
        bad "$desc (HTTP $http, 期望 200)"
    fi
}

# 检查页面应被拒绝（越权防护）
# 参数：$1=描述 $2=cookie文件 $3=URL路径
check_denied() {
    local desc="$1" ck="$2" path="$3"
    local body http
    body=$(curl -s --noproxy '*' -b "$ck" -H "Sec-Fetch-Dest: iframe" "$BASE_URL$path")
    http="?"
    if echo "$body" | grep -q "no permission"; then
        ok "$desc (已拦截)"
    elif echo "$body" | grep -q '"code":0'; then
        ok "$desc (已拦截)"
    else
        # 200 且无拦截字样 = 可能越权成功，需人工确认
        bad "$desc (未被拦截! 请人工检查是否越权)"
    fi
}

# 检查页面内容包含某关键字
# 参数：$1=描述 $2=cookie文件 $3=URL路径 $4=关键字
check_content() {
    local desc="$1" ck="$2" path="$3" keyword="$4"
    local body
    body=$(curl -s --noproxy '*' -b "$ck" -H "Sec-Fetch-Dest: iframe" "$BASE_URL$path")
    if echo "$body" | grep -q "$keyword"; then
        ok "$desc"
    else
        bad "$desc (页面缺少关键字: $keyword)"
    fi
}

echo "======================================================"
echo " 冒烟测试  $BASE_URL"
echo " $(date '+%Y-%m-%d %H:%M:%S')"
echo "======================================================"

# ================= 1. 服务器存活 =================
info "服务器存活检查"
srv_code=$(curl -s --noproxy '*' -o /dev/null -w "%{http_code}" "$BASE_URL/")
if [ "$srv_code" != "200" ] && [ "$srv_code" != "302" ]; then
    bad "服务器无响应 (HTTP $srv_code)，请先启动: php -S 127.0.0.1:8082 public/router.php"
    echo -e "\n${RED}服务器未启动，测试中止${NC}"
    exit 1
else
    ok "服务器响应正常 (HTTP $srv_code)"
fi

# ================= 2. 超管登录 + 核心页面 =================
info "[角色1] 超管 ($ADMIN_USER) 登录"
if admin_login "$ADMIN_USER" "$ADMIN_PASS" "$TMPDIR_TEST/admin.ck"; then
    ok "超管登录成功"
else
    bad "超管登录失败（检查账号密码/登录接口）"
fi

info "[角色1] 超管核心页面"
check_page "控制台"            "$TMPDIR_TEST/admin.ck" "/admin.php/dashboard.html?addtabs=1"
check_content "控制台统计卡片"   "$TMPDIR_TEST/admin.ck" "/admin.php/dashboard.html?addtabs=1" "总用户数"
check_page "用户列表"           "$TMPDIR_TEST/admin.ck" "/admin.php/user/user?addtabs=1"
check_page "代理列表"           "$TMPDIR_TEST/admin.ck" "/admin.php/agent/agent?addtabs=1"
check_page "消费记录"           "$TMPDIR_TEST/admin.ck" "/admin.php/statistics/scorelog?addtabs=1"
check_page "角色组管理"         "$TMPDIR_TEST/admin.ck" "/admin.php/auth/group?addtabs=1"
check_page "系统配置"           "$TMPDIR_TEST/admin.ck" "/admin.php/general/config?addtabs=1"

# ================= 3. 贴牌商登录 + 数据隔离 =================
info "[角色2] 贴牌商 ($TIEPAI_USER) 登录"
if admin_login "$TIEPAI_USER" "$TIEPAI_PASS" "$TMPDIR_TEST/tiepai.ck"; then
    ok "贴牌商登录成功"
else
    bad "贴牌商登录失败"
fi

info "[角色2] 贴牌商页面 + 数据隔离"
check_page "控制台"            "$TMPDIR_TEST/tiepai.ck" "/admin.php/dashboard.html?addtabs=1"
check_content "积分横幅显示"     "$TMPDIR_TEST/tiepai.ck" "/admin.php/dashboard.html?addtabs=1" "剩余积分"
check_page "用户列表"           "$TMPDIR_TEST/tiepai.ck" "/admin.php/user/user?addtabs=1"
check_page "代理列表"           "$TMPDIR_TEST/tiepai.ck" "/admin.php/agent/agent?addtabs=1"
check_denied "越权:系统配置"    "$TMPDIR_TEST/tiepai.ck" "/admin.php/general/config?addtabs=1"
check_denied "越权:角色组"      "$TMPDIR_TEST/tiepai.ck" "/admin.php/auth/group?addtabs=1"

# ================= 4. 代理登录 + 越权拦截 =================
info "[角色3] 代理 ($AGENT_USER) 登录"
if admin_login "$AGENT_USER" "$AGENT_PASS" "$TMPDIR_TEST/agent.ck"; then
    ok "代理登录成功"
else
    bad "代理登录失败"
fi

info "[角色3] 代理页面 + 越权拦截"
check_page "控制台"            "$TMPDIR_TEST/agent.ck" "/admin.php/dashboard.html?addtabs=1"
check_content "积分横幅显示"     "$TMPDIR_TEST/agent.ck" "/admin.php/dashboard.html?addtabs=1" "剩余积分"
check_page "用户列表"           "$TMPDIR_TEST/agent.ck" "/admin.php/user/user?addtabs=1"
check_denied "越权:代理列表"    "$TMPDIR_TEST/agent.ck" "/admin.php/agent/agent?addtabs=1"
check_denied "越权:系统配置"    "$TMPDIR_TEST/agent.ck" "/admin.php/general/config?addtabs=1"

# ================= 5. 客户端 API 链路 =================
info "[API] 客户端登录 → 查额度 → 查Key"
api_login=$(curl -s --noproxy '*' -X POST \
    -H "Content-Type: application/json" \
    -d "{\"mobile\":\"$CLIENT_USER\",\"password\":\"$CLIENT_PASS\"}" \
    "$BASE_URL/api/client/v1/auth/login" 2>/dev/null)

if echo "$api_login" | grep -q '"code":1'; then
    ok "客户端登录成功"
    token=$(echo "$api_login" | grep -oP '"token"\s*:\s*"[^"]+"' | head -1 | grep -oP '"[^"]+"' | tail -1 | tr -d '"')
    if [ -n "$token" ]; then
        quota=$(curl -s --noproxy '*' -H "Authorization: Bearer $token" \
            "$BASE_URL/api/client/v1/quota" 2>/dev/null)
        if echo "$quota" | grep -q '"code":1'; then
            ok "额度查询成功"
        else
            bad "额度查询失败: $(echo $quota | head -c 100)"
        fi
        apikey=$(curl -s --noproxy '*' -H "Authorization: Bearer $token" \
            "$BASE_URL/api/client/v1/apikey" 2>/dev/null)
        if echo "$apikey" | grep -q '"code":1'; then
            ok "API Key 查询成功"
        else
            bad "API Key 查询失败: $(echo $apikey | head -c 100)"
        fi
    else
        bad "无法从登录响应中提取 token"
    fi
else
    bad "客户端登录失败: $(echo $api_login | head -c 100)"
fi

# 无效 token 必须返回 401
unauth=$(curl -s --noproxy '*' -o /dev/null -w "%{http_code}" \
    -H "Authorization: Bearer invalid-token-test" \
    "$BASE_URL/api/client/v1/quota")
if [ "$unauth" = "401" ]; then
    ok "无效token返回401"
else
    bad "无效token返回 HTTP $unauth (期望 401)"
fi

# ================= 6. 编辑用户页（带参数路径） =================
info "带参数路径检查（历史bug: .html后缀/数字ID段导致权限匹配失败）"
check_page "编辑用户(ids=10)" "$TMPDIR_TEST/admin.ck" "/admin.php/user/user/edit/ids/10?dialog=1"

# ================= 7. 结果汇总 =================
echo "======================================================"
if [ $FAIL -eq 0 ]; then
    echo -e " ${GREEN}全部通过: $PASS 项测试 ✓${NC}"
    echo " 可以发布: git push && 服务器 git pull"
    exit 0
else
    echo -e " ${RED}失败 $FAIL 项 / 共 $((PASS+FAIL)) 项 ✗${NC}"
    for item in "${FAILED_ITEMS[@]}"; do
        echo -e "   ${RED}-${NC} $item"
    done
    echo ""
    echo " 请修复后重新运行，未全部通过前不要发布到生产"
    exit 1
fi
