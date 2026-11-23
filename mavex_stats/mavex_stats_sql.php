CREATE TABLE `mavex_stats_events` (
  `event_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_time` int(10) unsigned NOT NULL,
  `visitor_hash` char(64) NOT NULL,
  `page_path` varchar(500) NOT NULL DEFAULT '',
  `referrer_host` varchar(255) NOT NULL DEFAULT '',
  `user_agent` varchar(500) NOT NULL DEFAULT '',
  `screen_size` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`event_id`),
  KEY `event_time` (`event_time`),
  KEY `visitor_time` (`visitor_hash`,`event_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
