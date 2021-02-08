 delete from `metadata` where  `meta_name` ='ContractList' and meta_type=4   ;
 INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(85, 2, 'Неликвидные товары', 'NoLiq', 'Склад', 0);
 INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(86, 1, 'Договор', 'Contract', '', 0);
 INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(87, 3, 'Договора', 'ContractList', '', 0);
 
 
 
/*
CREATE TABLE `empacc` (
  `ea_id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `acctype` int(11) DEFAULT NULL,
  `createdon` date NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `income` decimal(10,2) NOT NULL,
  `outcome` decimal(10,2) NOT NULL,
  PRIMARY KEY (`ea_id`),
  KEY `emp_id` (`emp_id`)
)  DEFAULT CHARSET=utf8;



CREATE TABLE `prodproc` (
  `pp_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `detail` LONGTEXT DEFAULT NULL,
   PRIMARY KEY (`pp_id`)
  
)  DEFAULT CHARSET=utf8;

*/