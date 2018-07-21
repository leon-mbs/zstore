SET NAMES 'utf8';


CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(255) DEFAULT NULL,
  `detail` text NOT NULL,
  `email` varchar(64) DEFAULT NULL,
  `phone` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`customer_id`)
) ENGINE=InnoDB   DEFAULT CHARSET=utf8;

#
# Structure for the `docrel` table : 
#

CREATE TABLE `docrel` (
  `doc1` int(11) DEFAULT NULL,
  `doc2` int(11) DEFAULT NULL,
  KEY `doc1` (`doc1`),
  KEY `doc2` (`doc2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8  ;

#
# Structure for the `document_log` table : 
#

CREATE TABLE `document_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `hostname` varchar(128) DEFAULT NULL,
  `document_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `document_state` tinyint(4) NOT NULL,
  `updatedon` datetime NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `document_id` (`document_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB   DEFAULT CHARSET=utf8;

#
# Structure for the `documents` table : 
#

CREATE TABLE `documents` (
  `document_id` int(11) NOT NULL AUTO_INCREMENT,
  `document_number` varchar(45) NOT NULL,
  `document_date` date NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text,
  `amount` int(11) DEFAULT NULL,
  `meta_id` int(11) NOT NULL,
  `state` tinyint(4) NOT NULL,
  `datatag` int(11) DEFAULT NULL,
  `notes` varchar(255) NOT NULL,
  PRIMARY KEY (`document_id`),
  KEY `document_date` (`document_date`)
) ENGINE=InnoDB   DEFAULT CHARSET=utf8;

#
# Structure for the `employees` table : 
#

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(64) DEFAULT NULL,
  `detail` text,
  `emp_name` varchar(64) NOT NULL,
  PRIMARY KEY (`employee_id`)
) ENGINE=InnoDB   DEFAULT CHARSET=utf8;

#
# Structure for the `entrylist` table : 
#

CREATE TABLE `entrylist` (
  `entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL DEFAULT '0',
  `quantity` int(11) NOT NULL DEFAULT '0',
  `customer_id` int(11) NOT NULL DEFAULT '0',
  `employee_id` int(11) NOT NULL DEFAULT '0',
  `extcode` int(11) NOT NULL DEFAULT '0',
  `stock_id` int(11) NOT NULL DEFAULT '0',
  `service_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`entry_id`),
  KEY `document_id` (`document_id`),
  KEY `stock_id` (`stock_id`)
) ENGINE=InnoDB   DEFAULT CHARSET=utf8;

CREATE TRIGGER `entrylist_after_ins_tr` AFTER INSERT ON `entrylist`
  FOR EACH ROW
BEGIN



 IF new.stock_id >0 then

  update store_stock set qty=(select  coalesce(sum(quantity),0) from entrylist where stock_id=new.stock_id) where store_stock.stock_id = new.stock_id;
 END IF;
END;

CREATE TRIGGER `entrylist_after_del_tr` AFTER DELETE ON `entrylist`
  FOR EACH ROW
BEGIN


 IF old.stock_id >0 then

  update store_stock set qty=(select  coalesce(sum(quantity),0) from entrylist where stock_id=old.stock_id) where store_stock.stock_id = old.stock_id;
 END IF;
END;

#
# Structure for the `eventlist` table : 
#

