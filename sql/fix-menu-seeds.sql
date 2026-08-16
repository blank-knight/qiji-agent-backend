-- 奇计后端: 线上MySQL补菜单/权限表结构与种子 (幂等)
-- 来源: 本地SQLite权威数据程序化导出
-- 背景: 线上fa_auth_rule是13列老结构, 最新代码Auth.php需要15列(menutype/extend/url);
--       且种子仅15行老菜单, 缺控制台/代理/统计/版本/权限等全部业务菜单
-- 策略: fa_auth_rule先RENAME备份再重建(可回滚), fa_admin_rule/admin_group建表+种子

-- ========== 1. fa_auth_rule: 备份旧表 → 15列新结构 → 33行种子 ==========
DROP TABLE IF EXISTS `fa_auth_rule_backup`;
RENAME TABLE `fa_auth_rule` TO `fa_auth_rule_backup`;
CREATE TABLE `fa_auth_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(100) DEFAULT '',
  `title` varchar(100) DEFAULT '',
  `icon` varchar(50) DEFAULT '',
  `condition` varchar(255) DEFAULT '',
  `remark` varchar(255) DEFAULT '',
  `ismenu` tinyint(1) unsigned DEFAULT '0',
  `menutype` varchar(30) DEFAULT 'addtabs',
  `extend` varchar(255) DEFAULT '',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  `weigh` int(10) NOT NULL DEFAULT '0',
  `status` varchar(30) DEFAULT 'normal',
  `url` varchar(255) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限规则表';
INSERT INTO `fa_auth_rule` (`id`,`pid`,`name`,`title`,`icon`,`condition`,`remark`,`ismenu`,`menutype`,`extend`,`createtime`,`updatetime`,`weigh`,`status`,`url`) VALUES
(1,0,'dashboard','控制台','fa fa-dashboard','','',1,'','',1785941374,1786757083,999,'normal',''),
(5,0,'agent','代理管理','fa fa-users','','',1,'','',1785941374,1785941374,80,'normal',''),
(6,5,'agent/agent','代理列表','fa fa-list','','',1,'','',1785941374,1785941374,99,'normal',''),
(7,5,'agent/agentinvite','邀请码','fa fa-ticket','','',1,'','',1785941374,1785941374,98,'normal',''),
(8,0,'user','用户管理','fa fa-user','','',1,'','',1785941374,1786757095,90,'normal',''),
(9,8,'user/user','用户列表','fa fa-list','','',1,'','',1785941374,1785941374,99,'normal',''),
(10,0,'statistics','统计中心','fa fa-bar-chart','','',1,'','',1785941374,1785941374,70,'normal',''),
(11,10,'statistics/scorelog','Token消耗记录','fa fa-list','','',1,'','',1785941374,1785941374,99,'normal',''),
(12,0,'version','版本管理','fa fa-upload','','',1,'','',1785941374,1785941374,60,'normal',''),
(13,12,'version/index','版本列表','fa fa-list','','',1,'','',1785941374,1785941374,99,'normal',''),
(14,0,'auth','权限管理','fa fa-shield','','',1,'','',1785941374,1785941374,50,'normal',''),
(15,14,'auth/admin','管理员管理','fa fa-users','','',1,'','',1785941374,1785941374,99,'normal',''),
(16,14,'auth/group','角色组','fa fa-users','','',1,'','',1785941374,1785941374,98,'normal',''),
(17,14,'auth/rule','菜单规则','fa fa-list','','',1,'','',1785941374,1785941374,97,'normal',''),
(18,6,'agent/agent/index','查看','','','',0,'','',1786279487,1786279487,0,'normal',''),
(19,6,'agent/agent/add','添加','','','',0,'','',1786279487,1786279487,0,'normal',''),
(20,6,'agent/agent/edit','编辑','','','',0,'','',1786279487,1786279487,0,'normal',''),
(21,6,'agent/agent/del','删除','','','',0,'','',1786279487,1786279487,0,'normal',''),
(22,7,'agent/agentinvite/index','查看','','','',0,'','',1786279487,1786279487,0,'normal',''),
(23,7,'agent/agentinvite/add','添加','','','',0,'','',1786279487,1786279487,0,'normal',''),
(24,9,'user/user/index','查看','','','',0,'','',1786279487,1786279487,0,'normal',''),
(25,9,'user/user/add','添加','','','',0,'','',1786279487,1786279487,0,'normal',''),
(26,9,'user/user/edit','编辑','','','',0,'','',1786279487,1786279487,0,'normal',''),
(27,9,'user/user/score','充值','','','',0,'','',1786279487,1786279487,0,'normal',''),
(28,11,'statistics/scorelog/index','查看','','','',0,'','',1786279487,1786279487,0,'normal',''),
(29,13,'version/index/index','查看','','','',0,'','',1786279487,1786279487,0,'normal',''),
(30,1,'dashboard/index','主页','','','',0,'','',1786279487,1786279487,0,'normal',''),
(31,1,'dashboard/index/index','查看','','','',0,'','',1786279487,1786279487,0,'normal',''),
(32,0,'index/index','主页框架','','','',0,'','',1786279487,1786769199,0,'normal',''),
(33,0,'index/index/index','查看','','','',0,'','',1786279487,1786775980,0,'normal',''),
(34,0,'ajax/lang','语言包','','','',0,'','',1786279487,1786769199,0,'normal',''),
(35,0,'general/config','系统配置','','','',0,'','',1786279487,1786769199,0,'normal',''),
(36,0,'general/config/index','查看配置','','','',0,'','',1786279487,1786769199,0,'normal','');

-- ========== 2. fa_admin_rule: 建表 + 17行种子 ==========
CREATE TABLE IF NOT EXISTS `fa_admin_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(100) DEFAULT '',
  `title` varchar(100) DEFAULT '',
  `icon` varchar(50) DEFAULT '',
  `condition` varchar(255) DEFAULT '',
  `remark` varchar(255) DEFAULT '',
  `ismenu` tinyint(1) unsigned DEFAULT '0',
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  `weigh` int(10) NOT NULL DEFAULT '0',
  `status` varchar(30) DEFAULT 'normal',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员权限规则表';
