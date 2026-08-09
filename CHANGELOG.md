# 奇迹智能体后台 - 变更记录

## 最新提交（2026-08-09）

### feat: 实现四层层级体系 + 权限隔离 + Dashboard 增强

#### 新增功能

1. **四层层级体系（超管→贴牌→代理→用户）**
   - 三种预设角色组：超级管理员、贴牌商、代理
   - 每种角色有固定的权限规则，无需手动编辑
   - 贴牌商可管理旗下代理和用户；代理只能管理自己的用户

2. **Dashboard 按角色数据隔离**
   - 统计卡片、趋势图、模型占比按当前账号的数据范围显示
   - 贴牌/代理首页显示积分横幅（剩余积分 + 旗下用户数）
   - 超管看全局，贴牌看子树，代理看自己
   - 代理数统计排除自己，避免多算

3. **用户自定义 API Key**
   - 编辑用户表单新增"自定义API Key"选项
   - 继承上级（默认）或独立 Key
   - 代理/贴牌均可为用户设置

4. **贴牌商可管理代理**
   - 代理列表显示编辑/删除按钮（修复权限路径检测 bug）
   - 编辑代理页面支持修改所有字段

#### Bug 修复

5. **登录"令牌数据无效"**
   - 去除登录 validator 的 `__token__` 校验，避免 ThinkPHP token 机制冲突

6. **权限检查 URL 路径匹配失败**
   - `pathinfo()` 返回 `dashboard.html`（带后缀），自动 strip `.html`
   - 多级控制器路径（`agent/agent/edit`）中数字 ID 段自动去除

7. **`.html` 后缀导致 action 解析错误**
   - ThinkPHP 5.0 把 `add.html` 当作 action 名，报"方法不存在"
   - 入口文件 `admin.php` 在框架启动前剥离 `.html` 后缀

8. **角色组编辑 500 错误**
   - `$this->token()` 方法不存在导致 add/edit 崩溃，已移除

9. **Agent 模板 `username` 属性不存在**
   - Agent 表无 `username` 字段，从 `fa_admin` 表关联查出

10. **ECharts CDN 不可达**
    - 改用本地 `assets/js/echarts.min.js`

11. **趋势图数据全为 0**
    - 原始 SQL 表名缺少 `fa_` 前缀，SQL 静默失败

---

## 提交 73331eb（2026-08-09）

### fix: 修复后台管理多项问题（搜索、上传、API、模板）

#### API 接口修复

### 1. HTTP 状态码 Bug（致命）
- **问题**: POST 接口把业务 code 当作 HTTP 状态码返回（如 `HTTP/1.0 1 Unknown`），导致客户端 fetch() 直接失败
- **修复**: 所有业务接口统一返回 HTTP 200，业务状态码放在 JSON body 的 `code` 字段中
- **影响文件**: `application/common/controller/Api.php`

### 2. 401 鉴权拦截
- **问题**: 无效 token 返回 HTTP 200 + `{"code":401}`，客户端的 `res.status === 401` 拦截永远不触发
- **修复**: 未登录请求返回 HTTP 401 Unauthorized，客户端能正确自动弹登录页
- **影响文件**: `application/common/controller/Api.php`

### 3. Token 配置缺失
- **问题**: `application/extra/token.php` 不存在，登录成功后 Token::set() 崩溃（HTTP 000 无响应）
- **修复**: 创建 token 配置文件
- **新增文件**: `application/extra/token.php`

---

## 二、后台管理界面修复

### 4. 列表搜索功能（核心）
- **问题**: 
  - 搜索按钮不存在于 `build_toolbar` 中
  - `buildparams()` 中 filter/op JSON 被 HTML 实体编码（`&quot;`），`json_decode` 失败
  - `$where` 数组格式不兼容 ThinkPHP5.0
- **修复**:
  - `build_toolbar()` 自动为所有列表添加 commonsearch 按钮
  - `buildparams()` 添加 `htmlspecialchars_decode()` 还原 HTML 实体
  - `$where` 使用关联数组格式 `['字段' => ['操作符', '值']]`
- **影响文件**: `application/common.php`, `application/common/controller/Backend.php`

### 5. 文件上传功能
- **问题**: 
  - `Ajax/upload` 方法不存在
  - `Config.upload` 配置未传给前端，Dropzone 初始化失败
  - `success()` 参数顺序错误（数组传给了 $url 参数）
- **修复**:
  - 实现 `Ajax::upload()` 方法，支持图片上传到 `public/uploads/`
  - upload 配置放入 `$site['upload']`（因为前端 `window.Config = config.site`）
  - 修正 `success($msg, null, $data)` 调用方式
- **影响文件**: `application/admin/controller/Ajax.php`, `application/common/controller/Backend.php`, `public/assets/js/backend/general/profile.js`

### 6. F5 刷新丢失侧边栏
- **问题**: 页面在 iframe 内加载时 URL 带 `addtabs=1`，F5 刷新后只渲染内容部分
- **修复**: `IS_ADDTABS` 需同时满足 URL 参数 + `Sec-Fetch-Dest: iframe` 请求头
- **影响文件**: `application/common/controller/Backend.php`, `application/admin/view/layout/default.html`

### 7. 弹窗重复显示确定/重置按钮
- **问题**: iframe 内的 `.layer-footer` 按钮始终显示，与弹窗底部按钮重复
- **修复**: `<body>` 的 `is-dialog` class 也需要 `Sec-Fetch-Dest: iframe` 检测
- **影响文件**: `application/admin/view/layout/default.html`

---

## 三、模板修复

### 8. 表单字段清理
- **问题**: 用户/代理编辑表单包含数据库中不存在的字段（`company_name`, `out_time`, `memory` 等）
- **修复**: 简化为实际使用的字段
- **影响文件**: 
  - `application/admin/view/user/user/add.html`
  - `application/admin/view/user/user/edit.html`
  - `application/admin/view/agent/agent/add.html`
  - `application/admin/view/agent/agent/edit.html`

### 9. 缺失模板创建
- **问题**: 多个页面缺少 add.html/edit.html 模板
- **修复**: 创建以下模板
  - `application/admin/view/agent/agentinvite/add.html` - 邀请码添加
  - `application/admin/view/version/index/add.html` - 版本添加
  - `application/admin/view/version/index/edit.html` - 版本编辑

### 10. 头像上传后实时更新
- **修复**: 上传成功后同步更新父窗口侧边栏头像（无需先点提交）
- **影响文件**: `public/assets/js/backend/general/profile.js`

---

## 四、数据库修复

### 11. 缺失数据表创建
- `fa_admin_log` - 管理员操作日志表
- `fa_attachment` - 附件管理表
- `fa_user_token` - 用户 Token 表（之前已修复）
- 上传目录: `public/uploads/avatar`, `public/uploads/attachment`

---

## 测试账号

### 后台管理（admin.php）

| 账号 | 密码 | 角色 | 数据范围 |
|------|------|------|---------|
| `admin` | `123456` | 超级管理员 | 全局 |
| `tiepai2` | `123456` | 贴牌商 | 旗下代理 + 用户 |
| `agent1` | `123456` | 代理 | 自己的用户 |

### 客户端（API）

| 账号 | 密码 | 积分 | 模式 |
|------|------|------|------|
| `13800138000` | `123456` | ~8500 | formal |
| `13900139001` | `123456` | 0 | trial |

## 后端访问地址
- 后台管理: `http://127.0.0.1:8082/admin.php`
- API 接口: `http://127.0.0.1:8082/api/client/v1/`
- 客户端联调: `VITE_BACKEND_BASE_URL=http://localhost:8082`
