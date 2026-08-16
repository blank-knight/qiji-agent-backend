<?php
/**
 * SQLite 本地测试数据库初始化脚本
 * 用法: php init_sqlite.php
 */

$dbFile = __DIR__ . '/runtime/qiji_admin.db';
@mkdir(__DIR__ . '/runtime', 0777, true);
if (file_exists($dbFile)) unlink($dbFile);

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Creating tables...\n";

// 管理员表
$pdo->exec("CREATE TABLE fa_admin (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL DEFAULT '',
    nickname TEXT NOT NULL DEFAULT '',
    password TEXT NOT NULL DEFAULT '',
    salt TEXT NOT NULL DEFAULT '',
    avatar TEXT NOT NULL DEFAULT '',
    email TEXT NOT NULL DEFAULT '',
    mobile TEXT NOT NULL DEFAULT '',
    loginfailure INTEGER NOT NULL DEFAULT 0,
    logintime INTEGER DEFAULT NULL,
    loginip TEXT NOT NULL DEFAULT '',
    createtime INTEGER DEFAULT NULL,
    updatetime INTEGER DEFAULT NULL,
    token TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'normal'
)");

// 管理员分组
$pdo->exec("CREATE TABLE fa_admin_group (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pid INTEGER NOT NULL DEFAULT 0,
    name TEXT NOT NULL DEFAULT '',
    rules TEXT NOT NULL DEFAULT '',
    createtime INTEGER DEFAULT NULL,
    updatetime INTEGER DEFAULT NULL,
    status TEXT NOT NULL DEFAULT 'normal'
)");

// 权限规则
$pdo->exec("CREATE TABLE fa_admin_rule (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pid INTEGER NOT NULL DEFAULT 0,
    name TEXT NOT NULL DEFAULT '',
    title TEXT NOT NULL DEFAULT '',
    icon TEXT NOT NULL DEFAULT '',
    condition TEXT NOT NULL DEFAULT '',
    remark TEXT NOT NULL DEFAULT '',
    ismenu INTEGER NOT NULL DEFAULT 0,
    createtime INTEGER DEFAULT NULL,
    updatetime INTEGER DEFAULT NULL,
    weigh INTEGER NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'normal'
)");

// 系统配置
$pdo->exec("CREATE TABLE fa_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL DEFAULT '',
    \"group\" TEXT NOT NULL DEFAULT '',
    title TEXT NOT NULL DEFAULT '',
    tip TEXT NOT NULL DEFAULT '',
    type TEXT NOT NULL DEFAULT '',
    value TEXT NOT NULL DEFAULT '',
    content TEXT NOT NULL DEFAULT '',
    rule TEXT NOT NULL DEFAULT '',
    extend TEXT NOT NULL DEFAULT '',
    setting TEXT NOT NULL DEFAULT '',
    createtime INTEGER DEFAULT NULL,
    updatetime INTEGER DEFAULT NULL
)");

// 用户表
$pdo->exec("CREATE TABLE fa_user (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL DEFAULT '',
    nickname TEXT NOT NULL DEFAULT '',
    password TEXT NOT NULL DEFAULT '',
    salt TEXT NOT NULL DEFAULT '',
    email TEXT NOT NULL DEFAULT '',
    mobile TEXT NOT NULL DEFAULT '',
    avatar TEXT NOT NULL DEFAULT '',
    score INTEGER NOT NULL DEFAULT 0,
    agent_id INTEGER NOT NULL DEFAULT 0,
    is_custom_key INTEGER NOT NULL DEFAULT 0,
    api_key_encrypted TEXT NOT NULL DEFAULT '',
    mode TEXT NOT NULL DEFAULT 'trial',
    loginfailure INTEGER NOT NULL DEFAULT 0,
    loginfailuretime INTEGER DEFAULT NULL,
    successions INTEGER NOT NULL DEFAULT 0,
    maxsuccessions INTEGER NOT NULL DEFAULT 0,
    jointime INTEGER DEFAULT NULL,
    logintime INTEGER DEFAULT NULL,
    prevtime INTEGER DEFAULT NULL,
    loginip TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'normal',
    createtime INTEGER DEFAULT NULL,
    updatetime INTEGER DEFAULT NULL
)");

// 代理表
$pdo->exec("CREATE TABLE fa_agent (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL DEFAULT '',
    agent_id INTEGER NOT NULL DEFAULT 0,
    admin_id INTEGER NOT NULL DEFAULT 0,
    mobile TEXT NOT NULL DEFAULT '',
    score INTEGER NOT NULL DEFAULT 0,
    api_key TEXT NOT NULL DEFAULT '',
    is_custom_key INTEGER NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'normal',
    createtime INTEGER DEFAULT NULL,
    updatetime INTEGER DEFAULT NULL
)");

