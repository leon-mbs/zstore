

INSERT INTO `metadata` (  `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  1, 'Начисление зарплаты', 'CalcSalary', 'Касса и платежи', 0);
INSERT INTO `metadata` (  `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  4, 'Начисления и удержания', 'SalaryTypeList', '', 0);


CREATE TABLE `saltypes` (
  `salcode` int(11) NOT NULL  ,
 
  `salname` varchar(255) NOT NULL,
  `salshortname` varchar(255) DEFAULT NULL,
 
   `disabled` tinyint(1) NOT NULL DEFAULT 0 , 
  
   PRIMARY KEY (`salcode`) 
 
) engine=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `empacc` (
  `ea_id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `optype` int(11) DEFAULT NULL,
    
  `notes` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
 
  PRIMARY KEY (`ea_id`),
  KEY `emp_id` (`emp_id`)  ,
  KEY `document_id` (`document_id`)
) engine=InnoDB DEFAULT CHARSET=utf8;

CREATE VIEW empacc_view
AS
SELECT
  `e`.`ea_id` AS `ea_id`,
  `e`.`emp_id` AS `emp_id`,
  `e`.`document_id` AS `document_id`,
  `e`.`optype` AS `optype`,
  
  `e`.`notes` AS `notes`,
  `e`.`amount` AS `amount`,
  `d`.`document_date` AS `document_date`,
  `d`.`document_number` AS `document_number`,
  `em`.`emp_name` AS `emp_name`
FROM ((`empacc` `e`
  JOIN `documents` `d`
    ON ((`d`.`document_id` = `e`.`document_id`)))
  JOIN `employees` `em`
    ON ((`em`.`employee_id` = `e`.`emp_id`)));