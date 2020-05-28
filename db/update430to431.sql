CREATE TABLE `firms` (
  `firm_id` int(11) NOT NULL AUTO_INCREMENT,
  `firm_name` varchar(255) NOT NULL,
  `details` longtext NOT NULL,
  `disabled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`firm_id`)
) ENGINE=MyISAM   CHARSET=utf8;


ALTER TABLE `documents` ADD `firm_id` INT NULL DEFAULT '0'  , ADD INDEX (`firm_id`);

ALTER  VIEW `documents_view` AS 
  select 
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
    `f`.`firm_name` AS `firm_name`,
    `metadata`.`meta_name` AS `meta_name`,
    `metadata`.`description` AS `meta_desc` 
  from 
    (((((`documents` `d` left join `users_view` `u` on((`d`.`user_id` = `u`.`user_id`))) left join `customers` `c` on((`d`.`customer_id` = `c`.`customer_id`))) join `metadata` on((`metadata`.`meta_id` = `d`.`meta_id`))) left join `branches` `b` on((`d`.`branch_id` = `b`.`branch_id`))) left join `firms` `f` on((`d`.`firm_id` = `f`.`firm_id`)));
    
    
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  1, 'Договор', 'Contract', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  4, 'Договора', 'ContractList', '', 0);
    
    