  
  CREATE TABLE `custacc` (
  `ca_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `optype` int(11) DEFAULT '0',
 
  `amount` decimal(10,2) NOT NULL,
  `createdon` date NOT NULL,
  PRIMARY KEY (`ca_id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE   VIEW `custacc_view` AS 
  select 
    `c`.`ca_id` AS `ca_id`,
    `c`.`customer_id` AS `customer_id`,
    `c`.`document_id` AS `document_id`,
    `c`.`optype` AS `optype`,
   
    `c`.`amount` AS `amount`,
    `d`.`document_number` AS `document_number`,
    `c`.`createdon` AS `createdon`  
  from 
    (`custacc` `c` join `documents` `d` on((`d`.`document_id` = `c`.`document_id`)));       
  
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
     
/*  

CREATE TABLE `empacc` (
  `ea_id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `optype` int(11) DEFAULT NULL,
  //`createdon` date NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
 
  PRIMARY KEY (`ea_id`),
  KEY `emp_id` (`emp_id`)
) engine=InnoDB DEFAULT CHARSET=utf8;



CREATE TABLE `prodproc` (
  `pp_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `detail` LONGTEXT DEFAULT NULL,
   PRIMARY KEY (`pp_id`)
  
) engine=InnoDB DEFAULT CHARSET=utf8;



    
     
      */