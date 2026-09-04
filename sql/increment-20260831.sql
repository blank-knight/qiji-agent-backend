-- 充值码表（2026-08-31 新增，配套个人中心充值功能）
CREATE TABLE IF NOT EXISTS `fa_recharge_code` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL DEFAULT '' COMMENT '充值码',
  `score` int(10) NOT NULL DEFAULT '0' COMMENT '兑换积分面值',
  `agent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属代理ID（0=平台通用，非0=仅该代理的用户可兑）',
  `status` enum('unused','used','disabled') NOT NULL DEFAULT 'unused' COMMENT 'unused=未使用,used=已使用,disabled=已停用',
  `used_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '兑换用户ID',
  `used_at` int(10) DEFAULT NULL COMMENT '兑换时间',
  `createtime` int(10) DEFAULT NULL,
  `updatetime` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `agent_id` (`agent_id`),
  KEY `used_by` (`used_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值码表';
