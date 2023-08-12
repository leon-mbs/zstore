
 ALTER TABLE `customers` ADD INDEX (`phone`);
 ALTER TABLE `documents` ADD INDEX (`state`);

ALTER TABLE `notifies` ADD `created` DATETIME NULL;     
ALTER TABLE `messages` ADD `checked` tinyint(1) NULL;     
ALTER TABLE `eventlist` ADD `event_type` tinyint(4) NULL;     
ALTER TABLE `eventlist` ADD `details` text NULL;     

ALTER TABLE `eventlist` CHANGE `user_id` `user_id` int NULL ;
ALTER TABLE `eventlist` CHANGE `customer_id` `customer_id` int NULL ;

DROP VIEW messages_view  ;

CREATE VIEW messages_view
AS
SELECT
  `messages`.`message_id` AS `message_id`,
  `messages`.`user_id` AS `user_id`,
  `messages`.`created` AS `created`,
  `messages`.`message` AS `message`,
  `messages`.`item_id` AS `item_id`,
  `messages`.`item_type` AS `item_type`,
  `messages`.`checked` AS `checked`,
  `users_view`.`username` AS `username`
FROM (`messages`
  LEFT JOIN `users_view`
    ON ((`messages`.`user_id` = `users_view`.`user_id`)));
    
    


DROP VIEW eventlist_view CASCADE;

CREATE VIEW eventlist_view
AS
SELECT
  `e`.`user_id` AS `user_id`,
  `e`.`eventdate` AS `eventdate`,
  `e`.`title` AS `title`,
  `e`.`description` AS `description`,
  `e`.`event_id` AS `event_id`,
  `e`.`customer_id` AS `customer_id`,
  `e`.`isdone` AS `isdone`,
  `e`.`event_type` AS `event_type`,
  `e`.`details` AS `details`,
  `c`.`customer_name` AS `customer_name`,
  `uv`.`username` AS `username`
FROM ((`eventlist` `e`
  LEFT JOIN `customers` `c`
    ON ((`e`.`customer_id` = `c`.`customer_id`)))
  LEFT JOIN `users_view` `uv`
    ON ((`uv`.`user_id` = `e`.`user_id`)));    
    
    
delete  from  options where  optname='version' ;
insert  into options (optname,optvalue) values('version','6.8.0'); 