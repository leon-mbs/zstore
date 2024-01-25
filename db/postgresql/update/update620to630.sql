delete from  metadata  where  meta_name='CustActivity' ;
delete from  metadata  where  meta_name='EmpAccRep'    ;
delete from  metadata  where  meta_name='SalTypeRep'   ;
  
    
ALTER TABLE empacc ADD createdon DATE NULL  ;

DROP VIEW empacc_view  ;

CREATE VIEW empacc_view
AS
SELECT
  e.ea_id AS ea_id,
  e.emp_id AS emp_id,
  e.document_id AS document_id,
  e.optype AS optype,
  d.notes AS notes,
  e.amount AS amount,
  coalesce(e.createdon,d.document_date ) AS createdon,
  d.document_number AS document_number,
  em.emp_name AS emp_name
FROM ((empacc e
  LEFT JOIN documents d
    ON ((d.document_id = e.document_id)))
  JOIN employees em
    ON ((em.employee_id = e.emp_id)));    
    
    
    
CREATE VIEW cust_acc_view
AS
SELECT
  COALESCE(SUM((CASE WHEN (d.meta_name IN ('InvoiceCust', 'GoodsReceipt', 'IncomeService', 'OutcomeMoney')) THEN d.payed WHEN ((d.meta_name = 'OutcomeMoney') AND
      (d.content LIKE '%<detail>2</detail>%')) THEN d.payed WHEN (d.meta_name = 'RetCustIssue') THEN d.payamount ELSE 0 END)), 0) AS s_passive,
  COALESCE(SUM((CASE WHEN (d.meta_name IN ('GoodsReceipt') ) THEN d.payamount WHEN ((d.meta_name = 'IncomeMoney') AND
      (d.content LIKE '%<detail>2</detail>%')) THEN d.payed WHEN (d.meta_name = 'RetCustIssue') THEN d.payed ELSE 0 END)), 0) AS s_active,
  COALESCE(SUM((CASE WHEN (d.meta_name IN ('GoodsIssue', 'TTN', 'PosCheck', 'OrderFood')) THEN d.payamount WHEN ((d.meta_name = 'OutcomeMoney') AND
      (d.content LIKE '%<detail>1</detail>%')) THEN d.payed WHEN (d.meta_name = 'ReturnIssue') THEN d.payed ELSE 0 END)), 0) AS b_passive,
  COALESCE(SUM((CASE WHEN (d.meta_name IN ('GoodsIssue', 'Order', 'PosCheck', 'OrderFood', 'Invoice', 'ServiceAct')) THEN d.payed WHEN ((d.meta_name = 'IncomeMoney') AND
      (d.content LIKE '%<detail>1</detail>%')) THEN d.payed WHEN (d.meta_name = 'ReturnIssue') THEN d.payamount ELSE 0 END)), 0) AS b_active,
  d.customer_id AS customer_id
FROM documents_view d
WHERE ((d.state NOT IN (0, 1, 2, 3, 15, 8))
AND (d.customer_id > 0))
GROUP BY d.customer_id; 

 
delete  from  options where  optname='version' ;
insert  into options (optname,optvalue) values('version','6.3.0');
 