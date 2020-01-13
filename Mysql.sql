CREATE TABLE `typecho_links` (
  `sid` int(10) unsigned NOT NULL auto_increment COMMENT 'shorts表主键',
  `name` varchar(200) default NULL COMMENT '番剧标题',
  `orginal` varchar(200) default NULL COMMENT '原名',
  `text` longtext default NULL COMMENT '短评',
  `intro` text default NULL COMMENT '简介',
  `image` text default NULL COMMENT '番剧图片',
  `time` varchar(200) default NULL COMMENT '时间',
  `short2` varchar(200) default NULL COMMENT '插件扩展',
  `ord` int(10) unsigned default '0' COMMENT 'shorts排序',
  PRIMARY KEY  (`lid`)
) ENGINE=MYISAM  DEFAULT CHARSET=%charset%;
