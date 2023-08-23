ALTER TABLE paylist CHANGE paytype paytype mediumint NOT NULL;

ALTER TABLE `customers` ADD INDEX (`phone`);
ALTER TABLE `documents` ADD INDEX (`state`);

ALTER TABLE `messages` ADD `checked` tinyint(1) NULL;     
ALTER TABLE `eventlist` ADD `event_type` tinyint(4) NULL default 0;     
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
    
    


DROP VIEW eventlist_view  ;

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
    

CREATE TABLE crontask (
  id int(11) NOT NULL AUTO_INCREMENT,
  created datetime NOT NULL,
  tasktype varchar(64) DEFAULT NULL,
  taskdata text DEFAULT NULL,
  starton datetime DEFAULT NULL,
  PRIMARY KEY (id)
) 
 ENGINE = InnoDB DEFAULT CHARSET=utf8;    
    
CREATE TABLE  taglist (
  id int(11) NOT NULL AUTO_INCREMENT,
  tag_type smallint(6) NOT NULL,
  item_id int(11) NOT NULL,
  tag_name varchar(255) NOT NULL,
  PRIMARY KEY (id)
)
ENGINE = InnoDB DEFAULT CHARSET=utf8;


    
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Кафе', 'OutFood', 'Продажі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Платіжний календар', 'PayTable', 'Каса та платежі', 0);
                  
 
    
delete  from  options where  optname='version' ;
insert  into options (optname,optvalue) values('version','6.8.0'); 