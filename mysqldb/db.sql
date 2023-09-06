
CREATE TABLE `branches` (
  `branch_id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_name` varchar(255) NOT NULL,
  `details` longtext NOT NULL,
  `disabled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`branch_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;


CREATE TABLE `contracts` (
  `contract_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT '0',
  `firm_id` int(11) DEFAULT '0',
  `createdon` date NOT NULL,
  `contract_number` varchar(64) NOT NULL,
  `disabled` tinyint(1) DEFAULT '0',
  `details` longtext NOT NULL,
  PRIMARY KEY (`contract_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `custitems` (
  `custitem_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `quantity` decimal(10, 3) DEFAULT NULL,
  `price` decimal(10, 2) NOT NULL DEFAULT '0.00',
  `cust_code` varchar(255) NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `updatedon` date NOT NULL,
  PRIMARY KEY (`custitem_id`),
  KEY `item_id` (`item_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(255) DEFAULT NULL,
  `detail` mediumtext NOT NULL,
  `email` varchar(64) DEFAULT NULL,
  `phone` varchar(64) DEFAULT NULL,
  `status` smallint(4) NOT NULL DEFAULT '0',
  `city` varchar(255) DEFAULT NULL,
  `leadstatus` varchar(255) DEFAULT NULL,
  `leadsource` varchar(255) DEFAULT NULL,
  `createdon` date DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `passw` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`customer_id`),
  KEY `phone` (`phone`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `docstatelog` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `docstate` smallint(6) NOT NULL,
  `createdon` datetime NOT NULL,
  `hostname` varchar(64) NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `document_id` (`document_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `userlogin` varchar(32) NOT NULL,
  `userpass` varchar(255) NOT NULL,
  `createdon` date NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `acl` mediumtext NOT NULL,
  `disabled` int(1) NOT NULL DEFAULT '0',
  `options` longtext,
  `role_id` int(11) DEFAULT NULL,
  `lastactive` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `userlogin` (`userlogin`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;


CREATE TABLE `documents` (
  `document_id` int(11) NOT NULL AUTO_INCREMENT,
  `document_number` varchar(45) NOT NULL,
  `document_date` date NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` longtext,
  `amount` decimal(11, 2) DEFAULT NULL,
  `meta_id` int(11) NOT NULL,
  `state` tinyint(4) NOT NULL,
  `notes` varchar(255) NOT NULL,
  `customer_id` int(11) DEFAULT '0',
  `payamount` decimal(11, 2) DEFAULT '0.00',
  `payed` decimal(11, 2) DEFAULT '0.00',
  `branch_id` int(11) DEFAULT '0',
  `parent_id` bigint(20) DEFAULT '0',
  `firm_id` int(11) DEFAULT NULL,
  `priority` smallint(6) DEFAULT '100',
  `lastupdate` datetime DEFAULT NULL,
  PRIMARY KEY (`document_id`),
  UNIQUE KEY `unuqnumber` (`meta_id`, `document_number`, `branch_id`),
  KEY `document_date` (`document_date`),
  KEY `customer_id` (`customer_id`),
  KEY `user_id` (`user_id`),
  KEY `branch_id` (`branch_id`),
  KEY `state` (`state`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `itemname` varchar(255) DEFAULT NULL,
  `description` longtext,
  `detail` longtext NOT NULL,
  `item_code` varchar(64) DEFAULT NULL,
  `bar_code` varchar(64) DEFAULT NULL,
  `cat_id` int(11) NOT NULL,
  `msr` varchar(64) DEFAULT NULL,
  `disabled` tinyint(1) DEFAULT '0',
  `minqty` decimal(11, 3) DEFAULT '0.000',
  `manufacturer` varchar(355) DEFAULT NULL,
  `item_type` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `item_code` (`item_code`),
  KEY `itemname` (`itemname`),
  KEY `cat_id` (`cat_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `stores` (
  `store_id` int(11) NOT NULL AUTO_INCREMENT,
  `storename` varchar(64) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `branch_id` int(11) DEFAULT '0',
  `disabled` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`store_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `store_stock` (
  `stock_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `partion` decimal(11, 2) DEFAULT NULL,
  `store_id` int(11) NOT NULL,
  `qty` decimal(11, 3) DEFAULT '0.000',
  `snumber` varchar(64) DEFAULT NULL,
  `sdate` date DEFAULT NULL,
  PRIMARY KEY (`stock_id`),
  KEY `item_id` (`item_id`),
  KEY `store_id` (`store_id`),
  CONSTRAINT `store_stock_fk` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`),
  CONSTRAINT `store_stock_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;


CREATE TABLE `empacc` (
  `ea_id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `optype` int(11) DEFAULT NULL,
  `amount` decimal(10, 2) NOT NULL,
  `createdon` date DEFAULT NULL,
  PRIMARY KEY (`ea_id`),
  KEY `emp_id` (`emp_id`),
  KEY `document_id` (`document_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(64) DEFAULT NULL,
  `detail` mediumtext,
  `disabled` tinyint(1) DEFAULT '0',
  `emp_name` varchar(64) NOT NULL,
  `branch_id` int(11) DEFAULT '0',
  PRIMARY KEY (`employee_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `entrylist` (
  `entry_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `quantity` decimal(11, 3) DEFAULT '0.000',
  `stock_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `outprice` decimal(10, 2) DEFAULT NULL,
  `tag` int(11) DEFAULT '0',
  PRIMARY KEY (`entry_id`),
  KEY `document_id` (`document_id`),
  KEY `stock_id` (`stock_id`),
  CONSTRAINT `entrylist_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`document_id`),
  CONSTRAINT `entrylist_ibfk_2` FOREIGN KEY (`stock_id`) REFERENCES `store_stock` (`stock_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;


DELIMITER ;;
CREATE TRIGGER entrylist_after_ins_tr
    AFTER INSERT
    ON entrylist
    FOR EACH ROW
BEGIN

  IF NEW.stock_id > 0 THEN

    UPDATE store_stock
    SET qty = (SELECT
        COALESCE(SUM(quantity), 0)
      FROM entrylist
      WHERE stock_id = NEW.stock_id)
    WHERE store_stock.stock_id = NEW.stock_id;

  END IF;

END;;
DELIMITER ;
DELIMITER ;;

CREATE TRIGGER  entrylist_after_del_tr
    AFTER DELETE
    ON  entrylist
    FOR EACH ROW
BEGIN


  IF OLD.stock_id > 0 THEN

    UPDATE store_stock
    SET qty = (SELECT
        COALESCE(SUM(quantity), 0)
      FROM entrylist
      WHERE stock_id = OLD.stock_id)
    WHERE store_stock.stock_id = OLD.stock_id;

  END IF;

END;;
DELIMITER ;
CREATE TABLE `equipments` (
  `eq_id` int(11) NOT NULL AUTO_INCREMENT,
  `eq_name` varchar(255) DEFAULT NULL,
  `detail` mediumtext,
  `disabled` tinyint(1) DEFAULT '0',
  `description` text,
  PRIMARY KEY (`eq_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `eventlist` (
  `user_id` int(11) DEFAULT NULL,
  `eventdate` datetime NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `isdone` tinyint(1) NOT NULL DEFAULT '0',
  `event_type` tinyint(4) DEFAULT NULL,
  `details` text,
  PRIMARY KEY (`event_id`),
  KEY `user_id` (`user_id`),
  KEY `customer_id` (`customer_id`)
) ENGINE = INNODB   DEFAULT CHARSET = utf8;

CREATE TABLE `files` (
  `file_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `item_type` int(11) NOT NULL,
  `mime` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`file_id`),
  KEY `item_id` (`item_id`)
)  ENGINE = INNODB DEFAULT CHARSET = utf8;

CREATE TABLE `filesdata` (
  `file_id` int(11) DEFAULT NULL,
  `filedata` longblob,
  UNIQUE KEY `file_id` (`file_id`)
) ENGINE = MYISAM DEFAULT CHARSET = utf8;

CREATE TABLE `firms` (
  `firm_id` int(11) NOT NULL AUTO_INCREMENT,
  `firm_name` varchar(255) NOT NULL,
  `details` longtext NOT NULL,
  `disabled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`firm_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `content` longblob NOT NULL,
  `mime` varchar(16) DEFAULT NULL,
  `thumb` longblob,
  PRIMARY KEY (`image_id`)
) ENGINE = MYISAM  DEFAULT CHARSET = utf8;

CREATE TABLE `iostate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `iotype` smallint(6) NOT NULL,
  `amount` decimal(10, 2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `issue_history` (
  `hist_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) NOT NULL,
  `createdon` date NOT NULL,
  `user_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`hist_id`),
  KEY `issue_id` (`issue_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `issue_issuelist` (
  `issue_id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_name` varchar(255) NOT NULL,
  `details` longtext NOT NULL,
  `status` smallint(6) NOT NULL,
  `priority` tinyint(4) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lastupdate` datetime DEFAULT NULL,
  `project_id` int(11) NOT NULL,
  PRIMARY KEY (`issue_id`),
  KEY `project_id` (`project_id`),
  KEY `user_id` (`user_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `issue_projectacc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `issue_projectlist` (
  `project_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_name` varchar(255) NOT NULL,
  `details` longtext NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `status` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`project_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `issue_time` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) NOT NULL,
  `createdon` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `duration` decimal(10, 2) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `issue_id` (`issue_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `item_cat` (
  `cat_id` int(11) NOT NULL AUTO_INCREMENT,
  `cat_name` varchar(255) NOT NULL,
  `detail` longtext,
  `parent_id` int(11) DEFAULT '0',
  PRIMARY KEY (`cat_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `item_set` (
  `set_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) DEFAULT '0',
  `pitem_id` int(11) NOT NULL DEFAULT '0',
  `qty` decimal(11, 3) DEFAULT '0.000',
  `service_id` int(11) DEFAULT '0',
  `cost` decimal(10, 2) DEFAULT '0.00',
  PRIMARY KEY (`set_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;


CREATE TABLE `keyval` (
  `keyd` varchar(255) NOT NULL,
  `vald` text NOT NULL,
  PRIMARY KEY (`keyd`)
) ENGINE = INNODB DEFAULT CHARSET = utf8;

CREATE TABLE `messages` (
  `message_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `message` text,
  `item_id` int(11) NOT NULL,
  `item_type` int(11) DEFAULT NULL,
  `checked` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`message_id`),
  KEY `item_id` (`item_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `metadata` (
  `meta_id` int(11) NOT NULL AUTO_INCREMENT,
  `meta_type` tinyint(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `meta_name` varchar(255) NOT NULL,
  `menugroup` varchar(255) DEFAULT NULL,
  `disabled` tinyint(4) NOT NULL,
  PRIMARY KEY (`meta_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `mfund` (
  `mf_id` int(11) NOT NULL AUTO_INCREMENT,
  `mf_name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `branch_id` int(11) DEFAULT '0',
  `detail` longtext,
  `disabled` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`mf_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `note_fav` (
  `fav_id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`fav_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `note_nodes` (
  `node_id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `mpath` varchar(255) CHARACTER SET latin1 NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ispublic` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`node_id`),
  KEY `user_id` (`user_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `note_tags` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `tagvalue` varchar(255) NOT NULL,
  PRIMARY KEY (`tag_id`),
  KEY `topic_id` (`topic_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `note_topicnode` (
  `topic_id` int(11) NOT NULL,
  `node_id` int(11) NOT NULL,
  `tn_id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tn_id`),
  KEY `topic_id` (`topic_id`),
  KEY `node_id` (`node_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `note_topics` (
  `topic_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `acctype` smallint(4) DEFAULT '0',
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`topic_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `notifies` (
  `notify_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `dateshow` datetime NOT NULL,
  `message` text,
  `sender_id` int(11) DEFAULT NULL,
  `checked` tinyint(1) NOT NULL,
  PRIMARY KEY (`notify_id`),
  KEY `user_id` (`user_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `options` (
  `optname` varchar(64) NOT NULL,
  `optvalue` longtext NOT NULL,
  UNIQUE KEY `optname` (`optname`)
) ENGINE = INNODB DEFAULT CHARSET = utf8;

CREATE TABLE `parealist` (
  `pa_id` int(11) NOT NULL AUTO_INCREMENT,
  `pa_name` varchar(255) NOT NULL,
  PRIMARY KEY (`pa_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `paylist` (
  `pl_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `amount` decimal(11, 2) NOT NULL,
  `mf_id` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `paydate` datetime DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `paytype` smallint(6) NOT NULL,
  `detail` longtext,
  `bonus` int(11) DEFAULT NULL,
  PRIMARY KEY (`pl_id`),
  KEY `document_id` (`document_id`),
  CONSTRAINT `paylist_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`document_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `poslist` (
  `pos_id` int(11) NOT NULL AUTO_INCREMENT,
  `pos_name` varchar(255) NOT NULL,
  `details` longtext NOT NULL,
  `branch_id` int(11) DEFAULT '0',
  PRIMARY KEY (`pos_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `ppo_zformrep` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `createdon` date NOT NULL,
  `fnpos` varchar(255) NOT NULL,
  `fndoc` varchar(255) NOT NULL,
  `amount` decimal(10, 2) NOT NULL,
  `cnt` smallint(6) NOT NULL,
  `ramount` decimal(10, 2) NOT NULL,
  `rcnt` smallint(6) NOT NULL,
  `sentxml` longtext NOT NULL,
  `taxanswer` longblob NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `ppo_zformstat` (
  `zf_id` int(11) NOT NULL AUTO_INCREMENT,
  `pos_id` int(11) NOT NULL,
  `checktype` int(11) NOT NULL,
  `createdon` datetime NOT NULL,
  `amount0` decimal(10, 2) NOT NULL,
  `amount1` decimal(10, 2) NOT NULL,
  `amount2` decimal(10, 2) NOT NULL,
  `amount3` decimal(10, 2) NOT NULL,
  `document_number` varchar(255) DEFAULT NULL,
  `fiscnumber` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`zf_id`)
) ENGINE = INNODB DEFAULT CHARSET = utf8;

CREATE TABLE `prodproc` (
  `pp_id` int(11) NOT NULL AUTO_INCREMENT,
  `procname` varchar(255) NOT NULL,
  `basedoc` varchar(255) DEFAULT NULL,
  `snumber` varchar(255) DEFAULT NULL,
  `state` smallint(4) DEFAULT '0',
  `detail` longtext,
  PRIMARY KEY (`pp_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `prodstage` (
  `st_id` int(11) NOT NULL AUTO_INCREMENT,
  `pp_id` int(11) NOT NULL,
  `pa_id` int(11) NOT NULL,
  `state` smallint(6) NOT NULL,
  `stagename` varchar(255) NOT NULL,
  `detail` longtext,
  PRIMARY KEY (`st_id`),
  KEY `pp_id` (`pp_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `prodstageagenda` (
  `sta_id` int(11) NOT NULL AUTO_INCREMENT,
  `st_id` int(11) NOT NULL,
  `startdate` datetime NOT NULL,
  `enddate` datetime NOT NULL,
  PRIMARY KEY (`sta_id`),
  KEY `st_id` (`st_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `crontask` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime NOT NULL,
  tasktype varchar(64) DEFAULT NULL,
  taskdata text DEFAULT NULL,
  starton datetime DEFAULT NULL,

  PRIMARY KEY (`id`)
) DEFAULT CHARSET = utf8;

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `rolename` varchar(255) DEFAULT NULL,
  `acl` mediumtext,
  PRIMARY KEY (`role_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `saltypes` (
  `st_id` int(11) NOT NULL AUTO_INCREMENT,
  `salcode` int(11) NOT NULL,
  `salname` varchar(255) NOT NULL,
  `salshortname` varchar(255) DEFAULT NULL,
  `disabled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`st_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(255) NOT NULL,
  `detail` text,
  `disabled` tinyint(1) DEFAULT '0',
  `category` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`service_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `shop_attributes` (
  `attribute_id` int(11) NOT NULL AUTO_INCREMENT,
  `attributename` varchar(64) NOT NULL,
  `cat_id` int(11) NOT NULL,
  `attributetype` tinyint(4) NOT NULL,
  `valueslist` text,
  PRIMARY KEY (`attribute_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `shop_attributes_order` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `attr_id` int(11) NOT NULL,
  `pg_id` int(11) NOT NULL,
  `ordern` int(11) NOT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `shop_attributevalues` (
  `attributevalue_id` int(11) NOT NULL AUTO_INCREMENT,
  `attribute_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `attributevalue` varchar(255) NOT NULL,
  PRIMARY KEY (`attributevalue_id`),
  KEY `attribute_id` (`attribute_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `shop_prod_comments` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `author` varchar(64) NOT NULL,
  `comment` text NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  `rating` tinyint(4) NOT NULL DEFAULT '0',
  `moderated` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`comment_id`),
  KEY `product_id` (`item_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `shop_varitems` (
  `varitem_id` int(11) NOT NULL AUTO_INCREMENT,
  `var_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  PRIMARY KEY (`varitem_id`),
  KEY `item_id` (`item_id`),
  KEY `var_id` (`var_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `shop_vars` (
  `var_id` int(11) NOT NULL AUTO_INCREMENT,
  `attr_id` int(11) NOT NULL,
  `varname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`var_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `stats` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `category` smallint(6) NOT NULL,
  `keyd` int(11) NOT NULL,
  `vald` int(11) NOT NULL,
  `dt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `dt` (`dt`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;



CREATE TABLE `subscribes` (
  `sub_id` int(11) NOT NULL AUTO_INCREMENT,
  `sub_type` int(11) DEFAULT NULL,
  `reciever_type` int(11) DEFAULT NULL,
  `msg_type` int(11) DEFAULT NULL,
  `msgtext` text,
  `detail` longtext,
  `disabled` int(1) DEFAULT '0',
  PRIMARY KEY (`sub_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE `timesheet` (
  `time_id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `t_start` datetime DEFAULT NULL,
  `t_end` datetime DEFAULT NULL,
  `t_type` int(11) DEFAULT '0',
  `t_break` smallint(6) DEFAULT '0',
  `branch_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`time_id`),
  KEY `emp_id` (`emp_id`)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;


CREATE
VIEW `users_view`
AS
SELECT
  `users`.`user_id` AS `user_id`,
  `users`.`userlogin` AS `userlogin`,
  `users`.`userpass` AS `userpass`,
  `users`.`createdon` AS `createdon`,
  `users`.`email` AS `email`,
  `users`.`acl` AS `acl`,
  `users`.`options` AS `options`,
  `users`.`disabled` AS `disabled`,
  `users`.`lastactive` AS `lastactive`,
  `roles`.`rolename` AS `rolename`,
  `users`.`role_id` AS `role_id`,
  `roles`.`acl` AS `roleacl`,
  COALESCE(`employees`.`employee_id`, 0) AS `employee_id`,
  (CASE WHEN ISNULL(`employees`.`emp_name`) THEN `users`.`userlogin` ELSE `employees`.`emp_name` END) AS `username`
FROM ((`users`
  LEFT JOIN `employees`
    ON (((`users`.`userlogin` = `employees`.`login`)
    AND (`employees`.`disabled` <> 1))))
  LEFT JOIN `roles`
    ON ((`users`.`role_id` = `roles`.`role_id`))) ;


CREATE
VIEW `documents_view`
AS
SELECT
  `d`.`document_id` AS `document_id`,
  `d`.`document_number` AS `document_number`,
  `d`.`document_date` AS `document_date`,
  `d`.`user_id` AS `user_id`,
  `d`.`content` AS `content`,
  `d`.`amount` AS `amount`,
  `d`.`meta_id` AS `meta_id`,
  `u`.`username` AS `username`,
  `c`.`customer_id` AS `customer_id`,
  `c`.`customer_name` AS `customer_name`,
  `d`.`state` AS `state`,
  `d`.`notes` AS `notes`,
  `d`.`payamount` AS `payamount`,
  `d`.`payed` AS `payed`,
  `d`.`parent_id` AS `parent_id`,
  `d`.`branch_id` AS `branch_id`,
  `b`.`branch_name` AS `branch_name`,
  `d`.`firm_id` AS `firm_id`,
  `d`.`priority` AS `priority`,
  `f`.`firm_name` AS `firm_name`,
  `d`.`lastupdate` AS `lastupdate`,
  `metadata`.`meta_name` AS `meta_name`,
  `metadata`.`description` AS `meta_desc`
FROM (((((`documents` `d`
  LEFT JOIN `users_view` `u`
    ON ((`d`.`user_id` = `u`.`user_id`)))
  LEFT JOIN `customers` `c`
    ON ((`d`.`customer_id` = `c`.`customer_id`)))
  JOIN `metadata`
    ON ((`metadata`.`meta_id` = `d`.`meta_id`)))
  LEFT JOIN `branches` `b`
    ON ((`d`.`branch_id` = `b`.`branch_id`)))
  LEFT JOIN `firms` `f`
    ON ((`d`.`firm_id` = `f`.`firm_id`))) ;
    
CREATE
VIEW `contracts_view`
AS
SELECT
  `co`.`contract_id` AS `contract_id`,
  `co`.`customer_id` AS `customer_id`,
  `co`.`firm_id` AS `firm_id`,
  `co`.`createdon` AS `createdon`,
  `co`.`contract_number` AS `contract_number`,
  `co`.`disabled` AS `disabled`,
  `co`.`details` AS `details`,
  `cu`.`customer_name` AS `customer_name`,
  `f`.`firm_name` AS `firm_name`
FROM ((`contracts` `co`
  JOIN `customers` `cu`
    ON ((`co`.`customer_id` = `cu`.`customer_id`)))
  LEFT JOIN `firms` `f`
    ON ((`co`.`firm_id` = `f`.`firm_id`))) ;

CREATE
VIEW `cust_acc_view`
AS
SELECT
  COALESCE(SUM((CASE WHEN (`d`.`meta_name` IN ('InvoiceCust', 'GoodsReceipt', 'IncomeService', 'OutcomeMoney')) THEN `d`.`payed` WHEN ((`d`.`meta_name` = 'OutcomeMoney') AND
      (`d`.`content` LIKE '%<detail>2</detail>%')) THEN `d`.`payed` WHEN (`d`.`meta_name` = 'RetCustIssue') THEN `d`.`payamount` ELSE 0 END)), 0) AS `s_passive`,
  COALESCE(SUM((CASE WHEN (`d`.`meta_name` IN ('IncomeService', 'GoodsReceipt')) THEN `d`.`payamount` WHEN ((`d`.`meta_name` = 'IncomeMoney') AND
      (`d`.`content` LIKE '%<detail>2</detail>%')) THEN `d`.`payed` WHEN (`d`.`meta_name` = 'RetCustIssue') THEN `d`.`payed` ELSE 0 END)), 0) AS `s_active`,
  COALESCE(SUM((CASE WHEN (`d`.`meta_name` IN ('GoodsIssue', 'TTN', 'PosCheck', 'OrderFood', 'ServiceAct')) THEN `d`.`payamount` WHEN ((`d`.`meta_name` = 'OutcomeMoney') AND
      (`d`.`content` LIKE '%<detail>1</detail>%')) THEN `d`.`payed` WHEN (`d`.`meta_name` = 'ReturnIssue') THEN `d`.`payed` ELSE 0 END)), 0) AS `b_passive`,
  COALESCE(SUM((CASE WHEN (`d`.`meta_name` IN ('GoodsIssue', 'Order', 'PosCheck', 'OrderFood', 'Invoice', 'ServiceAct')) THEN `d`.`payed` WHEN ((`d`.`meta_name` = 'IncomeMoney') AND
      (`d`.`content` LIKE '%<detail>1</detail>%')) THEN `d`.`payed` WHEN (`d`.`meta_name` = 'ReturnIssue') THEN `d`.`payamount` ELSE 0 END)), 0) AS `b_active`,
  `d`.`customer_id` AS `customer_id`
FROM `documents_view` `d`
WHERE ((`d`.`state` NOT IN (0, 1, 2, 3, 15, 8, 17))
AND (`d`.`customer_id` > 0))
GROUP BY `d`.`customer_id` ;

CREATE
VIEW `custitems_view`
AS
SELECT
  `s`.`custitem_id` AS `custitem_id`,
  `s`.`item_id` AS `item_id`,
  `s`.`customer_id` AS `customer_id`,
  `s`.`quantity` AS `quantity`,
  `s`.`price` AS `price`,
  `s`.`updatedon` AS `updatedon`,
  `s`.`cust_code` AS `cust_code`,
  `s`.`comment` AS `comment`,
  `i`.`itemname` AS `itemname`,
  `i`.`cat_id` AS `cat_id`,
  `i`.`item_code` AS `item_code`,
  `i`.`detail` AS `detail`,
  `c`.`customer_name` AS `customer_name`
FROM ((`custitems` `s`
  JOIN `items` `i`
    ON ((`s`.`item_id` = `i`.`item_id`)))
  JOIN `customers` `c`
    ON ((`s`.`customer_id` = `c`.`customer_id`)))
WHERE ((`i`.`disabled` <> 1)
AND (`c`.`status` <> 1)) ;

CREATE
VIEW `customers_view`
AS
SELECT
  `customers`.`customer_id` AS `customer_id`,
  `customers`.`customer_name` AS `customer_name`,
  `customers`.`detail` AS `detail`,
  `customers`.`email` AS `email`,
  `customers`.`phone` AS `phone`,
  `customers`.`status` AS `status`,
  `customers`.`city` AS `city`,
  `customers`.`leadsource` AS `leadsource`,
  `customers`.`leadstatus` AS `leadstatus`,
  `customers`.`country` AS `country`,
  `customers`.`passw` AS `passw`,
  (SELECT
      COUNT(0)
    FROM `messages` `m`
    WHERE ((`m`.`item_id` = `customers`.`customer_id`)
    AND (`m`.`item_type` = 2))) AS `mcnt`,
  (SELECT
      COUNT(0)
    FROM `files` `f`
    WHERE ((`f`.`item_id` = `customers`.`customer_id`)
    AND (`f`.`item_type` = 2))) AS `fcnt`,
  (SELECT
      COUNT(0)
    FROM `eventlist` `e`
    WHERE ((`e`.`customer_id` = `customers`.`customer_id`)
    AND (`e`.`eventdate` >= NOW()))) AS `ecnt`
FROM `customers` ;

CREATE
VIEW `docstatelog_view`
AS
SELECT
  `dl`.`log_id` AS `log_id`,
  `dl`.`user_id` AS `user_id`,
  `dl`.`document_id` AS `document_id`,
  `dl`.`docstate` AS `docstate`,
  `dl`.`createdon` AS `createdon`,
  `dl`.`hostname` AS `hostname`,
  `u`.`username` AS `username`,
  `d`.`document_number` AS `document_number`,
  `d`.`meta_desc` AS `meta_desc`,
  `d`.`meta_name` AS `meta_name`
FROM ((`docstatelog` `dl`
  JOIN `users_view` `u`
    ON ((`dl`.`user_id` = `u`.`user_id`)))
  JOIN `documents_view` `d`
    ON ((`d`.`document_id` = `dl`.`document_id`))) ;



CREATE
VIEW `empacc_view`
AS
SELECT
  `e`.`ea_id` AS `ea_id`,
  `e`.`emp_id` AS `emp_id`,
  `e`.`document_id` AS `document_id`,
  `e`.`optype` AS `optype`,
  `d`.`notes` AS `notes`,
  `e`.`amount` AS `amount`,
  COALESCE(`e`.`createdon`, `d`.`document_date`) AS `createdon`,
  `d`.`document_number` AS `document_number`,
  `em`.`emp_name` AS `emp_name`
FROM ((`empacc` `e`
  LEFT JOIN `documents` `d`
    ON ((`d`.`document_id` = `e`.`document_id`)))
  JOIN `employees` `em`
    ON ((`em`.`employee_id` = `e`.`emp_id`))) ;

    
CREATE
VIEW entrylist_view
AS
SELECT
  `entrylist`.`entry_id` AS `entry_id`,
  `entrylist`.`document_id` AS `document_id`,
  `entrylist`.`quantity` AS `quantity`,
  `documents`.`customer_id` AS `customer_id`,
  `entrylist`.`stock_id` AS `stock_id`,
  `entrylist`.`service_id` AS `service_id`,
  `entrylist`.`tag` AS `tag`,
  `store_stock`.`item_id` AS `item_id`,
  `store_stock`.`partion` AS `partion`,
  `documents`.`document_date` AS `document_date`,
  `entrylist`.`outprice` AS `outprice`
FROM ((`entrylist`
  LEFT JOIN `store_stock`
    ON ((`entrylist`.`stock_id` = `store_stock`.`stock_id`)))
  JOIN `documents`
    ON ((`entrylist`.`document_id` = `documents`.`document_id`)));    
    
    
CREATE
VIEW `eventlist_view`
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
    ON ((`uv`.`user_id` = `e`.`user_id`))) ;

CREATE
VIEW `iostate_view`
AS
SELECT
  `s`.`id` AS `id`,
  `s`.`document_id` AS `document_id`,
  `s`.`iotype` AS `iotype`,
  `s`.`amount` AS `amount`,
  `d`.`document_date` AS `document_date`,
  `d`.`branch_id` AS `branch_id`
FROM (`iostate` `s`
  JOIN `documents` `d`
    ON ((`s`.`document_id` = `d`.`document_id`))) ;

CREATE
VIEW `issue_issuelist_view`
AS
SELECT
  `i`.`issue_id` AS `issue_id`,
  `i`.`issue_name` AS `issue_name`,
  `i`.`details` AS `details`,
  `i`.`status` AS `status`,
  `i`.`priority` AS `priority`,
  `i`.`user_id` AS `user_id`,
  `i`.`lastupdate` AS `lastupdate`,
  `i`.`project_id` AS `project_id`,
  `u`.`username` AS `username`,
  `p`.`project_name` AS `project_name`
FROM ((`issue_issuelist` `i`
  LEFT JOIN `users_view` `u`
    ON ((`i`.`user_id` = `u`.`user_id`)))
  JOIN `issue_projectlist` `p`
    ON ((`i`.`project_id` = `p`.`project_id`))) ;

CREATE
VIEW `issue_projectlist_view`
AS
SELECT
  `p`.`project_id` AS `project_id`,
  `p`.`project_name` AS `project_name`,
  `p`.`details` AS `details`,
  `p`.`customer_id` AS `customer_id`,
  `p`.`status` AS `status`,
  `c`.`customer_name` AS `customer_name`,
  (SELECT
      COALESCE(SUM((CASE WHEN (`i`.`status` = 0) THEN 1 ELSE 0 END)), 0)
    FROM `issue_issuelist` `i`
    WHERE (`i`.`project_id` = `p`.`project_id`)) AS `inew`,
  (SELECT
      COALESCE(SUM((CASE WHEN (`i`.`status` > 1) THEN 1 ELSE 0 END)), 0)
    FROM `issue_issuelist` `i`
    WHERE (`i`.`project_id` = `p`.`project_id`)) AS `iproc`,
  (SELECT
      COALESCE(SUM((CASE WHEN (`i`.`status` = 1) THEN 1 ELSE 0 END)), 0)
    FROM `issue_issuelist` `i`
    WHERE (`i`.`project_id` = `p`.`project_id`)) AS `iclose`
FROM (`issue_projectlist` `p`
  LEFT JOIN `customers` `c`
    ON ((`p`.`customer_id` = `c`.`customer_id`))) ;

CREATE
VIEW `issue_time_view`
AS
SELECT
  `t`.`id` AS `id`,
  `t`.`issue_id` AS `issue_id`,
  `t`.`createdon` AS `createdon`,
  `t`.`user_id` AS `user_id`,
  `t`.`duration` AS `duration`,
  `t`.`notes` AS `notes`,
  `u`.`username` AS `username`,
  `i`.`issue_name` AS `issue_name`,
  `i`.`project_id` AS `project_id`,
  `i`.`project_name` AS `project_name`
FROM ((`issue_time` `t`
  JOIN `users_view` `u`
    ON ((`t`.`user_id` = `u`.`user_id`)))
  JOIN `issue_issuelist_view` `i`
    ON ((`t`.`issue_id` = `i`.`issue_id`))) ;

    
CREATE
VIEW `items_view`
AS
SELECT
  `items`.`item_id` AS `item_id`,
  `items`.`itemname` AS `itemname`,
  `items`.`description` AS `description`,
  `items`.`detail` AS `detail`,
  `items`.`item_code` AS `item_code`,
  `items`.`bar_code` AS `bar_code`,
  `items`.`cat_id` AS `cat_id`,
  `items`.`msr` AS `msr`,
  `items`.`disabled` AS `disabled`,
  `items`.`minqty` AS `minqty`,
  `items`.`item_type` AS `item_type`,
  `items`.`manufacturer` AS `manufacturer`,
  `item_cat`.`cat_name` AS `cat_name`
FROM (`items`
  LEFT JOIN `item_cat`
    ON ((`items`.`cat_id` = `item_cat`.`cat_id`))) ;    
    
CREATE
VIEW `item_set_view`
AS
SELECT
  `item_set`.`set_id` AS `set_id`,
  `item_set`.`item_id` AS `item_id`,
  `item_set`.`pitem_id` AS `pitem_id`,
  `item_set`.`qty` AS `qty`,
  `item_set`.`service_id` AS `service_id`,
  `item_set`.`cost` AS `cost`,
  `items`.`itemname` AS `itemname`,
  `items`.`item_code` AS `item_code`,
  `services`.`service_name` AS `service_name`
FROM ((`item_set`
  LEFT JOIN `items`
    ON ((`item_set`.`item_id` = `items`.`item_id`)))
  LEFT JOIN `services`
    ON ((`item_set`.`service_id` = `services`.`service_id`))) ;



CREATE
VIEW `messages_view`
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
    ON ((`messages`.`user_id` = `users_view`.`user_id`))) ;

CREATE
VIEW `note_nodesview`
AS
SELECT
  `note_nodes`.`node_id` AS `node_id`,
  `note_nodes`.`pid` AS `pid`,
  `note_nodes`.`title` AS `title`,
  `note_nodes`.`mpath` AS `mpath`,
  `note_nodes`.`user_id` AS `user_id`,
  `note_nodes`.`ispublic` AS `ispublic`,
  (SELECT
      COUNT(`note_topicnode`.`topic_id`) AS `Count(topic_id)`
    FROM `note_topicnode`
    WHERE (`note_topicnode`.`node_id` = `note_nodes`.`node_id`)) AS `tcnt`
FROM `note_nodes` ;

CREATE
VIEW `note_topicnodeview`
AS
SELECT
  `note_topicnode`.`topic_id` AS `topic_id`,
  `note_topicnode`.`node_id` AS `node_id`,
  `note_topicnode`.`tn_id` AS `tn_id`,
  `note_topics`.`title` AS `title`,
  `note_nodes`.`user_id` AS `user_id`,
  `note_topics`.`content` AS `content`
FROM ((`note_topics`
  JOIN `note_topicnode`
    ON ((`note_topics`.`topic_id` = `note_topicnode`.`topic_id`)))
  JOIN `note_nodes`
    ON ((`note_nodes`.`node_id` = `note_topicnode`.`node_id`))) ;

CREATE
VIEW `note_topicsview`
AS
SELECT
  `t`.`topic_id` AS `topic_id`,
  `t`.`title` AS `title`,
  `t`.`content` AS `content`,
  `t`.`acctype` AS `acctype`,
  `t`.`user_id` AS `user_id`
FROM `note_topics` `t` ;

CREATE
VIEW `paylist_view`
AS
SELECT
  `pl`.`pl_id` AS `pl_id`,
  `pl`.`document_id` AS `document_id`,
  `pl`.`amount` AS `amount`,
  `pl`.`mf_id` AS `mf_id`,
  `pl`.`notes` AS `notes`,
  `pl`.`user_id` AS `user_id`,
  `pl`.`paydate` AS `paydate`,
  `pl`.`paytype` AS `paytype`,
  `pl`.`bonus` AS `bonus`,
  `d`.`document_number` AS `document_number`,
  `u`.`username` AS `username`,
  `m`.`mf_name` AS `mf_name`,
  `d`.`customer_id` AS `customer_id`,
  `d`.`customer_name` AS `customer_name`
FROM (((`paylist` `pl`
  JOIN `documents_view` `d`
    ON ((`pl`.`document_id` = `d`.`document_id`)))
  LEFT JOIN `users_view` `u`
    ON ((`pl`.`user_id` = `u`.`user_id`)))
  LEFT JOIN `mfund` `m`
    ON ((`pl`.`mf_id` = `m`.`mf_id`))) ;


CREATE
VIEW `prodstageagenda_view`
AS
SELECT
  `a`.`sta_id` AS `sta_id`,
  `a`.`st_id` AS `st_id`,
  `a`.`startdate` AS `startdate`,
  `a`.`enddate` AS `enddate`,
  `pv`.`stagename` AS `stagename`,
  `pv`.`state` AS `state`,
  (TIMESTAMPDIFF(MINUTE, `a`.`startdate`, `a`.`enddate`) / 60) AS `hours`,
  `pv`.`pa_id` AS `pa_id`,
  `pv`.`pp_id` AS `pp_id`
FROM (`prodstageagenda` `a`
  JOIN `prodstage` `pv`
    ON ((`a`.`st_id` = `pv`.`st_id`))) ;    
    
    
CREATE
VIEW `prodstage_view`
AS
SELECT
  `ps`.`st_id` AS `st_id`,
  `ps`.`pp_id` AS `pp_id`,
  `ps`.`pa_id` AS `pa_id`,
  `ps`.`state` AS `state`,
  `ps`.`stagename` AS `stagename`,
  COALESCE((SELECT
      MIN(`pag`.`startdate`)
    FROM `prodstageagenda` `pag`
    WHERE (`pag`.`st_id` = `ps`.`st_id`)), NULL) AS `startdate`,
  COALESCE((SELECT
      MAX(`pag`.`enddate`)
    FROM `prodstageagenda` `pag`
    WHERE (`pag`.`st_id` = `ps`.`st_id`)), NULL) AS `enddate`,
  COALESCE((SELECT
      MAX(`pag`.`hours`)
    FROM `prodstageagenda_view` `pag`
    WHERE (`pag`.`st_id` = `ps`.`st_id`)), NULL) AS `hours`,
  `ps`.`detail` AS `detail`,
  `pr`.`procname` AS `procname`,
  `pr`.`snumber` AS `snumber`,
  `pr`.`state` AS `procstate`,
  `pa`.`pa_name` AS `pa_name`
FROM ((`prodstage` `ps`
  JOIN `prodproc` `pr`
    ON ((`pr`.`pp_id` = `ps`.`pp_id`)))
  JOIN `parealist` `pa`
    ON ((`pa`.`pa_id` = `ps`.`pa_id`))) ;    
    
CREATE
VIEW `prodproc_view`
AS
SELECT
  `p`.`pp_id` AS `pp_id`,
  `p`.`procname` AS `procname`,
  `p`.`basedoc` AS `basedoc`,
  `p`.`snumber` AS `snumber`,
  `p`.`state` AS `state`,
  COALESCE((SELECT
      MIN(`ps`.`startdate`)
    FROM `prodstage_view` `ps`
    WHERE (`ps`.`pp_id` = `p`.`pp_id`)), NULL) AS `startdate`,
  COALESCE((SELECT
      MAX(`ps`.`enddate`)
    FROM `prodstage_view` `ps`
    WHERE (`ps`.`pp_id` = `p`.`pp_id`)), NULL) AS `enddate`,
  COALESCE((SELECT
      COUNT(0)
    FROM `prodstage` `ps`
    WHERE (`ps`.`pp_id` = `p`.`pp_id`)), NULL) AS `stagecnt`,
  `p`.`detail` AS `detail`
FROM `prodproc` `p` ;





CREATE
VIEW `roles_view`
AS
SELECT
  `roles`.`role_id` AS `role_id`,
  `roles`.`rolename` AS `rolename`,
  `roles`.`acl` AS `acl`,
  (SELECT
      COALESCE(COUNT(0), 0)
    FROM `users`
    WHERE (`users`.`role_id` = `roles`.`role_id`)) AS `cnt`
FROM `roles` ;

CREATE
VIEW `shop_attributes_view`
AS
SELECT
  `shop_attributes`.`attribute_id` AS `attribute_id`,
  `shop_attributes`.`attributename` AS `attributename`,
  `shop_attributes`.`cat_id` AS `cat_id`,
  `shop_attributes`.`attributetype` AS `attributetype`,
  `shop_attributes`.`valueslist` AS `valueslist`,
  `shop_attributes_order`.`ordern` AS `ordern`
FROM (`shop_attributes`
  JOIN `shop_attributes_order`
    ON (((`shop_attributes`.`attribute_id` = `shop_attributes_order`.`attr_id`)
    AND (`shop_attributes`.`cat_id` = `shop_attributes_order`.`pg_id`))))
ORDER BY `shop_attributes_order`.`ordern` ;

CREATE
VIEW `shop_products_view`
AS
SELECT
  `i`.`item_id` AS `item_id`,
  `i`.`itemname` AS `itemname`,
  `i`.`description` AS `description`,
  `i`.`detail` AS `detail`,
  `i`.`item_code` AS `item_code`,
  `i`.`bar_code` AS `bar_code`,
  `i`.`cat_id` AS `cat_id`,
  `i`.`msr` AS `msr`,
  `i`.`disabled` AS `disabled`,
  `i`.`minqty` AS `minqty`,
  `i`.`item_type` AS `item_type`,
  `i`.`manufacturer` AS `manufacturer`,
  `i`.`cat_name` AS `cat_name`,
  COALESCE((SELECT
      SUM(`store_stock`.`qty`)
    FROM `store_stock`
    WHERE (`store_stock`.`item_id` = `i`.`item_id`)), 0) AS `qty`,
  COALESCE((SELECT
      COUNT(0)
    FROM `shop_prod_comments` `c`
    WHERE (`c`.`item_id` = `i`.`item_id`)), 0) AS `comments`,
  COALESCE((SELECT
      SUM(`c`.`rating`)
    FROM `shop_prod_comments` `c`
    WHERE (`c`.`item_id` = `i`.`item_id`)), 0) AS `ratings`
FROM `items_view` `i` ;

CREATE
VIEW `shop_varitems_view`
AS
SELECT
  `shop_varitems`.`varitem_id` AS `varitem_id`,
  `shop_varitems`.`var_id` AS `var_id`,
  `shop_varitems`.`item_id` AS `item_id`,
  `sv`.`attr_id` AS `attr_id`,
  `sa`.`attributevalue` AS `attributevalue`,
  `it`.`itemname` AS `itemname`,
  `it`.`item_code` AS `item_code`
FROM (((`shop_varitems`
  JOIN `shop_vars` `sv`
    ON ((`shop_varitems`.`var_id` = `sv`.`var_id`)))
  JOIN `shop_attributevalues` `sa`
    ON (((`sa`.`item_id` = `shop_varitems`.`item_id`)
    AND (`sv`.`attr_id` = `sa`.`attribute_id`))))
  JOIN `items` `it`
    ON ((`shop_varitems`.`item_id` = `it`.`item_id`))) ;

CREATE
VIEW `shop_vars_view`
AS
SELECT
  `shop_vars`.`var_id` AS `var_id`,
  `shop_vars`.`attr_id` AS `attr_id`,
  `shop_vars`.`varname` AS `varname`,
  `shop_attributes`.`attributename` AS `attributename`,
  `shop_attributes`.`cat_id` AS `cat_id`,
  (SELECT
      COUNT(0)
    FROM `shop_varitems`
    WHERE (`shop_varitems`.`var_id` = `shop_vars`.`var_id`)) AS `cnt`
FROM ((`shop_vars`
  JOIN `shop_attributes`
    ON ((`shop_vars`.`attr_id` = `shop_attributes`.`attribute_id`)))
  JOIN `item_cat`
    ON ((`shop_attributes`.`cat_id` = `item_cat`.`cat_id`))) ;

CREATE
VIEW `store_stock_view`
AS
SELECT
  `st`.`stock_id` AS `stock_id`,
  `st`.`item_id` AS `item_id`,
  `st`.`partion` AS `partion`,
  `st`.`store_id` AS `store_id`,
  `i`.`itemname` AS `itemname`,
  `i`.`item_code` AS `item_code`,
  `i`.`cat_id` AS `cat_id`,
  `i`.`msr` AS `msr`,
  `i`.`item_type` AS `item_type`,
  `i`.`bar_code` AS `bar_code`,
  `i`.`cat_name` AS `cat_name`,
  `i`.`disabled` AS `itemdisabled`,
  `stores`.`storename` AS `storename`,
  `st`.`qty` AS `qty`,
  `st`.`snumber` AS `snumber`,
  `st`.`sdate` AS `sdate`
FROM ((`store_stock` `st`
  JOIN `items_view` `i`
    ON (((`i`.`item_id` = `st`.`item_id`)
    AND (`i`.`disabled` <> 1))))
  JOIN `stores`
    ON ((`stores`.`store_id` = `st`.`store_id`))) ;

CREATE
VIEW `timesheet_view`
AS
SELECT
  `t`.`time_id` AS `time_id`,
  `t`.`emp_id` AS `emp_id`,
  `t`.`description` AS `description`,
  `t`.`t_start` AS `t_start`,
  `t`.`t_end` AS `t_end`,
  `t`.`t_type` AS `t_type`,
  `t`.`t_break` AS `t_break`,
  `e`.`emp_name` AS `emp_name`,
  `b`.`branch_name` AS `branch_name`,
  `e`.`disabled` AS `disabled`,
  `t`.`branch_id` AS `branch_id`
FROM ((`timesheet` `t`
  JOIN `employees` `e`
    ON ((`t`.`emp_id` = `e`.`employee_id`)))
  LEFT JOIN `branches` `b`
    ON ((`t`.`branch_id` = `b`.`branch_id`))) ;


CREATE TABLE  taglist (
  id int(11) NOT NULL AUTO_INCREMENT,
  tag_type smallint(6) NOT NULL,
  item_id int(11) NOT NULL,
  tag_name varchar(255) NOT NULL,
  PRIMARY KEY (id)
)
ENGINE = InnoDB DEFAULT CHARSET=utf8;

