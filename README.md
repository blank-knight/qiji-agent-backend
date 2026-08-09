# QIJI Agent 后端

> 专为 QIJI-agent 客户端提供 **API 服务 + 用户管理 + 代理分销 + Token 计费** 的后端系统
>
> 技术栈：PHP 8.1 + FastAdmin + ThinkPHP 5 + MySQL
>
> 特点：**无 SourceGuardian 加密，全部明文源码，可自由修改**

---

## 目录

- [功能总览](#功能总览)
- [客户端 API 文档](#客户端-api-文档)
- [后台管理功能](#后台管理功能)
- [代理后台功能](#代理后台功能)
- [核心业务逻辑](#核心业务逻辑)
- [数据库设计](#数据库设计)
- [快速部署](#快速部署)
- [客户端对接指南](#客户端对接指南)

---

## 功能总览

### 客户端 API（8 个接口）

| 功能模块 | 接口数 | 说明 |
|----------|--------|------|
| 用户认证 | 3 | 注册 / 登录 / 邀请码激活 |
| 额度管理 | 2 | 余额查询 / Token 消耗上报 |
| API Key | 2 | Key 查询（层级继承）/ 自定义 Key 设置 |
| 版本检查 | 1 | 客户端更新检测 |

### 后台管理（admin.php）

统一后台入口，不同角色看到不同数据范围：

| 角色 | 数据范围 | 可见菜单 |
|------|---------|---------|
| 超级管理员 | 全局 | 所有功能 |
| 贴牌商 | 自己旗下的代理 + 用户 | 用户管理、代理管理、邀请码、统计 |
| 代理 | 自己的用户 | 用户管理、邀请码、统计 |

| 功能模块 | 具体能力 |
|----------|---------|
| 仪表盘 | 统计卡片（用户/代理/Token/请求）、近30天趋势图、模型占比饼图、积分横幅 |
| 用户管理 | 增删改查、充值/扣除积分、开关自定义 Key、设置用户独立 API Key |
| 代理管理 | 增删改查、设置配额、配置代理 API Key、多级代理树 |
| 邀请码管理 | 生成/编辑/禁用，控制次数和有效期 |
| 版本管理 | 发布新版本、强制更新开关 |
| Token 消耗记录 | 全量流水查询，按用户/模型筛选 |
| 权限管理 | 管理员账号、角色组（预设三种：超管/贴牌/代理） |
| 系统配置 | 计费参数、默认 API Key、站点信息 |

### 代理后台（agent.php）

贴牌商和代理共用 `admin.php` 入口，按角色权限自动隔离数据。代理不再需要单独的 `agent.php` 入口，直接用代理账号登录 `admin.php` 即可。

### 四层层级体系

```
超级管理员（admin）
  └── 贴牌商（tiepai）— 可管理旗下代理和用户
        ├── 代理A（agent）— 可管理自己的用户
        │     ├── 用户1
        │     └── 用户2
        └── 代理B
              └── 用户3
```

每个层级：
- 只能看到自己范围内的数据（用户数、代理数、Token 消耗等）
- Dashboard 显示自己的剩余积分和旗下用户数
- 统计图表（趋势图、模型占比）只展示范围数据

---

## 客户端 API 文档

### 认证方式

除注册、登录、版本检查外，所有接口需要在 Header 中携带 token：

```
Authorization: Bearer <token>
```

token 在 `login` 或 `register` 接口的返回值中获取。

### 统一响应格式

```json
{
    "code": 1,
    "msg": "成功提示",
    "time": 1785740000,
    "data": {}
}
```

- `code = 1` 表示成功，`code = 0` 表示失败

---

### 1. 用户注册

```
POST /api/client/v1/auth/register
```

**请求参数：**

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| mobile | string | 是 | 手机号 |
| password | string | 是 | 密码（≥6位） |
| invite_code | string | 否 | 邀请码，不填则为体验用户 |

**两种注册模式：**

| 模式 | 条件 | 用户状态 |
|------|------|---------|
| 体验模式 | 不带 invite_code | `agent_id=0, score=0`，可后续激活 |
| 正式模式 | 带有效 invite_code | `agent_id>0`，绑定到对应代理 |

**响应示例：**

```json
{
    "code": 1,
    "msg": "注册成功",
    "data": {
        "token": "xxx-xxx-xxx",
        "user_info": {
            "id": 1,
            "username": "13800138000",
            "mobile": "13800138000",
            "score": 0,
            "mode": "trial",
            "is_custom_key": 0,
            "agent_name": ""
        }
    }
}
```

---

### 2. 用户登录

```
POST /api/client/v1/auth/login
```

**请求参数：**

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| mobile | string | 是 | 手机号 |
| password | string | 是 | 密码 |

**响应：** 同注册接口

---

### 3. 邀请码激活（体验 → 正式）

```
POST /api/client/v1/auth/activate
```

体验用户补绑邀请码，升级为正式用户。

**请求参数：**

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| invite_code | string | 是 | 邀请码 |

**业务规则：**
- 已绑定代理的用户不能再次激活
- 邀请码必须有效（未禁用、未过期、未用完）

---

### 4. 查询额度

```
GET /api/client/v1/quota
```

**响应：**

```json
{
    "code": 1,
    "data": {
        "score": 8500,
        "mode": "formal",
        "is_custom_key": 0
    }
}
```

| 字段 | 说明 |
|------|------|
| score | 剩余积分 |
| mode | `trial`=体验用户，`formal`=正式用户 |
| is_custom_key | 是否允许自定义 Key |

---

### 5. 上报 Token 消耗

```
POST /api/client/v1/quota/report
```

客户端每次调用 LLM 后上报，自动扣减积分。

**请求参数：**

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| model | string | 是 | 模型名，如 `deepseek-chat` |
| input_tokens | int | 是 | 输入 Token 数 |
| output_tokens | int | 是 | 输出 Token 数 |
| request_id | string | 是 | 请求唯一标识（用于幂等去重） |

**计费规则：**

```
总 Token = input_tokens + output_tokens
扣除积分 = ceil(总Token / token_per_score)
```

`token_per_score` 默认 10000，可在后台「系统配置」修改。

**特殊处理：**
- `is_custom_key=1` 的用户（自带 Key）**不扣费**，直接返回成功
- 同一个 `request_id` 重复上报只扣一次
- 余额不足时返回 `code=0, HTTP 429`

**响应：**

```json
{
    "code": 1,
    "msg": "上报成功",
    "data": {
        "remaining_score": 8499
    }
}
```

---

### 6. 查询 API Key

```
GET /api/client/v1/apikey
```

返回当前用户应该使用的 API Key（按层级继承逻辑）。

**响应：**

```json
{
    "code": 1,
    "data": {
        "is_custom_key": 0,
        "api_key": "sk-xxxxxxxx",
        "key_source": "agent",
        "key_source_name": "腾讯云代理商",
        "can_customize": false
    }
}
```

**Key 来源优先级（从高到低）：**

| 优先级 | 来源 | key_source | 条件 |
|--------|------|-----------|------|
| 1 | 用户自定义 | `user` | `is_custom_key=1` 且已设置 |
| 2 | 所属代理 | `agent` | 代理配置了 Key |
| 3 | 贴牌商 | `tiepai` | 代理的上级配置了 Key |
| 4 | 系统默认 | `system` | 后台 `default_api_key` 配置项 |

---

### 7. 设置自定义 API Key

```
POST /api/client/v1/apikey/customize
```

仅 `is_custom_key=1` 的用户有权限调用。后台可随时开关此权限。

**请求参数：**

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| api_key | string | 是 | 自定义的 API Key |

**权限控制：**
- 后台编辑用户时，将 `is_custom_key` 设为 `1` → 允许自定义
- 设为 `0` → 强制走继承链，调用此接口会返回 `您没有自定义 Key 的权限`

---

### 8. 检查客户端更新

```
GET /api/client/v1/update/check?version=1.0.0
```

**请求参数：**

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| version | string | 是 | 当前客户端版本号 |

**有更新时的响应：**

```json
{
    "code": 1,
    "data": {
        "has_update": true,
        "enforce": 0,
        "newversion": "1.1.0",
        "downloadurl": "https://xxx/download/qiji-agent-1.1.0.exe",
        "packagesize": "85MB",
        "upgradetext": "1. 新增 Skill Hub\n2. 修复若干bug"
    }
}
```

**无更新时的响应：**

```json
{
    "code": 1,
    "data": {
        "has_update": false
    }
}
```

---

## 后台管理功能

### 访问方式

统一通过 `admin.php` 入口，不同账号看到不同数据范围：

| 后台 | 地址 | 测试账号 |
|------|------|---------|
| 超管后台 | `http://域名/admin.php` | `admin` / `123456` |
| 贴牌商后台 | `http://域名/admin.php` | `tiepai2` / `123456` |
| 代理后台 | `http://域名/admin.php` | `agent1` / `123456` |

### 功能详解

#### 用户管理（`admin.php/user/user`）

| 操作 | 说明 |
|------|------|
| 列表查看 | 显示所有用户，含所属代理、积分、注册时间 |
| 添加用户 | 手动创建，自动加密密码 |
| 编辑用户 | 修改信息、**开关 is_custom_key** |
| 充值积分 | 加/扣 score，**自动触发配额继承**（代理反向变动） |
| 删除用户 | 软删除 |

#### 代理管理（`admin.php/agent/agent`）

| 操作 | 说明 |
|------|------|
| 列表查看 | 显示所有代理，含用户数、剩余配额 |
| 添加代理 | 设置名称、初始配额、上级贴牌商 |
| 编辑代理 | 修改配额、配置 API Key、开关自定义 Key |
| 设置 API Key | 为代理配置统一 Key，下级用户自动继承 |

#### 邀请码管理（`admin.php/agent/agentinvite`）

| 操作 | 说明 |
|------|------|
| 生成邀请码 | 自动生成 8 位唯一码（如 `QIJI001`） |
| 设置次数 | `max_count`，0=不限 |
| 设置有效期 | `expiretime`，0=不限 |
| 查看使用情况 | `used_count` 已使用次数 |
| 禁用/启用 | `status` 字段 |

#### 版本管理（`admin.php/version`）

| 操作 | 说明 |
|------|------|
| 发布新版本 | 填写版本号、下载地址、更新内容 |
| 强制更新 | `enforce=1` 时客户端必须更新 |
| 版本号比较 | 自动比较，如 `1.0.0` < `1.1.0` |

#### Token 消耗记录（`admin.php/statistics/scorelog`）

| 字段 | 说明 |
|------|------|
| 用户名 | 哪个用户消耗的 |
| 模型 | 调用的 LLM 模型 |
| input_tokens | 输入 Token |
| output_tokens | 输出 Token |
| 扣除积分 | 本次消耗的 score |
| request_id | 幂等标识 |

#### 系统配置（`admin.php/general/config`）

| 配置项 | 说明 | 默认值 |
|--------|------|--------|
| `token_per_score` | 多少 Token 消耗 1 积分 | 10000 |
| `default_api_key` | 系统默认 API Key（兜底用） | 空 |
| `trial_score` | 体验用户初始积分 | 10 |
| `name` | 站点名称 | QIJI Agent |

---

## 代理后台功能

代理登录 `agent.php` 后，只能看到和管理**自己的数据**。

#### 仪表盘

- 我的用户总数
- 用户总消耗积分
- 我的剩余配额
- 邀请码统计

#### 邀请码管理

代理可以自己生成邀请码，分发给终端用户。

#### 用户解绑

解除某个用户和自己的绑定关系（用户变为无代理状态）。

---

## 核心业务逻辑

### 1. 配额继承机制

```
后台给用户 +100 score  →  所属代理自动 -100 score
后台给用户 -50 score   →  所属代理自动 +50 score
```

**实现位置：** [application/admin/model/User.php](application/admin/model/User.php) 的 `onBeforeUpdate` 钩子

**触发场景：**
- 后台手动充值/扣除
- 用户 Token 上报扣费（间接通过 score 变动）

### 2. Token 计费流程

```
客户端调用 LLM → 拿到 input/output tokens
    ↓
调用 /api/client/v1/quota/report 上报
    ↓
后端检查 request_id 是否已存在（幂等去重）
    ↓
计算扣费：ceil(总Token / token_per_score)
    ↓
检查余额是否足够
    ↓
扣除用户 score + 写入 score_log
```

### 3. API Key 层级继承

```
客户端请求 /api/client/v1/apikey
    ↓
用户 is_custom_key=1 且有自定义Key？
    ├─ 是 → 返回用户自己的 Key（source=user）
    └─ 否 ↓
所属代理有 Key？
    ├─ 是 → 返回代理的 Key（source=agent）
    └─ 否 ↓
代理的上级贴牌商有 Key？
    ├─ 是 → 返回贴牌商的 Key（source=tiepai）
    └─ 否 ↓
返回系统默认 Key（source=system）
```

### 4. 三级代理体系

```
贴牌商（agent_id=0）
  ├── 代理A（agent_id=贴牌商ID）
  │     ├── 用户1（agent_id=代理A的ID）
  │     └── 用户2
  └── 代理B
        └── 用户3
```

- 贴牌商给代理A充 100万 配额
- 代理A给用户1充 1万 → 代理A自动变 99万
- 用户1消耗 100 → 用户1变 9900，不响代理A（只在充值时继承）

### 5. 体验模式 vs 正式模式

| 维度 | 体验用户（trial） | 正式用户（formal） |
|------|------------------|-------------------|
| 注册方式 | 不带邀请码 | 带邀请码 或 后续激活 |
| agent_id | 0 | >0 |
| API Key 来源 | 系统默认 | 代理/贴牌商继承 |
| 计费 | 正常扣费 | 正常扣费 |
| 可升级 | 补绑邀请码 | - |

---

## 数据库设计

共 10 张核心表：

| 表名 | 用途 | 关键字段 |
|------|------|---------|
| `fa_admin` | 后台管理员 | id, username, password, salt |
| `fa_user` | 终端用户 | id, mobile, score, **agent_id, is_custom_key, api_key_encrypted** |
| `fa_agent` | 代理/贴牌商 | id, **agent_id(上级), admin_id, score, api_key, is_custom_key** |
| `fa_agent_invite` | 邀请码 | invite_code, agent_id, max_count, used_count, expiretime |
| `fa_user_score_log` | 积分流水 | user_id, score, **model, input_tokens, output_tokens, request_id** |
| `fa_user_token` | 登录 Token | token, user_id, expiretime |
| `fa_version` | 客户端版本 | newversion, downloadurl, enforce |
| `fa_config` | 系统配置 | name, value（含 token_per_score, default_api_key） |
| `fa_auth_group` | 角色组 | id, rules |
| `fa_auth_rule` | 菜单/权限规则 | name, title, ismenu |

完整建表 SQL：[sql/qiji_admin.sql](sql/qiji_admin.sql)

---

## 快速部署

### 前置要求

- PHP 7.4+（推荐 8.1）
- MySQL 5.7+
- Nginx / Apache

### 部署步骤

#### 1. 克隆代码到服务器

```bash
cd /www/wwwroot
git clone https://github.com/blank-knight/qiji-agent-backend.git qiji-admin
```

#### 2. 创建数据库

```sql
CREATE DATABASE qiji_admin DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

导入建表脚本：
```bash
mysql -u root -p qiji_admin < /www/wwwroot/qiji-admin/sql/qiji_admin.sql
```

#### 3. 配置环境

复制 `.env.example` 为 `.env`（或直接编辑 `.env`）：

```ini
[app]
debug = false

[database]
hostname = 127.0.0.1
database = qiji_admin
username = your_db_user
password = your_db_password
hostport = 3306
prefix = fa_
```

#### 4. 宝塔面板配置

| 配置项 | 值 |
|--------|-----|
| 站点目录 | `/www/wwwroot/qiji-admin` |
| 运行目录 | `/public` |
| PHP 版本 | 8.1 |
| 伪静态 | 粘贴 `nginx.conf` 内容 |

**Nginx 伪静态规则：**

```nginx
location ~* (runtime|application)/ {
    deny all;
}
location / {
    if (!-e $request_filename) {
        rewrite  ^(.*)$  /index.php/$1  last;
        break;
    }
}
```

#### 5. 设置目录权限

```bash
cd /www/wwwroot/qiji-admin
chown -R www:www .
chmod -R 755 .
mkdir -p runtime
chmod -R 777 runtime
```

#### 6. 访问验证

| 入口 | 地址 |
|------|------|
| API 状态 | `http://域名/` → 返回 JSON |
| 管理后台 | `http://域名/admin.php` |
| 代理后台 | `http://域名/agent.php` |

默认管理员：`admin` / `123456`（**部署后请立即修改密码**）

---

## 客户端对接指南

### QIJI-agent 客户端需要修改的配置

| 配置项 | 旧值 | 新值 |
|--------|------|------|
| API 地址 | `http://8.138.58.181` | `http://你的后端域名:端口` |
| 登录接口 | `/api/zhushou/login` | `/api/client/v1/auth/login` |
| 配置接口 | `/api/zhushou/index` | `/api/client/v1/quota` |
| 版本检查 | 无 | `GET /api/client/v1/update/check` |
| Token 上报 | 无 | `POST /api/client/v1/quota/report` |

### 对接示例（Python）

```python
import requests

BASE_URL = "http://your-domain:8888/api/client/v1"

# 1. 登录
resp = requests.post(f"{BASE_URL}/auth/login", json={
    "mobile": "13800138000",
    "password": "123456"
})
token = resp.json()["data"]["token"]

# 2. 查询额度
resp = requests.get(f"{BASE_URL}/quota", headers={
    "Authorization": f"Bearer {token}"
})

# 3. 上报 Token 消耗
requests.post(f"{BASE_URL}/quota/report", 
    headers={"Authorization": f"Bearer {token}"},
    json={
        "model": "deepseek-chat",
        "input_tokens": 1500,
        "output_tokens": 500,
        "request_id": "uuid-unique-id"
    }
)

# 4. 获取 API Key
resp = requests.get(f"{BASE_URL}/apikey", headers={
    "Authorization": f"Bearer {token}"
})
api_key = resp.json()["data"]["api_key"]
```

---

## 项目结构

```
qiji-admin/
├── application/
│   ├── api/controller/client/         客户端 API
│   │   ├── Auth.php                   认证（注册/登录/激活）
│   │   ├── Quota.php                  额度查询 + Token 上报扣费
│   │   ├── Apikey.php                 API Key 管理（层级继承）
│   │   └── Update.php                 版本检查
│   ├── admin/
│   │   ├── controller/                总后台控制器
│   │   │   ├── Index.php              登录/主界面
│   │   │   ├── Dashboard.php          仪表盘
│   │   │   ├── Agent.php              代理管理
│   │   │   ├── AgentInvite.php        邀请码管理
│   │   │   ├── Version.php            版本管理
│   │   │   ├── ScoreLog.php           Token 消耗记录
│   │   │   ├── Ajax.php               通用接口
│   │   │   ├── auth/                  权限管理
│   │   │   ├── general/               系统配置
│   │   │   └── user/                  用户管理
│   │   ├── model/                     后台 Model
│   │   ├── library/                   Auth 认证
│   │   └── view/                      后台模板
│   ├── agent/controller/              代理后台
│   │   ├── AgentInvite.php            邀请码管理
│   │   ├── user/Unbind.php            用户解绑
│   │   ├── Dashboard.php              仪表盘
│   │   └── Index.php                  首页
│   ├── common/
│   │   ├── controller/
│   │   │   ├── Api.php                API 基类
│   │   │   └── Backend.php            后台基类
│   │   ├── library/                   Auth/Token/Sms
│   │   └── model/                     公共 Model
│   ├── index/controller/              API 首页
│   ├── common.php                     全局函数库
│   ├── config.php                     应用配置
│   └── route.php                      路由配置
├── public/
│   ├── index.php                      主入口
│   ├── admin.php                      后台入口
│   ├── agent.php                      代理入口
│   └── assets/                        前端静态资源
├── thinkphp/                          ThinkPHP 5 框架
├── vendor/                            Composer 依赖
├── extend/fast/                       FastAdmin 工具库
├── sql/qiji_admin.sql                 数据库脚本
├── .env                               环境配置
├── nginx.conf                         Nginx 伪静态
└── composer.json
```

---

## 技术说明

- **框架版本：** FastAdmin + ThinkPHP 5.0.28
- **PHP 版本：** 7.4 - 8.1（推荐 8.1）
- **加密依赖：** 无（纯明文，不需要 SourceGuardian）
- **前端 UI：** Bootstrap + RequireJS + jQuery（FastAdmin 标准）

## License

内部项目，未公开发布。
