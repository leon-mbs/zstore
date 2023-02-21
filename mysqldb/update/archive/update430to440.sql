CREATE TABLE `firms` (
  `firm_id` int(11) NOT NULL AUTO_INCREMENT,
  `firm_name` varchar(255) NOT NULL,
  `details` longtext    ,
  `disabled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`firm_id`)
) ENGINE=MyISAM   CHARSET=utf8;


 
   
CREATE TABLE `contracts` (
  `contract_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT '0',
  `firm_id` int(11) DEFAULT '0',
  `createdon` date NOT NULL,
  `contract_number` varchar(64) NOT NULL,
  `disabled` tinyint(1) DEFAULT '0',
  `details` longtext NOT NULL,
  PRIMARY KEY (`contract_id`)
)   DEFAULT CHARSET=utf8;    


CREATE  VIEW `contracts_view` AS 
  select 
    `co`.`contract_id` AS `contract_id`,
    `co`.`customer_id` AS `customer_id`,
    `co`.`firm_id` AS `firm_id`,
    `co`.`createdon` AS `createdon`,
    `co`.`contract_number` AS `contract_number`,
    `co`.`disabled` AS `disabled`,
    `co`.`details` AS `details`,
    `cu`.`customer_name` AS `customer_name`,
    `f`.`firm_name` AS `firm_name` 
  from 
    ((`contracts` `co` join `customers` `cu` on((`co`.`customer_id` = `cu`.`customer_id`))) left join `firms` `f` on((`co`.`firm_id` = `f`.`firm_id`)));
 