// 邀请码
$pdo->exec("CREATE TABLE fa_agent_invite (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invite_code TEXT NOT NULL DEFAULT '',
    agent_id INTEGER NOT NULL DEFAULT 0,
    max_count INTEGER NOT NULL DEFAULT 0,
    used_count INTEGER NOT NULL DEFAULT 0,
    expiretime INTEGER NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'normal',
    createtime INTEGER DEFAULT NULL,
    updatetime INTEGER DEFAULT NULL
)");

// 积分日志
$pdo->exec("CREATE TABLE fa_user_score_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL DEFAULT 0,
    score INTEGER NOT NULL DEFAULT 0,
    before_score INTEGER NOT NULL DEFAULT 0,
    after_score INTEGER NOT NULL DEFAULT 0,
    model TEXT NOT NULL DEFAULT '',
    input_tokens INTEGER NOT NULL DEFAULT 0,
    output_tokens INTEGER NOT NULL DEFAULT 0,
    request_id TEXT NOT NULL DEFAULT '',
    memo TEXT NOT NULL DEFAULT '',
    createtime INTEGER DEFAULT NULL
)");
$pdo->exec("CREATE UNIQUE INDEX idx_request_id ON fa_user_score_log(request_id)");

// 用户Token
$pdo->exec("CREATE TABLE fa_user_token (
    token TEXT PRIMARY KEY,
    user_id INTEGER NOT NULL DEFAULT 0,
    createtime INTEGER DEFAULT NULL,
    expiretime INTEGER DEFAULT NULL
)");

// 版本表
$pdo->exec("CREATE TABLE fa_version (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    version TEXT NOT NULL DEFAULT '',
    newversion TEXT NOT NULL DEFAULT '',
    downloadurl TEXT NOT NULL DEFAULT '',
    requireversion TEXT NOT NULL DEFAULT '',
    packagesize TEXT NOT NULL DEFAULT '',
    enforce INTEGER NOT NULL DEFAULT 0,
    upgradetext TEXT NOT NULL DEFAULT '',
    createtime INTEGER DEFAULT NULL,
    updatetime INTEGER DEFAULT NULL,
    weigh INTEGER NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'normal'
)");

echo "Inserting initial data...\n";
$now = time();

