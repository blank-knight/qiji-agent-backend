-- ===================================================================
-- QIJI Agent 后端数据库 — 合并版初始化脚本
-- 包含：FastAdmin 基础表 + 客户端专用表 + 初始数据
--
-- 使用方法：
--   1. 创建空数据库 qiji_admin（utf8mb4）
--   2. 导入本文件即可
--   3. 安装 FastAdmin 完整版后，在 public/install.php 的数据库配置里填入信息
--      或者直接用这个 SQL，跳过 FastAdmin 安装向导
-- ===================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------------
-- 一、FastAdmin 基础表（从 fastadmin.sql 精简提取）
-- -------------------------------------------------------------------

-- ----------------------------
-- 管理员表
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_admin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `username` varchar(20) DEFAULT NULL COMMENT '用户名',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `password` varchar(32) DEFAULT NULL COMMENT '密码',
  `salt` varchar(30) DEFAULT NULL COMMENT '密码盐',
  `avatar` varchar(255) DEFAULT '' COMMENT '头像',
  `email` varchar(100) DEFAULT NULL COMMENT '电子邮箱',
  `loginfailure` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
  `logintime` int(10) DEFAULT NULL COMMENT '登录时间',
  `loginip` varchar(50) DEFAULT NULL COMMENT '登录IP',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `token` varchar(59) DEFAULT '' COMMENT 'Session标识',
  `status` varchar(30) NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- ----------------------------
-- 权限分组表
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_auth_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父组别',
  `name` varchar(100) DEFAULT '' COMMENT '组名',
  `rules` text COMMENT '规则ID',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `status` enum('normal','hidden') DEFAULT 'normal',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限分组表';

-- ----------------------------
-- 权限规则表
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_auth_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(30) DEFAULT 'file',
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(100) DEFAULT '' COMMENT '规则名称',
  `title` varchar(100) DEFAULT '' COMMENT '规则标题',
  `icon` varchar(50) DEFAULT '' COMMENT '图标',
  `condition` varchar(255) DEFAULT '' COMMENT '条件',
  `remark` varchar(255) DEFAULT '' COMMENT '备注',
  `ismenu` tinyint(1) unsigned DEFAULT '0' COMMENT '是否菜单',
  `createtime` int(10) DEFAULT NULL,
  `updatetime` int(10) DEFAULT NULL,
  `weigh` int(10) NOT NULL DEFAULT '0',
  `status` varchar(30) DEFAULT 'normal',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限规则表';

-- ----------------------------
-- 管理员权限分组关联表
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_auth_group_access` (
  `uid` int(10) unsigned DEFAULT NULL COMMENT '会员ID',
  `group_id` int(10) unsigned DEFAULT NULL COMMENT '级别ID',
  UNIQUE KEY `uid_group_id` (`uid`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限分组表';

-- -------------------------------------------------------------------
-- 二、用户体系表
-- -------------------------------------------------------------------

-- ----------------------------
-- 会员表（已加客户端专用字段）
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '组别ID',
  `username` varchar(32) DEFAULT NULL COMMENT '用户名',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `password` varchar(32) DEFAULT NULL COMMENT '密码',
  `salt` varchar(30) DEFAULT NULL COMMENT '密码盐',
  `email` varchar(100) DEFAULT NULL COMMENT '电子邮箱',
  `mobile` varchar(11) DEFAULT NULL COMMENT '手机',
  `avatar` varchar(255) DEFAULT '' COMMENT '头像',
  `level` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '等级',
  `gender` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '性别',
  `birthday` date DEFAULT NULL COMMENT '生日',
  `bio` varchar(100) DEFAULT NULL COMMENT '格言',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '余额',
  `score` int(10) NOT NULL DEFAULT '0' COMMENT '积分',
  `successions` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '连续登录次数',
  `prevtime` int(10) DEFAULT NULL COMMENT '上次登录时间',
  `logintime` int(10) DEFAULT NULL COMMENT '登录时间',
  `loginip` varchar(50) DEFAULT NULL COMMENT '登录IP',
  `loginfailure` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
  `joinip` varchar(50) DEFAULT NULL COMMENT '加入IP',
  `jointime` int(10) DEFAULT NULL COMMENT '加入时间',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `token` varchar(50) DEFAULT '' COMMENT 'Token',
  `status` varchar(30) DEFAULT 'normal' COMMENT '状态',
  `verification` varchar(255) DEFAULT '' COMMENT '验证',
  -- ▼▼▼ 客户端专用字段 ▼▼▼
  `agent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属代理ID',
  `api_key_encrypted` varchar(500) DEFAULT NULL COMMENT '加密的API Key',
  `is_custom_key` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否自定义Key:0=否,1=是',
  `token_expire` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Token过期时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `email` (`email`),
  KEY `mobile` (`mobile`),
  KEY `agent_id` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员表';

