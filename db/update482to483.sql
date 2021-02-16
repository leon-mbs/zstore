 
 INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(85, 2, 'Неликвидные товары', 'NoLiq', 'Склад', 0);
 
 
/*
CREATE TABLE `empacc` (
  `ea_id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `acctype` int(11) DEFAULT NULL,
  //`createdon` date NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
 
  PRIMARY KEY (`ea_id`),
  KEY `emp_id` (`emp_id`)
)  DEFAULT CHARSET=utf8;



CREATE TABLE `prodproc` (
  `pp_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `detail` LONGTEXT DEFAULT NULL,
   PRIMARY KEY (`pp_id`)
  
)  DEFAULT CHARSET=utf8;


CREATE TABLE `subscribes` (
  `sub_id` int(11) NOT NULL AUTO_INCREMENT,
  `doc_type` int(11) DEFAULT NULL,
  `doc_state` int(11) DEFAULT NULL,
  `reciever_type` int(11) DEFAULT NULL,
  `reciever_id` int(11) DEFAULT NULL,
  `subs_type` int(11) DEFAULT NULL,
  `detail` LONGTEXT DEFAULT NULL,
   PRIMARY KEY (`sub_id`)
  
)  DEFAULT CHARSET=utf8;
    */
    
CREATE TABLE `cust_acc` (
  `ca_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `contract_id` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`ca_id`),
  KEY `customer_id` (`customer_id`),
  KEY `document_id` (`document_id`),
  KEY `contract_id` (`contract_id`),
  CONSTRAINT `cust_acc_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  CONSTRAINT `cust_acc_fk1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`document_id`),
  CONSTRAINT `cust_acc_fk2` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`contract_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8; 

CREATE   `cust_acc_view` AS 
  select 
    `ca`.`ca_id` AS `ca_id`,
    `ca`.`customer_id` AS `customer_id`,
    `ca`.`document_id` AS `document_id`,
    `ca`.`contract_id` AS `contract_id`,
    `ca`.`notes` AS `notes`,
    `ca`.`amount` AS `amount`,
    `d`.`document_number` AS `document_number`,
    `d`.`document_date` AS `document_date`,
    `d`.`meta_name` AS `meta_name`,
    `c`.`customer_name` AS `customer_name`,
    `ct`.`contract_number` AS `contract_number` 
  from 
    (((`cust_acc` `ca` join `documents_view` `d` on((`ca`.`document_id` = `d`.`document_id`))) join `customers` `c` on((`c`.`customer_id` = `ca`.`customer_id`))) left join `contracts` `ct` on((`ca`.`contract_id` = `ct`.`contract_id`)));