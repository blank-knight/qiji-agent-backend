-- 奇计后端: 线上 MySQL 补建缺失表 (幂等)
-- 来源: 本地 SQLite 实际表结构程序化生成并校验

CREATE TABLE IF NOT EXISTS `fa_admin_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `admin_id` int(10) unsigned NOT NULL DEFAULT 0,
  `username` varchar(30) DEFAULT '',
  `url` varchar(1500) DEFAULT '',
  `title` varchar(100) DEFAULT '',
  `content` longtext,
  `ip` varchar(50) DEFAULT '',
  `useragent` varchar(255) DEFAULT '',
  `createtime` bigint(16) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员日志表';

CREATE TABLE IF NOT EXISTS `fa_attachment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `admin_id` int(10) unsigned NOT NULL DEFAULT 0,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `url` varchar(255) DEFAULT '',
  `imagewidth` varchar(30) DEFAULT '',
  `imageheight` varchar(30) DEFAULT '',
  `imagetype` varchar(30) DEFAULT '',
  `imageframes` int(10) unsigned NOT NULL DEFAULT 0,
  `filename` varchar(100) DEFAULT '',
  `filesize` int(10) unsigned NOT NULL DEFAULT 0,
  `mimetype` varchar(100) DEFAULT '',
  `extension` varchar(30) DEFAULT '',
  `isattachment` int(10) unsigned NOT NULL DEFAULT 0,
  `uploadtime` bigint(16) DEFAULT NULL,
  `storage` varchar(100) DEFAULT 'local',
  `sha1` varchar(40) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='附件表';