-- ----------------------------
-- 用户Token表
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_user_token` (
  `token` varchar(50) NOT NULL COMMENT 'Token',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `expiretime` int(10) DEFAULT NULL COMMENT '过期时间',
  `expires_in` int(10) DEFAULT NULL COMMENT '有效期',
  PRIMARY KEY (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员Token表';

-- -------------------------------------------------------------------
-- 三、积分/Score 表
-- -------------------------------------------------------------------

-- ----------------------------
-- 积分日志表（已加客户端专用字段）
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_user_score_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL COMMENT '会员ID',
  `score` int(10) DEFAULT NULL COMMENT '变更积分',
  `before` int(10) DEFAULT NULL COMMENT '变更前积分',
  `after` int(10) DEFAULT NULL COMMENT '变更后积分',
  `memo` varchar(255) DEFAULT '' COMMENT '备注',
  `createtime` int(10) DEFAULT NULL,
  -- ▼▼▼ 客户端专用字段 ▼▼▼
  `model` varchar(50) DEFAULT NULL COMMENT 'LLM模型名',
  `input_tokens` int(10) unsigned DEFAULT '0' COMMENT '输入Token数',
  `output_tokens` int(10) unsigned DEFAULT '0' COMMENT '输出Token数',
  `request_id` varchar(64) DEFAULT NULL COMMENT '请求唯一标识（幂等去重）',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  UNIQUE KEY `request_id` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分日志表';

-- -------------------------------------------------------------------
-- 四、代理系统表（新增）
-- -------------------------------------------------------------------

-- ----------------------------
-- 代理表
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_agent` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `agent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上级贴牌商ID（0=顶级）',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联后台管理员ID',
  `username` varchar(50) DEFAULT NULL COMMENT '代理用户名',
  `name` varchar(100) DEFAULT NULL COMMENT '代理名称',
  `password` varchar(32) DEFAULT NULL COMMENT '密码',
  `salt` varchar(30) DEFAULT NULL COMMENT '密码盐',
  `mobile` varchar(20) DEFAULT NULL COMMENT '联系电话',
  `domain` varchar(255) DEFAULT NULL COMMENT '代理网址',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `score` int(10) NOT NULL DEFAULT '0' COMMENT '剩余配额',
  `total_score` int(10) NOT NULL DEFAULT '0' COMMENT '累计配额',
  `api_key` varchar(500) DEFAULT NULL COMMENT '加密的API Key',
  `is_custom_key` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否自定义Key',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '佣金比例',
  `status` varchar(30) DEFAULT 'normal' COMMENT '状态',
  `createtime` int(10) DEFAULT NULL,
  `updatetime` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='代理表';

-- ----------------------------
-- 邀请码表
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_agent_invite` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `agent_id` int(10) unsigned NOT NULL COMMENT '所属代理ID',
  `invite_code` varchar(20) NOT NULL COMMENT '邀请码',
  `name` varchar(100) DEFAULT NULL COMMENT '备注名称',
  `max_count` int(10) NOT NULL DEFAULT '0' COMMENT '最大使用次数（0=不限）',
  `used_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '已使用次数',
  `expiretime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '过期时间（0=不限）',
  `status` varchar(30) DEFAULT 'normal' COMMENT '状态',
  `createtime` int(10) DEFAULT NULL,
  `updatetime` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invite_code` (`invite_code`),
  KEY `agent_id` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='代理邀请码表';

-- -------------------------------------------------------------------
-- 五、配置/版本表
-- -------------------------------------------------------------------

-- ----------------------------
-- 系统配置表
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) DEFAULT '' COMMENT '变量名',
  `group` varchar(30) DEFAULT '' COMMENT '分组',
  `title` varchar(100) DEFAULT '' COMMENT '变量标题',
  `tip` varchar(100) DEFAULT '' COMMENT '变量描述',
  `type` varchar(30) DEFAULT '' COMMENT '类型:string,text,int,bool,array,datetime,date,select,selects,checkbox,radio,editor,city,image,images,file,files,switch,datepicker,datetimepicker,datetimerange,textarea',
  `value` text COMMENT '变量值',
  `content` text COMMENT '变量字典数据',
  `rule` varchar(100) DEFAULT '' COMMENT '验证规则',
  `extend` varchar(255) DEFAULT '' COMMENT '扩展属性',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置';

-- ----------------------------
-- 版本表
-- ----------------------------
CREATE TABLE IF NOT EXISTS `fa_version` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) DEFAULT NULL COMMENT '应用名称',
  `newversion` varchar(30) DEFAULT NULL COMMENT '新版本号',
  `downloadurl` varchar(255) DEFAULT NULL COMMENT '下载地址',
  `requireversion` varchar(30) DEFAULT NULL COMMENT '需要的版本',
  `content` varchar(500) DEFAULT NULL COMMENT '升级版内容',
  `packagesize` varchar(30) DEFAULT NULL COMMENT '包大小',
  `enforce` tinyint(1) unsigned DEFAULT '0' COMMENT '是否强制更新',
  `createtime` int(10) DEFAULT NULL,
  `updatetime` int(10) DEFAULT NULL,
  `weigh` int(10) NOT NULL DEFAULT '0',
  `status` varchar(30) DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='版本管理';

-- -------------------------------------------------------------------
-- 六、初始数据
-- -------------------------------------------------------------------

-- 初始超级管理员（admin / 密码双md5(123456) = 14e1b600b1fd579f47433b88e8d85291）
INSERT INTO `fa_admin` (`id`, `username`, `nickname`, `password`, `salt`, `email`, `createtime`, `updatetime`, `status`)
VALUES (1, 'admin', '超级管理员', '14e1b600b1fd579f47433b88e8d85291', '', 'admin@admin.com', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'normal');

-- 权限分组
INSERT INTO `fa_auth_group` (`id`, `pid`, `name`, `rules`, `createtime`, `updatetime`, `status`)
VALUES
(1, 0, '超级管理员', '*', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'normal'),
(2, 1, '代理管理员', '10,11,12,13,14,15', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'normal');

-- 管理员分组关联
INSERT INTO `fa_auth_group_access` (`uid`, `group_id`) VALUES (1, 1);

-- 后台菜单（权限规则）
INSERT INTO `fa_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES
-- 系统管理
(1, 'file', 0, 'general', '常规管理', 'fa fa-cogs', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 99, 'normal'),
(2, 'file', 1, 'general/config', '系统配置', 'fa fa-circle-o', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 90, 'normal'),
(3, 'file', 1, 'general/profile', '个人资料', 'fa fa-user', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 80, 'normal'),
-- 用户管理
(4, 'file', 0, 'user', '用户管理', 'fa fa-user-circle', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 95, 'normal'),
(5, 'file', 4, 'user/user', '用户列表', 'fa fa-users', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 90, 'normal'),
-- 代理管理
(6, 'file', 0, 'agent', '代理管理', 'fa fa-users', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 94, 'normal'),
(7, 'file', 6, 'agent/agent', '代理列表', 'fa fa-list', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 90, 'normal'),
(8, 'file', 6, 'agent/agentinvite', '邀请码管理', 'fa fa-ticket', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 80, 'normal'),
-- 统计
(9, 'file', 0, 'statistics', '统计报表', 'fa fa-bar-chart', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 90, 'normal'),
(10, 'file', 9, 'statistics/scorelog', 'Token消耗记录', 'fa fa-list-alt', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 90, 'normal'),
-- 版本管理
(11, 'file', 0, 'version', '版本管理', 'fa fa-upload', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 50, 'normal'),
-- 系统维护
(12, 'file', 0, 'auth', '权限管理', 'fa fa-shield', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 50, 'normal'),
(13, 'file', 12, 'auth/admin', '管理员管理', 'fa fa-user', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 90, 'normal'),
(14, 'file', 12, 'auth/group', '角色组', 'fa fa-users', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 80, 'normal'),
(15, 'file', 12, 'auth/rule', '菜单规则', 'fa fa-list', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 70, 'normal');

