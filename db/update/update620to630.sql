delete from  metadata  where  meta_name='CustActivity' ;
delete from  metadata  where  meta_name='EmpAccRep'    ;
delete from  metadata  where  meta_name='SalTypeRep'   ;
  
    
ALTER TABLE `empacc` ADD `createdon` date NULL  ;


DROP VIEW empacc_view  ;

CREATE VIEW empacc_view
AS
SELECT
  `e`.`ea_id` AS `ea_id`,
  `e`.`emp_id` AS `emp_id`,
  `e`.`document_id` AS `document_id`,
  `e`.`optype` AS `optype`,
  `d`.`notes` AS `notes`,
  `e`.`amount` AS `amount`,
  coalesce(`e`.`createdon`,d.document_date ) AS `createdon`,
  `d`.`document_number` AS `document_number`,
  `em`.`emp_name` AS `emp_name`
FROM ((`empacc` `e`
  LEFT JOIN `documents` `d`
    ON ((`d`.`document_id` = `e`.`document_id`)))
  JOIN `employees` `em`
    ON ((`em`.`employee_id` = `e`.`emp_id`)));    
    
    
 
 
delete  from  options where  optname='version' ;
insert  into options (optname,optvalue) values('version','6.3.0');
 