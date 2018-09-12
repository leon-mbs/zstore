
SET NAMES 'utf8';


 
INSERT   INTO `users` (  `userlogin`, `userpass`, `createdon`, `active`, `email`, `acl`, `smartmenu`) VALUES(  'admin', '$2y$10$GsjC.thVpQAPMQMO6b4Ma.olbIFr2KMGFz12l5/wnmxI1PEqRDQf.', '2017-01-01', 1, 'admin@admin.admin', '<detail><acltype>1</acltype><onlymy></onlymy><aclview></aclview><acledit></acledit><widgets></widgets></detail>', NULL);

 
INSERT  INTO `stores` (  `storename`, `description`) VALUES(  'Основной склад', '');

 
INSERT  INTO `options` (`optname`, `optvalue`) VALUES('common', 'a:5:{s:8:"firmname";s:20:"Наша  фирма";s:5:"cdoll";s:1:"2";s:5:"ceuro";s:1:"5";s:4:"crub";s:3:"0.4";s:6:"useval";b:0;}');



INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(1, 4, 'Склады', 'StoreList', 'Товары', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(2, 4, 'Номенклатура', 'ItemList', 'Товары', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(3, 4, 'Сотрудники', 'EmployeeList', '', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(4, 4, 'Категории товаров', 'CategoryList', 'Товары', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(5, 4, 'Контрагенты', 'CustomerList', '', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(6, 1, 'Приходная накладная', 'GoodsReceipt', '', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(7, 1, 'Расходная накладная', 'GoodsIssue', '', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(8, 3, 'Журнал документов', 'DocList', '', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(9, 3, 'Товары на складе', 'StockList', '', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(10, 1, 'Гарантийный талон', 'Warranty', '', '', 1, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(11, 1, 'Перемещение товара', 'MoveItem', '', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(12, 2, 'Движение по складу', 'ItemActivity', '', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(13, 2, 'ABC анализ', 'ABC', '', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(14, 4, 'Услуги, работы', 'ServiceList', '', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(15, 1, 'Акт выполненных работ', 'ServiceAct', '', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(16, 1, 'Возврат от покупателя', 'ReturnIssue', '', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(17, 3, 'Расчеты с  контрагентами', 'PayList', '', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(18, 3, 'Работы, наряды', 'TaskList', '', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(19, 1, 'Наряд', 'Task', '', 'Наряд на выполнение работы, задачи', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(20, 2, 'Оплата по нарядам', 'EmpTask', '', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(21, 2, 'Закупки', 'Income', '', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(22, 2, 'Продажи', 'Outcome', '', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(23, 5, 'Бренды', 'Manufacturers', 'Справочники', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(24, 5, 'Группы товаров', 'GroupList', 'Справочники', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(26, 5, 'Товары', 'ProductList', '', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(27, 5, 'Журнал заказов', 'OrderList', '', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(28, 1, 'Онлайн заказ', 'ShopOrder', 'Онлайн каталог', '', 1, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(30, 1, 'Оприходование  с  производства', 'ProdReceipt', 'Производство', 'Оприходование готовой продукции и полуфабрикатов  с  производства  на  склад.  ', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(31, 1, 'Списание на  производство', 'ProdIssue', 'Производство', 'Передача  на производство  материалов  и комплектующий.', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(32, 2, 'Отчет по производству', 'Prod', 'Производство', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(33, 4, 'Производственные участвки', 'ProdAreaList', '', '', 0, 0);
