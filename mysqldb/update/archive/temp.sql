delete from  metadata  where  meta_name='CustActivity' ;
delete from  metadata  where  meta_name='EmpAccRep'    ;
delete from  metadata  where  meta_name='SalTypeRep'   ;
INSERT INTO `metadata` (  `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Ввод  початккових залишків', 'BeginData', '', 0);


  
    
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
    
    
    
CREATE VIEW cust_acc_view
AS
SELECT
  COALESCE(SUM((CASE WHEN (`d`.`meta_name` IN ('InvoiceCust', 'GoodsReceipt', 'IncomeService', 'OutcomeMoney')) THEN `d`.`payed` WHEN ((`d`.`meta_name` = 'OutcomeMoney') AND
      (`d`.`content` LIKE '%<detail>2</detail>%')) THEN `d`.`payed` WHEN (`d`.`meta_name` = 'RetCustIssue') THEN `d`.`payamount` ELSE 0 END)), 0) AS `s_passive`,
  COALESCE(SUM((CASE WHEN (`d`.`meta_name` = 'GoodsReceipt') THEN `d`.`payamount` WHEN ((`d`.`meta_name` = 'IncomeMoney') AND
      (`d`.`content` LIKE '%<detail>2</detail>%')) THEN `d`.`payed` WHEN (`d`.`meta_name` = 'RetCustIssue') THEN `d`.`payed` ELSE 0 END)), 0) AS `s_active`,
  COALESCE(SUM((CASE WHEN (`d`.`meta_name` IN ('GoodsIssue', 'TTN', 'PosCheck', 'OrderFood')) THEN `d`.`payamount` WHEN ((`d`.`meta_name` = 'OutcomeMoney') AND
      (`d`.`content` LIKE '%<detail>1</detail>%')) THEN `d`.`payed` WHEN (`d`.`meta_name` = 'ReturnIssue') THEN `d`.`payed` ELSE 0 END)), 0) AS `b_passive`,
  COALESCE(SUM((CASE WHEN (`d`.`meta_name` IN ('GoodsIssue', 'Order', 'PosCheck', 'OrderFood', 'Invoice', 'ServiceAct')) THEN `d`.`payed` WHEN ((`d`.`meta_name` = 'IncomeMoney') AND
      (`d`.`content` LIKE '%<detail>1</detail>%')) THEN `d`.`payed` WHEN (`d`.`meta_name` = 'ReturnIssue') THEN `d`.`payamount` ELSE 0 END)), 0) AS `b_active`,
  `d`.`customer_id` AS `customer_id`
FROM `documents_view` `d`
WHERE ((`d`.`state` > 3)
AND (`d`.`customer_id` > 0))
GROUP BY `d`.`customer_id`; 

    
  оплата поставщику
    
    invoicecust          payed     +
    goodsreceipt         payed     +
    IncomeService         payed    +
    OutcomeMoney         payed     +    '%<detail>2</detail>%'

  товар  поставщику
    retcust      payamoyunt   +
    
    

  товар  от поставщика
    goodsreceipt         payamount  -
    
  оплата  от  поставщика
    
    retcust      payed              -
    IncomeMoneay   payed     -  '%<detail>2</detail>%'
    
    

 товар  покупателю    +
   
   GoodsIssue  payAmount 
   TTN  payAmount 
   PosCheck  payAmount 
   OrderFood   payAmount     -
     
  
   оплата  покупателю   +
    
    OutcomeMoney         payed     +    '%<detail>1</detail>%'
    retissue payed   
   
 товар  от покупателя
   
   retissue   payamount
   
 оплата  от покупателя
    
   IncomeMoneay   payed     -  '%<detail>1</detail>%'
   Order   payed     -
   OrderFood   payed     -
   Invoice   payed     -
   GoodsIssue   payed     -
   ServiceAct   payed     -
   PosCheck   payed     -
   
    
    
    
SELECT
coalesce(
SUM(
CASE  when  meta_name IN('InvoiceCust','GoodsReceipt','IncomeService','OutcomeMoney') THEN  payed
   when  meta_name = 'OutcomeMoney' and    content like '%<detail>2</detail>%'   THEN  payed
   when  meta_name IN('RetCustIssue') THEN  payamount
ELSE  0 END 

),0) as  s_passive,

coalesce(SUM(
CASE  when  meta_name IN('GoodsReceipt') THEN  payamount
   when  meta_name = 'IncomeMoney' and    content like '%<detail>2</detail>%'   THEN  payed
   when  meta_name IN('RetCustIssue') THEN  payed
ELSE  0 END 

),0) as  s_active,


coalesce(SUM(
CASE  when  meta_name IN('GoodsIssue','TTN','PosCheck','OrderFood') THEN  payamount
   when  meta_name = 'OutcomeMoney' and    content like '%<detail>1</detail>%'   THEN  payed
   when  meta_name IN('ReturnIssue') THEN  payed
ELSE  0 END 

),0) as  b_passive,

coalesce(SUM(
CASE  when  meta_name IN('GoodsIssue','Order','PosCheck','OrderFood','Invoice','ServiceAct') THEN  payed
   when  meta_name = 'IncomeMoney' and    content like '%<detail>1</detail>%'   THEN  payed
   when  meta_name IN('ReturnIssue') THEN  payamount
ELSE  0 END 

),0) as  b_active,



 customer_id FROM  documents_view d  where state  > 3 and  customer_id >0   GROUP BY   customer_id  
 