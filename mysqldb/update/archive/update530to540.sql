

INSERT INTO `metadata` (  `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  1, 'Начисление зарплаты', 'CalcSalary', 'Касса и платежи', 0);
INSERT INTO `metadata` (  `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  4, 'Начисления и удержания', 'SalaryTypeList', '', 0);
INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Зарплата', 'SalaryList', 'Касса и платежи', 0);
INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Начисления и удержания', 'SalTypeRep', 'Зарплата', 0);
INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Движение  по л/с', 'EmpAccRep', 'Зарплата', 0);


UPDATE `metadata`   set  menugroup = 'Касса и платежи' where  meta_name= 'OutSalary';
UPDATE `metadata`   set  description = 'Перекомплектация (расфасовка)' where  meta_name= 'TransItem';

INSERT INTO `options` (`optname`, `optvalue`) VALUES('salary', 'a:4:{s:13:\"codebaseincom\";s:3:\"105\";s:10:\"coderesult\";s:3:\"900\";s:4:\"calc\";s:212:\"v200 = v105\r\n//налоги\r\nv220 =  v200 * 0.18\r\nv300 =  v200 * 0.22\r\n//всего  удержано\r\nv600 =v200  - v220- v300\r\n//на руки\r\nv900 =v200 - v600-v850\r\n\r\n\r\n//пример\r\nif(invalid){\r\n   \r\n}  \";s:11:\"codeadvance\";s:3:\"850\";}');


CREATE TABLE `saltypes` (
  `st_id` int(11) NOT NULL AUTO_INCREMENT,
 
  `salcode` int(11) NOT NULL,
  `salname` varchar(255) NOT NULL,
  `salshortname` varchar(255) DEFAULT NULL,
 
  `disabled` tinyint(1) NOT NULL DEFAULT 0 , 
  
   PRIMARY KEY (`st_id`) 
 
) engine=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `empacc` (
  `ea_id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `optype` int(11) DEFAULT NULL,
  
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
   
  `d`.`notes` AS `notes`,
  `e`.`amount` AS `amount`,
  `d`.`document_date` AS `document_date`,
  `d`.`document_number` AS `document_number`,
  `em`.`emp_name` AS `emp_name`
FROM ((`empacc` `e`
  JOIN `documents` `d`
    ON ((`d`.`document_id` = `e`.`document_id`)))
  JOIN `employees` `em`
    ON ((`em`.`employee_id` = `e`.`emp_id`)));
    
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(2, 105, 'Основная  зарплата', 'осн', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(3, 200, 'Всего начислено', 'Всего. нач', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(4, 600, 'Всего  удержано', 'всего удер', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(5, 900, 'К выдаче', 'К выдаче', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(6, 850, 'Аванс', 'Аванс', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(7, 220, 'НДФЛ', 'НДФЛ', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(8, 300, 'ЕСВ', 'ЕСВ', 0);
    