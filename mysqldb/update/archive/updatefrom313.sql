

  SET NAMES 'utf8';

  CREATE TABLE branches (
  branch_id INT(11) NOT NULL AUTO_INCREMENT,
  branch_name VARCHAR(255) NOT NULL,
  details LONGTEXT NOT NULL,
  disabled TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (branch_id)
)
 ;
 
 ALTER TABLE stores 
  ADD COLUMN branch_id INT(11)   DEFAULT 0;
  
  ALTER TABLE mfund 
  ADD COLUMN branch_id INT(11)   DEFAULT 0;
  
ALTER TABLE employees 
  ADD COLUMN branch_id INT(11) DEFAULT 0;
  
--
ALTER TABLE item_cat 
  ADD COLUMN detail LONGTEXT DEFAULT NULL; 
--
ALTER TABLE documents 
  ADD COLUMN branch_id INT(11) DEFAULT 0;

 
--
ALTER TABLE documents 
  ADD COLUMN parent_id BIGINT(20) DEFAULT 0;
  
  



--
CREATE TABLE poslist (
  pos_id INT(11) NOT NULL AUTO_INCREMENT,
  pos_name VARCHAR(255) NOT NULL,
  details LONGTEXT NOT NULL,
  branch_id INT(11) DEFAULT 0,
  PRIMARY KEY (pos_id)
)
 ;
  DROP VIEW documents_view CASCADE;
  
CREATE
VIEW documents_view
AS
SELECT
  `d`.`document_id` AS `document_id`,
  `d`.`document_number` AS `document_number`,
  `d`.`document_date` AS `document_date`,
  `d`.`user_id` AS `user_id`,
  `d`.`content` AS `content`,
  `d`.`amount` AS `amount`,
  `d`.`meta_id` AS `meta_id`,
  `u`.`username` AS `username`,
  `c`.`customer_id` AS `customer_id`,
  `c`.`customer_name` AS `customer_name`,
  `d`.`state` AS `state`,
  `d`.`notes` AS `notes`,
  `d`.`payamount` AS `payamount`,
  `d`.`payed` AS `payed`,
  `d`.`parent_id` AS `parent_id`,
  `d`.`branch_id` AS `branch_id`,
  `b`.`branch_name` AS `branch_name`,
  `metadata`.`meta_name` AS `meta_name`,
  `metadata`.`description` AS `meta_desc`
FROM ((((`documents` `d`
  JOIN `users_view` `u`
    ON ((`d`.`user_id` = `u`.`user_id`)))
  LEFT JOIN `customers` `c`
    ON ((`d`.`customer_id` = `c`.`customer_id`)))
  JOIN `metadata`
    ON ((`metadata`.`meta_id` = `d`.`meta_id`)))
  LEFT JOIN `branches` `b`
    ON ((`d`.`branch_id` = `b`.`branch_id`)));

 
DROP VIEW paylist_view CASCADE;

 
CREATE
VIEW paylist_view
AS
SELECT
  `pl`.`pl_id` AS `pl_id`,
  `pl`.`document_id` AS `document_id`,
  `pl`.`amount` AS `amount`,
  `pl`.`mf_id` AS `mf_id`,
  `pl`.`notes` AS `notes`,
  `pl`.`user_id` AS `user_id`,
  `pl`.`paydate` AS `paydate`,
  `pl`.`paytype` AS `paytype`,
  `d`.`document_number` AS `document_number`,
  `u`.`username` AS `username`,
  `m`.`mf_name` AS `mf_name`
FROM (((`paylist` `pl`
  JOIN `documents_view` `d`
    ON ((`pl`.`document_id` = `d`.`document_id`)))
  JOIN `users_view` `u`
    ON ((`pl`.`user_id` = `u`.`user_id`)))
  LEFT JOIN `mfund` `m`
    ON ((`pl`.`mf_id` = `m`.`mf_id`)));
	
	
ALTER TABLE notifies 
  ADD COLUMN sender_name VARCHAR(255) DEFAULT NULL;

  
  
  
  
delete from  metadata;  
  
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(1, 4, '������', 'StoreList', '������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(2, 4, '������������', 'ItemList', '������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(3, 4, '����������', 'EmployeeList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(4, 4, '��������� �������', 'CategoryList', '������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(5, 4, '�����������', 'CustomerList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(6, 1, '��������� ���������', 'GoodsReceipt', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(7, 1, '��������� ���������', 'GoodsIssue', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(8, 3, '����� ������', 'DocList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(10, 1, '����������� �����', 'Warranty', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(12, 2, '�������� �� ������', 'ItemActivity', '�����', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(13, 2, 'ABC ������', 'ABC', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(14, 4, '������, ������', 'ServiceList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(15, 1, '��� ����������� �����', 'ServiceAct', '������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(16, 1, '������� �� ����������', 'ReturnIssue', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(18, 3, '������', 'TaskList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(19, 1, '�����', 'Task', '������������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(20, 2, '������ �� �������', 'EmpTask', '������������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(21, 2, '�������', 'Income', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(22, 2, '�������', 'Outcome', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(46, 4, '�������� �����', 'MFList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(27, 3, '������ ��������', 'OrderList', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(28, 1, '�����', 'Order', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(30, 1, '�������������  �  ������������', 'ProdReceipt', '������������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(31, 1, '�������� ��  ������������', 'ProdIssue', '������������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(32, 2, '����� �� ������������', 'Prod', '������������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(33, 4, '���������������� ��������', 'ProdAreaList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(38, 1, '������  ����������', 'OrderCust', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(35, 3, '�������', 'GIList', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(36, 4, '������������', 'EqList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(37, 3, '�������', 'GRList', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(39, 3, '������ �����������', 'OrderCustList', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(40, 2, '�����', 'Price', '�����', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(41, 1, '������� ����������', 'RetCustIssue', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(69, 3, '������, ������', 'SerList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(44, 1, '���������������� ���', 'TransItem', '�����', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(47, 3, '������ ��������', 'PayList', '����� � �������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(48, 2, '�������� �� �������� ������', 'PayActivity', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(64, 1, '�������� ���', 'OutcomeItem', '�����', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(50, 1, '��������� �����', 'IncomeMoney', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(51, 1, '��������� �����', 'OutcomeMoney', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(53, 2, '��������� ������', 'PayBalance', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(57, 1, '��������������', 'Inventory', '�����', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(58, 1, '���� ��������', 'InvoiceCust', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(59, 1, '����-�������', 'Invoice', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(60, 5, '������ ������������', 'Import', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(61, 3, '��������  ���', 'StockList', '�����', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(62, 1, '�������� ���', 'POSCheck', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(63, 2, '������ �  ����', 'CustOrder', '�������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(65, 1, '������������� ���', 'IncomeItem', '�����', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(66, 4, 'POS ���������', 'PosList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(67, 5, '��� �������', 'ARMPos', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(70, 3, '������� �  �������������', 'PayCustList', '����� � �������', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(71, 3, '������  ��  ������', 'ItemList', '�����', 0);

  
  
  update documents  set payed=amount,payamount=amount