// 默认管理员 admin/123456 (双重MD5)
$hashedPwd = md5(md5('123456'));
$pdo->exec("INSERT INTO fa_admin (id, username, nickname, password, salt, avatar, email, loginfailure, logintime, createtime, updatetime, token, status)
VALUES (1, 'admin', 'Admin', '{$hashedPwd}', '', '', 'admin@admin.com', 0, 0, {$now}, {$now}, '', 'normal')");

// 角色组
$pdo->exec("INSERT INTO fa_admin_group (id, pid, name, rules, createtime, updatetime, status)
VALUES (1, 0, '超级管理员', '*', {$now}, {$now}, 'normal')");

// 菜单规则 [pid, name(路由), title(中文名), icon, condition, remark, ismenu, createtime, updatetime, weigh, status]
$menus = [
    [0, 'dashboard', '控制台', 'fa fa-dashboard', '', '', 1, $now, $now, 999, 'normal'],
    [0, 'general', '常规管理', 'fa fa-cogs', '', '', 1, $now, $now, 100, 'normal'],
    [1, 'dashboard/index', '控制台', 'fa fa-dashboard', '', '', 1, $now, $now, 999, 'normal'],
    [2, 'general/config', '系统配置', 'fa fa-gears', '', '', 1, $now, $now, 90, 'normal'],
    [0, 'agent', '代理管理', 'fa fa-users', '', '', 1, $now, $now, 80, 'normal'],
    [5, 'agent/agent', '代理列表', 'fa fa-list', '', '', 1, $now, $now, 99, 'normal'],
    [5, 'agent/agentinvite', '邀请码', 'fa fa-ticket', '', '', 1, $now, $now, 98, 'normal'],
    [0, 'user', '用户管理', 'fa fa-user', '', '', 1, $now, $now, 90, 'normal'],
    [8, 'user/user', '用户列表', 'fa fa-list', '', '', 1, $now, $now, 99, 'normal'],
    [0, 'statistics', '统计中心', 'fa fa-bar-chart', '', '', 1, $now, $now, 70, 'normal'],
    [10, 'statistics/scorelog', 'Token消耗记录', 'fa fa-list', '', '', 1, $now, $now, 99, 'normal'],
    [0, 'version', '版本管理', 'fa fa-upload', '', '', 1, $now, $now, 60, 'normal'],
    [12, 'version/index', '版本列表', 'fa fa-list', '', '', 1, $now, $now, 99, 'normal'],
    [0, 'auth', '权限管理', 'fa fa-shield', '', '', 1, $now, $now, 50, 'normal'],
    [14, 'auth/admin', '管理员管理', 'fa fa-users', '', '', 1, $now, $now, 99, 'normal'],
    [14, 'auth/group', '角色组', 'fa fa-users', '', '', 1, $now, $now, 98, 'normal'],
    [14, 'auth/rule', '菜单规则', 'fa fa-list', '', '', 1, $now, $now, 97, 'normal'],
];
$stmt = $pdo->prepare("INSERT INTO fa_admin_rule (pid, name, title, icon, condition, remark, ismenu, createtime, updatetime, weigh, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
foreach ($menus as $m) {
    $stmt->execute($m);
}

// 系统配置
$configs = [
    ['name', 'basic', '站点名称', '', 'string', 'QIJI Agent', '', '', '', '', $now, $now],
    ['token_per_score', 'basic', 'Token/积分比例', '每多少Token消耗1积分', 'number', '10000', '', '', '', '', $now, $now],
    ['default_api_key', 'basic', '默认API Key', '系统兜底Key', 'string', 'sk-demo-key', '', '', '', '', $now, $now],
    ['trial_score', 'basic', '体验用户初始积分', '', 'number', '10', '', '', '', '', $now, $now],
];
$stmt = $pdo->prepare('INSERT INTO fa_config (name, "group", title, tip, type, value, content, rule, extend, setting, createtime, updatetime) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
foreach ($configs as $c) {
    $stmt->execute($c);
}

// 测试代理 (api_key 需 base64 编码存储，与 decryptKey 的 base64_decode 对应)
$agentKey = base64_encode('sk-agent-key-a');
$pdo->exec("INSERT INTO fa_agent (id, name, agent_id, admin_id, mobile, score, api_key, is_custom_key, status, createtime, updatetime)
VALUES (1, '测试代理A', 0, 1, '13800000001', 100000, '{$agentKey}', 1, 'normal', {$now}, {$now})");

// 测试邀请码
$pdo->exec("INSERT INTO fa_agent_invite (id, invite_code, agent_id, max_count, used_count, expiretime, status, createtime, updatetime)
VALUES (1, 'QIJI001', 1, 100, 0, 0, 'normal', {$now}, {$now})");

// 测试用户
$pdo->exec("INSERT INTO fa_user (id, username, nickname, password, salt, mobile, score, agent_id, is_custom_key, mode, status, createtime, updatetime)
VALUES (1, '13800138000', '测试用户', '{$hashedPwd}', '', '13800138000', 8500, 1, 0, 'formal', 'normal', {$now}, {$now})");

// 测试版本
$pdo->exec("INSERT INTO fa_version (id, version, newversion, downloadurl, requireversion, packagesize, enforce, upgradetext, createtime, updatetime, weigh, status)
VALUES (1, '1.0.0', '1.1.0', 'https://example.com/download/qiji-agent-1.1.0.exe', '1.0.0', '85MB', 0, '1. 新增 Skill Hub\n2. 修复若干bug', {$now}, {$now}, 99, 'normal')");

// ===== 标准 RBAC 表（FastAdmin auth_* 系列，核心 Auth 库依赖） =====
// 权限分组
$pdo->exec("CREATE TABLE fa_auth_group (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pid INTEGER NOT NULL DEFAULT 0,
    name TEXT NOT NULL DEFAULT '',
    rules TEXT NOT NULL DEFAULT '',
    createtime INTEGER DEFAULT NULL,
    updatetime INTEGER DEFAULT NULL,
    status TEXT NOT NULL DEFAULT 'normal'
)");
// 超级管理员组（rules='*' 拥有全部权限）
$pdo->exec("INSERT INTO fa_auth_group (id, pid, name, rules, createtime, updatetime, status)
VALUES (1, 0, '超级管理员', '*', {$now}, {$now}, 'normal')");

// 管理员-分组关联
$pdo->exec("CREATE TABLE fa_auth_group_access (
    uid INTEGER NOT NULL,
    group_id INTEGER NOT NULL
)");
$pdo->exec("INSERT INTO fa_auth_group_access (uid, group_id) VALUES (1, 1)");

// 权限规则/菜单（从 fa_admin_rule 复制，补充 menutype/extend 字段）
$pdo->exec("CREATE TABLE fa_auth_rule (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pid INTEGER NOT NULL DEFAULT 0,
    name TEXT NOT NULL DEFAULT '',
    title TEXT NOT NULL DEFAULT '',
    icon TEXT NOT NULL DEFAULT '',
    condition TEXT NOT NULL DEFAULT '',
    remark TEXT NOT NULL DEFAULT '',
    ismenu INTEGER NOT NULL DEFAULT 0,
    menutype TEXT NOT NULL DEFAULT '',
    extend TEXT NOT NULL DEFAULT '',
    createtime INTEGER DEFAULT NULL,
    updatetime INTEGER DEFAULT NULL,
    weigh INTEGER NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'normal'
)");
$pdo->exec("INSERT INTO fa_auth_rule (pid, name, title, icon, condition, remark, ismenu, menutype, extend, createtime, updatetime, weigh, status)
SELECT pid, name, title, icon, condition, remark, ismenu, '', '', createtime, updatetime, weigh, status FROM fa_admin_rule");

echo "Done! Database created at: {$dbFile}\n";
echo "Admin login: admin / 123456\n";
