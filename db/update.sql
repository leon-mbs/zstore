/*Обновление  с  версии v1.5.0*/


CREATE TABLE `note_nodes` (
  `node_id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `mpath` varchar(255) CHARACTER SET latin1 NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`node_id`),
  KEY `user_id` (`user_id`)
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
)  DEFAULT CHARSET=utf8;

CREATE TABLE `note_topics` (
  `topic_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `favorites` tinyint(1) NOT NULL DEFAULT '0',
  `ispublic` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`topic_id`)
)  DEFAULT CHARSET=utf8;

CREATE  VIEW `note_topicnodeview` AS
  select 
    `note_topicnode`.`topic_id` AS `topic_id`,
    `note_topicnode`.`node_id` AS `node_id`,
    `note_topicnode`.`tn_id` AS `tn_id`,
    `note_topics`.`title` AS `title`,
    `note_nodes`.`user_id` AS `user_id`,
    `note_topics`.`content` AS `content` 
  from 
    ((`note_topics` join `note_topicnode` on((`note_topics`.`topic_id` = `note_topicnode`.`topic_id`))) join `note_nodes` on((`note_nodes`.`node_id` = `note_topicnode`.`node_id`)));


CREATE  VIEW note_topicsview AS 
  select 
    t.topic_id AS topic_id,
    t.title AS title,
    t.content AS content,
    t.favorites AS favorites,
    t.ispublic AS ispublic 
  from 
    note_topics t;	
CREATE   VIEW `note_nodesview` AS
  select 
    `note_nodes`.`node_id` AS `node_id`,
    `note_nodes`.`pid` AS `pid`,
    `note_nodes`.`title` AS `title`,
    `note_nodes`.`mpath` AS `mpath`,
    `note_nodes`.`user_id` AS `user_id`,
    (
  select 
    count(`note_topicnode`.`topic_id`) AS `Count(topic_id)` 
  from 
    `note_topicnode` 
  where 
    (`note_topicnode`.`node_id` = `note_nodes`.`node_id`)) AS `tcnt` 
  from 
    `note_nodes`;
	
	
/*Обновление  с  версии v1.6.0*/
	
	ALTER TABLE `services` ADD `disabled` TINYINT(1) NULL DEFAULT '0'  ;
	ALTER TABLE `employees` ADD `disabled` TINYINT(1) NULL DEFAULT '0'  ;
    ALTER TABLE `equipments` ADD `disabled` TINYINT(1) NULL DEFAULT '0'  ;
   	
	

	