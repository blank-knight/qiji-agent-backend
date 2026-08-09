# 奇迹智能体后台 - 修复总结

## 提交信息
- **Commit**: `73331eb` - fix: 修复后台管理多项问题（搜索、上传、API、模板）
- **修改文件**: 21 files changed, 457 insertions(+), 726 deletions(-)
- **日期**: 2026-08-09

---

## 一、API 接口修复

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

| 账号 | 密码 | 积分 | 模式 | 说明 |
|------|------|------|------|------|
| `admin` | `123456` | - | - | 后台超级管理员 |
| `13800138000` | `123456` | 8500 | formal | 正式用户 |
| `13900139001` | `123456` | 0 | trial | 体验用户 |

## 后端访问地址
- 后台管理: `http://127.0.0.1:8082/admin.php`
- API 接口: `http://127.0.0.1:8082/api/client/v1/`
- 客户端联调: `VITE_BACKEND_BASE_URL=http://localhost:8082`