CREATE TABLE `eventlist` (
  `user_id` int(11) NOT NULL,
  `eventdate` datetime NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `notify_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  PRIMARY KEY (`event_id`),
  KEY `user_id` (`user_id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB   DEFAULT CHARSET=utf8;

#
# Structure for the `files` table : 
#

CREATE TABLE `files` (
  `file_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `item_type` int(11) NOT NULL,
  PRIMARY KEY (`file_id`)
) ENGINE=InnoDB   DEFAULT CHARSET=utf8;

#
# Structure for the `filesdata` table : 
#

CREATE TABLE `filesdata` (
  `file_id` int(11) DEFAULT NULL,
  `filedata` longblob,
  UNIQUE KEY `file_id` (`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Structure for the `item_cat` table : 
#

CREATE TABLE `item_cat` (
  `cat_id` int(11) NOT NULL AUTO_INCREMENT,
  `cat_name` varchar(255) NOT NULL,
  PRIMARY KEY (`cat_id`)
) ENGINE=InnoDB   DEFAULT CHARSET=utf8;

#
# Structure for the `items` table : 
#

CREATE TABLE `items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `itemname` varchar(64) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `detail` text NOT NULL,
  `item_code` varchar(64) DEFAULT NULL,
  `bar_code` varchar(64) DEFAULT NULL,
  `cat_id` int(11) NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `item_code` (`item_code`),
  KEY `itemname` (`itemname`),
  KEY `cat_id` (`cat_id`)
) ENGINE=InnoDB   DEFAULT CHARSET=utf8;

#
# Structure for the `messages` table : 
#

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `message` text,
  `item_id` int(11) NOT NULL,
  `item_type` int(11) DEFAULT NULL,
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB    DEFAULT CHARSET=utf8;

#
# Structure for the `metadata` table : 
#

CREATE TABLE `metadata` (
  `meta_id` int(11) NOT NULL AUTO_INCREMENT,
  `meta_type` tinyint(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `meta_name` varchar(255) NOT NULL,
  `menugroup` varchar(255) DEFAULT NULL COMMENT '???????????  ???   ???????',
  `notes` text NOT NULL,
  `disabled` tinyint(4) NOT NULL,
  PRIMARY KEY (`meta_id`)
) ENGINE=InnoDB    DEFAULT CHARSET=utf8;

#
# Structure for the `notifies` table : 
#

CREATE TABLE `notifies` (
  `notify_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `dateshow` datetime NOT NULL,
  `checked` tinyint(1) NOT NULL DEFAULT '0',
  `message` text NOT NULL,
  PRIMARY KEY (`notify_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB   DEFAULT CHARSET=utf8;

#
# Structure for the `options` table : 
#

CREATE TABLE `options` (
  `optname` varchar(64) NOT NULL,
  `optvalue` text NOT NULL,
  UNIQUE KEY `optname` (`optname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Structure for the `services` table : 
#

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(255) NOT NULL,
  `detail` text,
  PRIMARY KEY (`service_id`)
) ENGINE=InnoDB   DEFAULT CHARSET=utf8;


#
# Structure for the `store_stock` table : 
#

CREATE TABLE `store_stock` (
  `stock_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `partion` int(11) DEFAULT NULL,
  `store_id` int(11) NOT NULL,
  `qty` int(11) DEFAULT NULL,
  PRIMARY KEY (`stock_id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB   DEFAULT CHARSET=utf8;

#
# Structure for the `stores` table : 
#

CREATE TABLE `stores` (
  `store_id` int(11) NOT NULL AUTO_INCREMENT,
  `storename` varchar(64) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`store_id`)
) ENGINE=InnoDB   DEFAULT CHARSET=utf8 COMMENT='????? ????????';

#
# Structure for the `users` table : 
#

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `userlogin` varchar(32) NOT NULL,
  `userpass` varchar(255) NOT NULL,
  `createdon` date NOT NULL,
  `active` int(1) NOT NULL DEFAULT '0',
  `email` varchar(255) DEFAULT NULL,
  `acl` text NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `userlogin` (`userlogin`)
) ENGINE=InnoDB   DEFAULT CHARSET=utf8;


#
# Definition for the `users_view` view :
#

CREATE VIEW `users_view` AS
  select
    `users`.`user_id` AS `user_id`,
    `users`.`userlogin` AS `userlogin`,
    `users`.`userpass` AS `userpass`,
    `users`.`createdon` AS `createdon`,
    `users`.`active` AS `active`,
    `users`.`email` AS `email`,
    `users`.`acl` AS `acl`,
    (case when isnull(`employees`.`emp_name`) then `users`.`userlogin` else `employees`.`emp_name` end) AS `username`
  from
    (`users` left join `employees` on((`users`.`userlogin` = `employees`.`login`)));



#
# Definition for the `customers_view` view : 
#

CREATE VIEW `customers_view` AS
  select 
    `customers`.`customer_id` AS `customer_id`,
    `customers`.`customer_name` AS `customer_name`,
    `customers`.`detail` AS `detail`,
    `customers`.`email` AS `email`,
    `customers`.`phone` AS `phone` 
  from 
    `customers`;

#
# Definition for the `documents_view` view : 
#

CREATE VIEW `documents_view` AS 
  select 
    `d`.`document_id` AS `document_id`,
    `d`.`document_number` AS `document_number`,
    `d`.`document_date` AS `document_date`,
    `d`.`user_id` AS `user_id`,
    `d`.`content` AS `content`,
    `d`.`amount` AS `amount`,
    `d`.`meta_id` AS `meta_id`,
    `u`.`username` AS `username`,
    `d`.`state` AS `state`,
    `d`.`notes` AS `notes`,
    `d`.`datatag` AS `datatag`,
    `metadata`.`meta_name` AS `meta_name`,
    `metadata`.`description` AS `meta_desc` 
  from 
    ((`documents` `d` join `users_view` `u` on((`d`.`user_id` = `u`.`user_id`))) join `metadata` on((`metadata`.`meta_id` = `d`.`meta_id`)));

#
# Definition for the `entrylist_view` view : 
#

CREATE VIEW `entrylist_view` AS 
  select 
    `entrylist`.`entry_id` AS `entry_id`,
    `entrylist`.`document_id` AS `document_id`,
    `entrylist`.`amount` AS `amount`,
    `entrylist`.`quantity` AS `quantity`,
    `entrylist`.`customer_id` AS `customer_id`,
    `entrylist`.`employee_id` AS `employee_id`,
    `entrylist`.`extcode` AS `extcode`,
    `entrylist`.`stock_id` AS `stock_id`,
    `entrylist`.`service_id` AS `service_id`,
    `store_stock`.`item_id` AS `item_id`,
    `documents`.`document_date` AS `document_date` 
  from 
    ((`entrylist` left join `store_stock` on((`entrylist`.`stock_id` = `store_stock`.`stock_id`))) join `documents` on((`entrylist`.`document_id` = `documents`.`document_id`)));

#
# Definition for the `eventlist_view` view : 
#

CREATE VIEW `eventlist_view` AS 
  select 
    `e`.`user_id` AS `user_id`,
    `e`.`eventdate` AS `eventdate`,
    `e`.`title` AS `title`,
    `e`.`description` AS `description`,
    `e`.`notify_id` AS `notify_id`,
    `e`.`event_id` AS `event_id`,
    `e`.`customer_id` AS `customer_id`,
    `c`.`customer_name` AS `customer_name` 
  from 
    (`eventlist` `e` left join `customers` `c` on((`e`.`customer_id` = `c`.`customer_id`)));

#
# Definition for the `items_view` view : 
#

CREATE VIEW `items_view` AS 
  select 
    `items`.`item_id` AS `item_id`,
    `items`.`itemname` AS `itemname`,
    `items`.`description` AS `description`,
    `items`.`detail` AS `detail`,
    `items`.`item_code` AS `item_code`,
    `items`.`bar_code` AS `bar_code`,
    `items`.`cat_id` AS `cat_id`,
    `item_cat`.`cat_name` AS `cat_name` 
  from 
    (`items` left join `item_cat` on((`items`.`cat_id` = `item_cat`.`cat_id`)));

#
# Definition for the `messages_view` view : 
#

CREATE VIEW `messages_view` AS 
  select 
    `messages`.`message_id` AS `message_id`,
    `messages`.`user_id` AS `user_id`,
    `messages`.`created` AS `created`,
    `messages`.`message` AS `message`,
    `messages`.`item_id` AS `item_id`,
    `messages`.`item_type` AS `item_type`,
    `users_view`.`username` AS `username` 
  from 
    (`messages` join `users_view` on((`messages`.`user_id` = `users_view`.`user_id`)));


CREATE VIEW `store_stock_view` AS 
  select 
    `st`.`stock_id` AS `stock_id`,
    `st`.`item_id` AS `item_id`,
    `st`.`partion` AS `partion`,
    `st`.`store_id` AS `store_id`,
    `i`.`itemname` AS `itemname`,
    `i`.`item_code` AS `item_code`,
    `i`.`cat_id` AS `cat_id`,
    `i`.`bar_code` AS `bar_code`,
    `stores`.`storename` AS `storename`,
    `st`.`qty` AS `qty` 
  from 
    ((`store_stock` `st` join `items` `i` on((`i`.`item_id` = `st`.`item_id`))) join `stores` on((`stores`.`store_id` = `st`.`store_id`)));

    