DELETE FROM `fa_admin_rule` WHERE id BETWEEN 1 AND 100;
INSERT INTO `fa_admin_rule` (`id`,`pid`,`name`,`title`,`icon`,`condition`,`remark`,`ismenu`,`createtime`,`updatetime`,`weigh`,`status`) VALUES
(1,0,'dashboard','控制台','fa fa-dashboard','','',1,1785941374,1785941374,999,'normal'),
(2,0,'general','常规管理','fa fa-cogs','','',1,1785941374,1785941374,100,'normal'),
(3,1,'dashboard/index','控制台','fa fa-dashboard','','',1,1785941374,1785941374,999,'normal'),
(4,1,'general/config','系统配置','fa fa-gears','','',1,1785941374,1785941374,90,'normal'),
(5,0,'agent','代理管理','fa fa-users','','',1,1785941374,1785941374,80,'normal'),
(6,4,'agent/agent','代理列表','fa fa-list','','',1,1785941374,1785941374,99,'normal'),
(7,4,'agent/agentinvite','邀请码','fa fa-ticket','','',1,1785941374,1785941374,98,'normal'),
(8,0,'user','用户管理','fa fa-user','','',1,1785941374,1785941374,90,'normal'),
(9,7,'user/user','用户列表','fa fa-list','','',1,1785941374,1785941374,99,'normal'),
(10,0,'statistics','统计中心','fa fa-bar-chart','','',1,1785941374,1785941374,70,'normal'),
(11,9,'statistics/scorelog','Token消耗记录','fa fa-list','','',1,1785941374,1785941374,99,'normal'),
(12,0,'version','版本管理','fa fa-upload','','',1,1785941374,1785941374,60,'normal'),
(13,11,'version/index','版本列表','fa fa-list','','',1,1785941374,1785941374,99,'normal'),
(14,0,'auth','权限管理','fa fa-shield','','',1,1785941374,1785941374,50,'normal'),
(15,13,'auth/admin','管理员管理','fa fa-users','','',1,1785941374,1785941374,99,'normal'),
(16,13,'auth/group','角色组','fa fa-users','','',1,1785941374,1785941374,98,'normal'),
(17,13,'auth/rule','菜单规则','fa fa-list','','',1,1785941374,1785941374,97,'normal');

-- ========== 3. fa_admin_group: 建表 + 种子 ==========
CREATE TABLE IF NOT EXISTS `fa_admin_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(50) DEFAULT '',
  `rules` text,
  `createtime` bigint(16) DEFAULT NULL,
  `updatetime` bigint(16) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'normal',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员权限分组表';
DELETE FROM `fa_admin_group` WHERE id BETWEEN 1 AND 100;
INSERT INTO `fa_admin_group` (`id`,`pid`,`name`,`rules`,`createtime`,`updatetime`,`status`) VALUES
(1,0,'超级管理员','*',1785941374,1785941374,'normal');

-- ========== 4. fa_auth_group: 对齐本地3组种子(超管/贴牌商/代理) ==========
DELETE FROM `fa_auth_group` WHERE id BETWEEN 1 AND 100;
INSERT INTO `fa_auth_group` (`id`,`pid`,`name`,`rules`,`createtime`,`updatetime`,`status`) VALUES
(1,0,'超级管理员','*',1785941374,1785941374,'normal'),
(2,1,'贴牌商','1,5,6,7,8,9,10,11,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34',1786279209,1786286720,'normal'),
(3,1,'代理','1,7,8,9,10,11,22,23,24,25,26,27,28,30,31,32,33,34',1786279508,1786286645,'normal');

-- 回滚方法: RENAME TABLE fa_auth_rule TO fa_auth_rule_new, fa_auth_rule_backup TO fa_auth_rule;
