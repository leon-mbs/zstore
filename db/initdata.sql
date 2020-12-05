
SET NAMES 'utf8';

 
INSERT INTO `users` (`user_id`, `userlogin`, `userpass`, `createdon`, `email`, `acl`, `disabled`, `options`, `role_id`) VALUES(4, 'admin', '$2y$10$GsjC.thVpQAPMQMO6b4Ma.olbIFr2KMGFz12l5/wnmxI1PEqRDQf.', '2017-01-01', 'admin@admin.admin', 'a:2:{s:9:"aclbranch";N;s:6:"onlymy";N;}', 0, 'a:6:{s:8:"defstore";s:2:"19";s:7:"deffirm";i:0;s:5:"defmf";s:1:"2";s:8:"pagesize";s:2:"15";s:11:"hidesidebar";i:0;s:8:"mainpage";s:15:"\\App\\Pages\\Main";}', 1);

INSERT INTO `roles` (`role_id`, `rolename`, `acl`) VALUES(1, 'admins', 'a:9:{s:7:"aclview";N;s:7:"acledit";N;s:6:"aclexe";N;s:9:"aclcancel";N;s:8:"aclstate";N;s:9:"acldelete";N;s:7:"widgets";N;s:7:"modules";N;s:9:"smartmenu";s:1:"8";}');

UPDATE users set  role_id=(select role_id  from roles  where  rolename='admins' limit 0,1 )  where  userlogin='admin' ;

 
INSERT  INTO `stores` (  `storename`, `description`) VALUES(  'Основной склад', '');
INSERT INTO `mfund` (`mf_id`, `mf_name`, `description`) VALUES(2, 'Касса', 'Основная касса');
INSERT INTO `firms` (  `firm_name`, `details`, `disabled`) VALUES(  'Наша  фирма', '', 0);


