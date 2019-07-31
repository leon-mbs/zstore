
/* Обновение  с  версии 1.8.0 (касается  тоько  модуя задач)*/

ALTER TABLE `issue_issuelist` (
  `issue_id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_name` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `customer_id` int(11) NOT NULL,
  `status` smallint(6) NOT NULL,
  `priority` tinyint(4) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lastupdate` datetime DEFAULT NULL,
  `price` int(11) DEFAULT '0',
   PRIMARY KEY (`issue_id`)
) DEFAULT CHARSET=utf8;

ALTER TABLE `issue_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) NOT NULL,
  `createdon` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `duration` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `status` smallint(6) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `issue_id` (`issue_id`)
) DEFAULT CHARSET=utf8;

 ALTER VIEW `issue_issuelist_view` AS 
  select 
    `i`.`issue_id` AS `issue_id`,
    `i`.`issue_name` AS `issue_name`,
    `i`.`content` AS `content`,
    `i`.`customer_id` AS `customer_id`,
    `i`.`status` AS `status`,
    `i`.`priority` AS `priority`,
    `i`.`user_id` AS `user_id`,
    `i`.`lastupdate` AS `lastupdate`,
    `u`.`username` AS `username`,
    `i`.`price` AS `price`,
    `c`.`customer_name` AS `customer_name`,
    coalesce((
  select 
    sum(`issue_history`.`duration`) 
  from 
    `issue_history` 
  where 
    (`issue_history`.`issue_id` = `i`.`issue_id`)),0) AS `totaltime` 
  from 
    ((`issue_issuelist`
	
ALTER VIEW `issue_history_view` AS 
  select 
    `h`.`id` AS `id`,
    `h`.`issue_id` AS `issue_id`,
    `h`.`createdon` AS `createdon`,
    `h`.`user_id` AS `user_id`,
    `h`.`duration` AS `duration`,
    `h`.`notes` AS `notes`,
    `h`.`status` AS `status`,
    `u`.`username` AS `username`,
    `i`.`issue_name` AS `issue_name` 
  from 
    ((`issue_history` `h` join `users_view` `u` on((`h`.`user_id` = `u`.`user_id`))) join `issue_issuelist` `i` on((`h`.`issue_id` = `i`.`issue_id`)));	
	
	