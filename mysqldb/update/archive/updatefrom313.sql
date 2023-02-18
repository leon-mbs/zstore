

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
  
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(1, 4, 'Склады', 'StoreList', 'Товары', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(2, 4, 'Номенклатура', 'ItemList', 'Товары', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(3, 4, 'Сотрудники', 'EmployeeList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(4, 4, 'Категории товаров', 'CategoryList', 'Товары', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(5, 4, 'Контрагенты', 'CustomerList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(6, 1, 'Приходная накладная', 'GoodsReceipt', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(7, 1, 'Расходная накладная', 'GoodsIssue', 'Продажи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(8, 3, 'Общий журнал', 'DocList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(10, 1, 'Гарантийный талон', 'Warranty', 'Продажи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(12, 2, 'Движение по складу', 'ItemActivity', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(13, 2, 'ABC анализ', 'ABC', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(14, 4, 'Услуги, работы', 'ServiceList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(15, 1, 'Акт выполненных работ', 'ServiceAct', 'Услуги', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(16, 1, 'Возврат от покупателя', 'ReturnIssue', 'Продажи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(18, 3, 'Наряды', 'TaskList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(19, 1, 'Наряд', 'Task', 'Производство', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(20, 2, 'Оплата по нарядам', 'EmpTask', 'Производство', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(21, 2, 'Закупки', 'Income', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(22, 2, 'Продажи', 'Outcome', 'Продажи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(46, 4, 'Денежные счета', 'MFList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(27, 3, 'Заказы клиентов', 'OrderList', 'Продажи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(28, 1, 'Заказ', 'Order', 'Продажи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(30, 1, 'Оприходование  с  производства', 'ProdReceipt', 'Производство', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(31, 1, 'Списание на  производство', 'ProdIssue', 'Производство', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(32, 2, 'Отчет по производству', 'Prod', 'Производство', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(33, 4, 'Производственные участвки', 'ProdAreaList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(38, 1, 'Заявка  поставщику', 'OrderCust', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(35, 3, 'Продажи', 'GIList', 'Продажи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(36, 4, 'Оборудование', 'EqList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(37, 3, 'Закупки', 'GRList', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(39, 3, 'Заявки поставщикам', 'OrderCustList', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(40, 2, 'Прайс', 'Price', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(41, 1, 'Возврат поставщику', 'RetCustIssue', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(69, 3, 'Работы, услуги', 'SerList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(44, 1, 'Перекомплектация ТМЦ', 'TransItem', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(47, 3, 'Журнал платежей', 'PayList', 'Касса и платежи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(48, 2, 'Движение по денежным счетам', 'PayActivity', 'Платежи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(64, 1, 'Списание ТМЦ', 'OutcomeItem', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(50, 1, 'Приходный ордер', 'IncomeMoney', 'Платежи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(51, 1, 'Расходный ордер', 'OutcomeMoney', 'Платежи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(53, 2, 'Платежный баланс', 'PayBalance', 'Платежи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(57, 1, 'Инвентаризация', 'Inventory', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(58, 1, 'Счет входящий', 'InvoiceCust', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(59, 1, 'Счет-фактура', 'Invoice', 'Продажи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(60, 5, 'Импорт номенклатуры', 'Import', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(61, 3, 'Движение  ТМЦ', 'StockList', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(62, 1, 'Кассовый чек', 'POSCheck', 'Продажи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(63, 2, 'Товары в  пути', 'CustOrder', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(65, 1, 'Оприходование ТМЦ', 'IncomeItem', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(66, 4, 'POS терминалы', 'PosList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(67, 5, 'АРМ кассира', 'ARMPos', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(70, 3, 'Расчеты с  контрагентами', 'PayCustList', 'Касса и платежи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(71, 3, 'Товары  на  складе', 'ItemList', 'Склад', 0);

  
  
  update documents  set payed=amount,payamount=amount