INSERT INTO `options` (`optname`, `optvalue`) VALUES('common', 'a:23:{s:9:"qtydigits";s:1:"0";s:8:"amdigits";s:1:"0";s:10:"dateformat";s:5:"d.m.Y";s:11:"partiontype";s:1:"1";s:4:"curr";s:2:"ru";s:6:"price1";s:18:"Розничная";s:6:"price2";s:14:"Оптовая";s:6:"price3";s:0:"";s:6:"price4";s:0:"";s:6:"price5";s:0:"";s:8:"shopname";s:14:"Магазин";s:8:"ts_break";s:2:"60";s:8:"ts_start";s:5:"09:00";s:6:"ts_end";s:5:"18:00";s:11:"autoarticle";i:1;s:6:"useset";i:0;s:10:"usesnumber";i:0;s:10:"usescanner";i:1;s:9:"useimages";i:0;s:9:"usebranch";i:0;s:10:"allowminus";i:1;s:6:"useval";i:0;s:6:"capcha";i:0;}');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('printer', 'a:8:{s:6:"pwidth";s:4:"100%";s:9:"pricetype";s:6:"price1";s:11:"barcodetype";s:5:"EAN13";s:9:"pfontsize";s:2:"16";s:5:"pname";i:1;s:5:"pcode";i:0;s:8:"pbarcode";i:1;s:6:"pprice";i:0;}');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('shop', 'N;');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('modules', 'a:11:{s:6:"ocsite";s:20:"http://local.ostore3";s:9:"ocapiname";s:5:"admin";s:5:"ockey";s:256:"Bf81dB8fY2waVxlhych4fFprGfxF2tULlSlHiwEXZqf45E6HDBoA6XjocGcziRsfCQsRovzzDAvMBImmrlzXqEJcMByQpkfeLYfZBDoYstDVuA0Qvx86YkeXVwQ6I2v8xEXS2ZL6ioH1l8qinySGZdRrO5mgFCFWKhgKxIfkNOYpvzIZdR2MdqkHKSzHGSfoDVmbts8slGNFqYzvkXQSP0VaHcw0fYmBZLo0HEvLb2EiBZ5A8EcGDZWWtndg2wlY";s:13:"occustomer_id";s:1:"8";s:11:"ocpricetype";s:6:"price1";s:6:"wcsite";s:15:"http://local.wp";s:6:"wckeyc";s:43:"ck_a36c9d5d8ef70a34001b6a44bc245a7665ca77e7";s:6:"wckeys";s:43:"cs_12b03012d9db469b45b1fc82e329a3bc995f3e36";s:5:"wcapi";s:2:"v3";s:13:"wccustomer_id";s:1:"8";s:11:"wcpricetype";s:6:"price1";}');

  
INSERT INTO `metadata` VALUES(1, 4, 'Склады', 'StoreList', 'Товары', 0);
INSERT INTO `metadata` VALUES(2, 4, 'Номенклатура', 'ItemList', 'Товары', 0);
INSERT INTO `metadata` VALUES(3, 4, 'Сотрудники', 'EmployeeList', '', 0);
INSERT INTO `metadata` VALUES(4, 4, 'Категории товаров', 'CategoryList', 'Товары', 0);
INSERT INTO `metadata` VALUES(5, 4, 'Контрагенты', 'CustomerList', '', 0);
INSERT INTO `metadata` VALUES(6, 1, 'Приходная накладная', 'GoodsReceipt', 'Закупки', 0);
INSERT INTO `metadata` VALUES(7, 1, 'Расходная накладная', 'GoodsIssue', 'Продажи', 0);
INSERT INTO `metadata` VALUES(8, 3, 'Общий журнал', 'DocList', '', 0);
INSERT INTO `metadata` VALUES(10, 1, 'Гарантийный талон', 'Warranty', 'Продажи', 0);
INSERT INTO `metadata` VALUES(12, 2, 'Движение по складу', 'ItemActivity', 'Склад', 0);
INSERT INTO `metadata` VALUES(13, 2, 'ABC анализ', 'ABC', '', 0);
INSERT INTO `metadata` VALUES(14, 4, 'Услуги, работы', 'ServiceList', '', 0);
INSERT INTO `metadata` VALUES(15, 1, 'Акт выполненных работ', 'ServiceAct', 'Услуги', 0);
INSERT INTO `metadata` VALUES(16, 1, 'Возврат от покупателя', 'ReturnIssue', 'Продажи', 0);
INSERT INTO `metadata` VALUES(18, 3, 'Наряды', 'TaskList', '', 0);
INSERT INTO `metadata` VALUES(19, 1, 'Наряд', 'Task', 'Производство', 0);
INSERT INTO `metadata` VALUES(20, 2, 'Отчет по нарядам', 'EmpTask', 'Производство', 0);
INSERT INTO `metadata` VALUES(21, 2, 'Закупки', 'Income', 'Закупки', 0);
INSERT INTO `metadata` VALUES(22, 2, 'Продажи', 'Outcome', 'Продажи', 0);
INSERT INTO `metadata` VALUES(46, 4, 'Кассы, счета', 'MFList', '', 0);
INSERT INTO `metadata` VALUES(27, 3, 'Заказы клиентов', 'OrderList', 'Продажи', 0);
INSERT INTO `metadata` VALUES(28, 1, 'Заказ', 'Order', 'Продажи', 0);
INSERT INTO `metadata` VALUES(30, 1, 'Оприходование  с  производства', 'ProdReceipt', 'Производство', 0);
INSERT INTO `metadata` VALUES(31, 1, 'Списание на  производство', 'ProdIssue', 'Производство', 0);
INSERT INTO `metadata` VALUES(32, 2, 'Отчет по производству', 'Prod', 'Производство', 0);
INSERT INTO `metadata` VALUES(33, 4, 'Производственные участки', 'ProdAreaList', '', 0);
INSERT INTO `metadata` VALUES(38, 1, 'Заявка  поставщику', 'OrderCust', 'Закупки', 0);
INSERT INTO `metadata` VALUES(35, 3, 'Продажи', 'GIList', 'Продажи', 0);
INSERT INTO `metadata` VALUES(36, 4, 'Основные фонды', 'EqList', '', 0);
INSERT INTO `metadata` VALUES(37, 3, 'Закупки', 'GRList', 'Закупки', 0);
INSERT INTO `metadata` VALUES(39, 3, 'Заявки поставщикам', 'OrderCustList', 'Закупки', 0);
INSERT INTO `metadata` VALUES(40, 2, 'Прайс', 'Price', 'Склад', 0);
INSERT INTO `metadata` VALUES(41, 1, 'Возврат поставщику', 'RetCustIssue', 'Закупки', 0);
INSERT INTO `metadata` VALUES(69, 3, 'Работы, услуги', 'SerList', '', 0);
INSERT INTO `metadata` VALUES(44, 1, 'Перекомплектация ТМЦ', 'TransItem', 'Склад', 0);
INSERT INTO `metadata` VALUES(47, 3, 'Журнал платежей', 'PayList', 'Касса и платежи', 0);
INSERT INTO `metadata` VALUES(48, 2, 'Движение по денежным счетам', 'PayActivity', 'Платежи', 0);
INSERT INTO `metadata` VALUES(64, 1, 'Списание ТМЦ', 'OutcomeItem', 'Склад', 0);
INSERT INTO `metadata` VALUES(50, 1, 'Приходный ордер', 'IncomeMoney', 'Платежи', 0);
INSERT INTO `metadata` VALUES(51, 1, 'Расходный ордер', 'OutcomeMoney', 'Платежи', 0);
INSERT INTO `metadata` VALUES(53, 2, 'Финансовые результаты', 'PayBalance', '', 0);
INSERT INTO `metadata` VALUES(57, 1, 'Инвентаризация', 'Inventory', 'Склад', 0);
INSERT INTO `metadata` VALUES(58, 1, 'Счет входящий', 'InvoiceCust', 'Закупки', 0);
INSERT INTO `metadata` VALUES(59, 1, 'Счет-фактура', 'Invoice', 'Продажи', 0);
INSERT INTO `metadata` VALUES(60, 5, 'Импорт', 'Import', '', 0);
INSERT INTO `metadata` VALUES(61, 3, 'Движение  ТМЦ', 'StockList', 'Склад', 0);
INSERT INTO `metadata` VALUES(62, 1, 'Кассовый чек', 'POSCheck', 'Продажи', 1);
INSERT INTO `metadata` VALUES(63, 2, 'Товары в  пути', 'CustOrder', 'Закупки', 0);
INSERT INTO `metadata` VALUES(65, 1, 'Оприходование ТМЦ', 'IncomeItem', 'Склад', 0);
INSERT INTO `metadata` VALUES(75, 5, 'Экспорт', 'Export', '', 0);
INSERT INTO `metadata` VALUES(67, 5, 'АРМ кассира', 'ARMPos', '', 0);
INSERT INTO `metadata` VALUES(70, 3, 'Расчеты с  контрагентами', 'PayCustList', 'Касса и платежи', 0);
INSERT INTO `metadata` VALUES(71, 3, 'Товары  на  складе', 'ItemList', 'Склад', 0);
INSERT INTO `metadata` VALUES(76, 1, 'Выплата зарплаты', 'OutSalary', 'Платежи', 0);
INSERT INTO `metadata` VALUES(77, 2, 'Отчет по  зарплате', 'SalaryRep', 'Зарплата', 0);
INSERT INTO `metadata` VALUES(78, 2, 'Движение  по  контрагентам', 'CustActivity', 'Платежи', 0);
INSERT INTO `metadata` VALUES(81, 4, 'Договора', 'ContractList', '', 0);
INSERT INTO `metadata` VALUES(82, 1, 'Перемещение товара', 'MoveItem', 'Склад', 0);
INSERT INTO `metadata` VALUES(83, 2, 'Рабочее время', 'Timestat', 'Зарплата', 0);
