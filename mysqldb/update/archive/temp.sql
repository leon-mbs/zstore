delete from  metadata  where  meta_name='CustActivity' ;
delete from  metadata  where  meta_name='EmpAccRep'    ;
delete from  metadata  where  meta_name='SalTypeRep'   ;


CREATE TABLE  custacc (
  ca_id int(11) NOT NULL AUTO_INCREMENT,
  customer_id int(11) NOT NULL,
  document_id int(11) DEFAULT NULL,
  optype int(11) NOT NULL,
  notes varchar(255) DEFAULT NULL,
  amount decimal(10, 2) NOT NULL,
  createdon date DEFAULT NULL,
  KEY `document_id` (`document_id`) ,
  KEY `customer_id` (`customer_id`),
  PRIMARY KEY (ca_id)
) ENGINE=InnoDB;


CREATE VIEW custacc_view
AS
SELECT
  `a`.`ca_id` AS `ca_id`,
  `a`.`customer_id` AS `customer_id`,
  `a`.`document_id` AS `document_id`,
  `a`.`optype` AS `optype`,
  `a`.`notes` AS `notes`,
  `a`.`amount` AS `amount`,
  `a`.`createdon` AS `createdon`,
  `c`.`customer_name` AS `customer_name`,
  `d`.`document_number` AS `document_number`
FROM ((`custacc` `a`
  JOIN `documents` `d`
    ON ((`d`.`document_id` = `a`.`document_id`)))
  JOIN `customers` `c`
    ON ((`c`.`customer_id` = `a`.`customer_id`)));
    

    
    
ALTER TABLE `empacc` ADD `createdon` date NULL  ;

DROP VIEW empacc_view  ;

CREATE VIEW empacc_view
AS
SELECT
  `e`.`ea_id` AS `ea_id`,
  `e`.`emp_id` AS `emp_id`,
  `e`.`document_id` AS `document_id`,
  `e`.`optype` AS `optype`,
  `e`.`notes` AS `notes`,
  `e`.`amount` AS `amount`,
  coalesce(`e`.`createdon`,d.document_date ) AS `createdon`,
  `d`.`document_number` AS `document_number`,
  `em`.`emp_name` AS `emp_name`
FROM ((`empacc` `e`
  LEFT JOIN `documents` `d`
    ON ((`d`.`document_id` = `e`.`document_id`)))
  JOIN `employees` `em`
    ON ((`em`.`employee_id` = `e`.`emp_id`)));    