-- 系统配置项
INSERT INTO `fa_config` (`name`, `group`, `title`, `tip`, `type`, `value`, `content`)
VALUES
('name', 'basic', '站点名称', '', 'string', 'QIJI Agent', NULL),
('token_per_score', 'basic', '每积分对应Token数', '每多少个Token消耗1个积分', 'int', '10000', NULL),
('default_api_key', 'basic', '系统默认API Key', '用户/代理都未自定义时使用', 'text', '', NULL),
('trial_score', 'basic', '体验用户积分', '新注册体验用户的初始积分', 'int', '10', NULL);

-- 初始测试代理（密码双md5(123456) = 14e1b600b1fd579f47433b88e8d85291）
INSERT INTO `fa_agent` (`id`, `agent_id`, `admin_id`, `username`, `name`, `password`, `salt`, `score`, `total_score`, `status`, `createtime`, `updatetime`)
VALUES (1, 0, 0, 'testagent', '测试代理', '14e1b600b1fd579f47433b88e8d85291', '', 1000000, 1000000, 'normal', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 初始测试邀请码
INSERT INTO `fa_agent_invite` (`id`, `agent_id`, `invite_code`, `name`, `max_count`, `used_count`, `expiretime`, `status`, `createtime`, `updatetime`)
VALUES (1, 1, 'QIJI001', '首批测试邀请码', 0, 0, 0, 'normal', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 初始版本记录
INSERT INTO `fa_version` (`id`, `name`, `newversion`, `downloadurl`, `requireversion`, `content`, `packagesize`, `enforce`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES (1, 'qiji-agent', '1.0.0', '', '0.0.1', '首个版本', '0MB', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

SET FOREIGN_KEY_CHECKS = 1;
