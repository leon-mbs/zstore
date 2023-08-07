
 ALTER TABLE `customers` ADD INDEX (`phone`);
 ALTER TABLE `documents` ADD INDEX (`state`);

ALTER TABLE `notifies` ADD `created` DATETIME NULL;     
ALTER TABLE `messages` ADD `checked` tinyint(1) NULL;     

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
    
