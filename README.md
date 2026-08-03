# QIJI Agent 后端

> 专为 QIJI-agent 客户端提供 API 服务、用户管理、代理分销、计费的后端系统
> 基于 FastAdmin（ThinkPHP 5）构建，**无加密、完全开源**

---

## 快速部署（3步）

### 1. 下载 FastAdmin 完整版框架

本项目只包含业务代码，需要 FastAdmin 框架核心。从官网下载完整版：

```
https://www.fastadmin.net/download.html
```

解压后，把 `thinkphp/`、`vendor/`、`extend/` 三个目录复制到本项目根目录。

或者用 git clone + composer install：
```bash
cd /www/wwwroot/qiji-admin
composer install
```

最终目录结构：
```
qiji-admin/
├── application/          ← 本项目已提供
├── public/               ← 本项目已提供
├── thinkphp/             ← FastAdmin 框架（需下载）
├── vendor/               ← Composer 依赖（需下载）
├── extend/               ← 扩展（需下载）
├── sql/
│   └── qiji_admin.sql    ← 数据库脚本
├── .env                  ← 数据库配置
├── composer.json
└── nginx.conf            ← Nginx 伪静态
```

### 2. 创建数据库 + 导入

```sql
CREATE DATABASE qiji_admin DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

导入 `sql/qiji_admin.sql`（含建表 + 初始数据）。

### 3. 配置 .env + 宝塔建站

编辑 `.env`，填数据库信息：
```ini
[database]
hostname = 127.0.0.1
database = qiji_admin
username = 你的数据库用户
password = 你的数据库密码
```

宝塔添加站点：
- 运行目录：`/public`
- PHP 版本：8.1
- 伪静态：粘贴 `nginx.conf` 内容
- 端口：80/443 被封时用 8888 等非标端口

---

## 入口地址

| 入口 | URL | 用途 |
|------|-----|------|
| API | `http://域名/api/client/v1/*` | 客户端调用 |
| 管理后台 | `http://域名/admin.php` | 总后台（超级管理员） |
| 代理后台 | `http://域名/agent.php` | 代理后台 |

默认管理员：`admin` / `123456`

---

## API 接口列表

| 方法 | 路径 | 认证 | 说明 |
|------|------|------|------|
| POST | `/api/client/v1/auth/register` | 无 | 注册（体验/正式） |
| POST | `/api/client/v1/auth/login` | 无 | 登录 |
| POST | `/api/client/v1/auth/activate` | Bearer | 邀请码激活 |
| GET | `/api/client/v1/quota` | Bearer | 查询余额 |
| POST | `/api/client/v1/quota/report` | Bearer | 上报 Token 消耗 |
| GET | `/api/client/v1/apikey` | Bearer | 查询 API Key（层级继承） |
| POST | `/api/client/v1/apikey/customize` | Bearer | 设置自定义 Key |
| GET | `/api/client/v1/update/check` | 无 | 检查更新 |

### 认证方式

```
Authorization: Bearer <token>
```

token 在 `login` 或 `register` 时返回。

---

## 项目结构

```
application/
├── api/controller/client/         客户端 API
│   ├── Auth.php                   认证（注册/登录/激活）
│   ├── Quota.php                  额度查询 + Token上报
│   ├── Apikey.php                 API Key 管理
│   └── Update.php                 版本检查
├── admin/controller/              总后台 CRUD
│   ├── Agent.php                  代理管理
│   ├── AgentInvite.php            邀请码管理
│   ├── Version.php                版本管理
│   └── ScoreLog.php               Token 消耗记录
├── admin/model/
│   ├── Agent.php                  代理 Model
│   └── User.php                   用户 Model（含配额继承钩子）
├── agent/controller/              代理后台
│   ├── AgentInvite.php            邀请码管理
│   ├── user/Unbind.php            用户解绑
│   ├── Dashboard.php              仪表盘
│   └── Index.php                  首页
├── common/model/                  公共 Model
│   ├── AgentInvite.php
│   ├── Version.php                版本检查逻辑
│   └── ScoreLog.php
├── config.php                     应用配置
├── route.php                      路由配置
└── database.php                   数据库配置（由 .env 驱动）
```

---

## 核心逻辑说明

### 配额继承

用户 score 变动时，自动从所属代理反向扣减/返还。

```
管理员给用户 +100 score → 代理 -100 score
管理员给用户 -50 score  → 代理 +50 score
```

实现：[admin/model/User.php](application/admin/model/User.php) 的 `onBeforeUpdate` 钩子。

### Token 计费

客户端每次 LLM 调用后上报：
```
POST /api/client/v1/quota/report
{
    "model": "deepseek-chat",
    "input_tokens": 1500,
    "output_tokens": 500,
    "request_id": "uuid-xxx"
}
```

计费规则：`扣除积分 = ceil(总Token数 / token_per_score)`
- `token_per_score` 在后台「系统配置」里设置，默认 10000

### 幂等去重

同一个 `request_id` 多次上报只扣一次。通过 `fa_user_score_log.request_id` 唯一索引保证。

### API Key 层级继承

```
用户自定义Key → 用户查看自己的
  ↓ 否
代理Key → 查看代理的
  ↓ 否
贴牌商Key → 查看贴牌商的
  ↓ 否
系统默认Key → 后台配置的 default_api_key
```

---

## 客户端对接

QIJI-agent 客户端需要修改的配置：

| 当前值 | 新值 |
|--------|------|
| `http://8.138.58.181` | `http://新后端域名:端口` |
| `/api/zhushou/login` | `/api/client/v1/auth/login` |
| 无额度查询 | `GET /api/client/v1/quota` |
| 无 Token 上报 | `POST /api/client/v1/quota/report` |

---

## 注意事项

1. **PHP 版本**：必须 7.4+，推荐 8.1
2. **目录权限**：`runtime/` 需要 777
3. **安全**：生产环境把 `.env` 的 `debug` 改为 `false`
4. **FastAdmin 框架**：本项目不包含 `thinkphp/` 和 `vendor/` 目录，需要单独下载 FastAdmin 完整版获取
