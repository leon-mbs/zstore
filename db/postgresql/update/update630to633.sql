update  metadata set  menugroup ='Продажі' where  meta_name='ItemOrder';

DROP VIEW cust_acc_view  ;

CREATE VIEW cust_acc_view
AS
SELECT
  COALESCE(SUM((CASE WHEN (d.meta_name IN ('InvoiceCust', 'GoodsReceipt', 'IncomeService', 'OutcomeMoney')) THEN d.payed WHEN ((d.meta_name = 'OutcomeMoney') AND
      (d.content LIKE '%<detail>2</detail>%')) THEN d.payed WHEN (d.meta_name = 'RetCustIssue') THEN d.payamount ELSE 0 END)), 0) AS s_passive,
  COALESCE(SUM((CASE WHEN (d.meta_name IN ('IncomeService', 'GoodsReceipt')) THEN d.payamount WHEN ((d.meta_name = 'IncomeMoney') AND
      (d.content LIKE '%<detail>2</detail>%')) THEN d.payed WHEN (d.meta_name = 'RetCustIssue') THEN d.payed ELSE 0 END)), 0) AS s_active,
  COALESCE(SUM((CASE WHEN (d.meta_name IN ('GoodsIssue', 'TTN', 'PosCheck', 'OrderFood', 'ServiceAct')) THEN d.payamount WHEN ((d.meta_name = 'OutcomeMoney') AND
      (d.content LIKE '%<detail>1</detail>%')) THEN d.payed WHEN (d.meta_name = 'ReturnIssue') THEN d.payed ELSE 0 END)), 0) AS b_passive,
  COALESCE(SUM((CASE WHEN (d.meta_name IN ('GoodsIssue', 'Order', 'PosCheck', 'OrderFood', 'Invoice', 'ServiceAct')) THEN d.payed WHEN ((d.meta_name = 'IncomeMoney') AND
      (d.content LIKE '%<detail>1</detail>%')) THEN d.payed WHEN (d.meta_name = 'ReturnIssue') THEN d.payamount ELSE 0 END)), 0) AS b_active,
  d.customer_id AS customer_id
FROM documents_view d
WHERE ((d.state NOT IN (0, 1, 2, 3, 15, 8, 17))
AND (d.customer_id > 0))
GROUP BY d.customer_id;


delete  from  options where  optname='version' ;
insert  into options (optname,optvalue) values('version','6.3.3');
 