CREATE TABLE `note_topics` (
  `topic_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `favorites` tinyint(1) NOT NULL DEFAULT '0',
  `ispublic` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`topic_id`)
)  DEFAULT CHARSET=utf8;

CREATE TABLE `note_topicnode` (
  `topic_id` int(11) NOT NULL,
  `node_id` int(11) NOT NULL,
  `tn_id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tn_id`),
  KEY `topic_id` (`topic_id`),
  KEY `node_id` (`node_id`)
)  DEFAULT CHARSET=utf8;

CREATE TABLE `note_tags` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `tagvalue` varchar(255) NOT NULL,
  PRIMARY KEY (`tag_id`),
  KEY `topic_id` (`topic_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `note_nodes` (
  `node_id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `mpath` varchar(255) CHARACTER SET latin1 NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`node_id`),
  KEY `user_id` (`user_id`)
) DEFAULT

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `topicsview` AS 
  select 
    `t`.`topic_id` AS `topic_id`,
    `t`.`title` AS `title`,
    `t`.`content` AS `content`,
    `t`.`favorites` AS `favorites`,
    `t`.`ispublic` AS `ispublic`,
    `tn`.`user_id` AS `user_id` 
  from 
    (`topics` `t` join `topicnodeview` `tn` on((`t`.`topic_id` = `tn`.`topic_id`)));

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `topicnodeview` AS 
  select 
    `topicnode`.`topic_id` AS `topic_id`,
    `topicnode`.`node_id` AS `node_id`,
    `topicnode`.`tn_id` AS `tn_id`,
    `topics`.`title` AS `title`,
    `nodes`.`user_id` AS `user_id`,
    `topics`.`content` AS `content` 
  from 
    ((`topics` join `topicnode` on((`topics`.`topic_id` = `topicnode`.`topic_id`))) join `nodes` on((`nodes`.`node_id` = `topicnode`.`node_id`)));
	
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `topicsview` AS 
  select 
    `t`.`topic_id` AS `topic_id`,
    `t`.`title` AS `title`,
    `t`.`content` AS `content`,
    `t`.`favorites` AS `favorites`,
    `t`.`ispublic` AS `ispublic`,
    `tn`.`user_id` AS `user_id` 
  from 
    (`topics` `t` join `topicnodeview` `tn` on((`t`.`topic_id` = `tn`.`topic_id`)));	
	
	