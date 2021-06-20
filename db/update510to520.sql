

CREATE TABLE `iostate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `iotype` smallint(6) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
  
CREATE   VIEW `iostate_view` AS 
  select 
    `s`.`id` AS `id`,
    `s`.`document_id` AS `document_id`,
    `s`.`iotype` AS `iotype`,
    `s`.`amount` AS `amount`,
    `d`.`document_date` AS `document_date`,
    `d`.`branch_id` AS `branch_id` 
  from 
    (`iostate` `s` join `documents` `d` on((`s`.`document_id` = `d`.`document_id`)));  
     
ALTER TABLE `paylist` CHANGE `mf_id` `mf_id` INT(11) NULL;

DROP VIEW `paylist_view`;

CREATE VIEW `paylist_view` AS 
  select 
    `pl`.`pl_id` AS `pl_id`,
    `pl`.`document_id` AS `document_id`,
    `pl`.`amount` AS `amount`,
    `pl`.`mf_id` AS `mf_id`,
    `pl`.`notes` AS `notes`,
    `pl`.`user_id` AS `user_id`,
    `pl`.`paydate` AS `paydate`,
    `pl`.`paytype` AS `paytype`,
    `pl`.`detail` AS `detail`,
    `d`.`document_number` AS `document_number`,
    `u`.`username` AS `username`,
    `m`.`mf_name` AS `mf_name`,
    `d`.`customer_id` AS `customer_id`,
    `d`.`customer_name` AS `customer_name` 
  from 
    (((`paylist` `pl` join `documents_view` `d` on((`pl`.`document_id` = `d`.`document_id`))) join `users_view` `u` on((`pl`.`user_id` = `u`.`user_id`))) left join `mfund` `m` on((`pl`.`mf_id` = `m`.`mf_id`)));     
    
INSERT INTO `metadata` (  `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Журнал доставок', 'DeliveryList', 'Общепит', 0);
INSERT INTO `metadata` (  `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 5, 'АРМ кухни (бара)', 'ArmProdFood', 'Общепит', 0);
INSERT INTO `metadata` (  `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Доходы и расходы', 'IOState', '', 0);
    