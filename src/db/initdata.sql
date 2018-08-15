
SET NAMES 'utf8';


 
INSERT  INTO `users` (`user_id`, `userlogin`, `userpass`, `createdon`, `active`, `email`, `acl`) VALUES(4, 'admin', '$2y$10$GsjC.thVpQAPMQMO6b4Ma.olbIFr2KMGFz12l5/wnmxI1PEqRDQf.', '2017-01-01', 1, 'admin@admin.admin', '<detail><acl><detail><acl>0</acl><onlymy>0</onlymy><aclview></aclview><acledit></acledit><menu></menu><widgets></widgets></detail></acl><onlymy>0</onlymy><aclview></aclview><acledit></acledit><menu></menu><widgets></widgets></detail>');

 
INSERT  INTO `stores` (`store_id`, `storename`, `description`) VALUES(19, 'Основной склад', '');

 
INSERT  INTO `options` (`optname`, `optvalue`) VALUES('common', 'a:5:{s:8:"firmname";s:20:"Наша  фирма";s:5:"cdoll";s:1:"2";s:5:"ceuro";s:1:"5";s:4:"crub";s:3:"0.4";s:6:"useval";b:0;}');


INSERT  INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`) VALUES(1, 4, 'Склады', 'StoreList', 'Номенклатура', '', 0);
INSERT  INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`) VALUES(2, 4, 'Товары', 'ItemList', 'Номенклатура', '', 0);
INSERT  INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`) VALUES(3, 4, 'Сотрудники', 'EmployeeList', '', '', 0);
INSERT  INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`) VALUES(4, 4, 'Категории товаров', 'CategoryList', 'Номенклатура', '', 0);
INSERT  INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`) VALUES(5, 4, 'Контрагенты', 'CustomerList', '', '', 0);
INSERT  INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`) VALUES(6, 1, 'Приходная накладная', 'GoodsReceipt', '', '', 0);
INSERT  INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`) VALUES(7, 1, 'Расходная накладная', 'GoodsIssue', '', '', 0);
INSERT  INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`) VALUES(8, 3, 'Журнал документов', 'DocList', '', '', 0);
INSERT  INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`) VALUES(9, 3, 'Товары на складе', 'StockList', '', '', 0);
INSERT  INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`) VALUES(10, 1, 'Гарантийный талон', 'Warranty', '', '', 1);
INSERT  INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`) VALUES(11, 1, 'Перемещение товара', 'MoveItem', '', '', 0);
INSERT  INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`) VALUES(12, 2, 'Движение по складу', 'ItemActivity', '', '', 0);
INSERT  INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`) VALUES(13, 2, 'ABC анализ', 'ABC', '', '', 0);
INSERT  INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`) VALUES(14, 4, 'Услуги, работы', 'ServiceList', '', '', 0);
INSERT  INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`) VALUES(15, 1, 'Акт выполненных работ', 'ServiceAct', '', '', 0);
INSERT  INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`) VALUES(16, 1, 'Возврат от покупателя', 'ReturnIssue', '', '', 0);
INSERT  INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`) VALUES(17, 3, 'Расчеты с  контрагентами', 'PayList', '', '', 0);
INSERT  INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`) VALUES(18, 3, 'Работы, наряды', 'TaskList', '', '', 0);
INSERT  INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`) VALUES(19, 1, 'Наряд', 'Task', '', 'Наряд на выполнение работы, задачи', 0);
INSERT  INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`) VALUES(20, 2, 'Оплата по нарядам', 'EmpTask', '', '', 0);
