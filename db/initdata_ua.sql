SET NAMES 'utf8';


 
INSERT INTO `users` (`user_id`, `userlogin`, `userpass`, `createdon`, `email`, `acl`, `disabled`, `options`) VALUES(4, 'admin', '$2y$10$GsjC.thVpQAPMQMO6b4Ma.olbIFr2KMGFz12l5/wnmxI1PEqRDQf.', '2017-01-01', 'admin@admin.admin', 'a:8:{s:7:"acltype";i:0;s:6:"onlymy";i:0;s:7:"aclview";N;s:7:"acledit";N;s:6:"aclexe";N;s:9:"aclbranch";N;s:7:"widgets";N;s:7:"modules";N;}', 0, 'a:4:{s:9:"smartmenu";s:3:"7,8";s:8:"defstore";s:2:"19";s:5:"defmf";s:1:"2";s:8:"pagesize";s:2:"15";}');
INSERT INTO `roles` (  `rolename`, `acl`) VALUES ( 'admins', NULL);
UPDATE users set  role_id=(select role_id  from roles  where  rolename='admins' limit 0,1 )  where  userlogin='admin';

 
INSERT INTO `stores` (`store_id`, `storename`, `description`, `branch_id`) VALUES (28, 'Основний склад', '', 0);
INSERT INTO `mfund` (`mf_id`, `mf_name`, `description`, `branch_id`) VALUES (2, 'Каса', 'Основна каса', 0);
INSERT INTO `firms` (  `firm_name`, `details`, `disabled`) VALUES(  '????  ?i???', '', 0);

  
INSERT INTO `options` (`optname`, `optvalue`) VALUES('common', 'a:23:{s:9:"qtydigits";s:1:"0";s:8:"amdigits";s:1:"0";s:10:"dateformat";s:5:"d.m.Y";s:11:"partiontype";s:1:"1";s:4:"curr";s:2:"ru";s:6:"price1";s:18:"���������";s:6:"price2";s:14:"�������";s:6:"price3";s:0:"";s:6:"price4";s:0:"";s:6:"price5";s:0:"";s:8:"shopname";s:14:"�������";s:8:"ts_break";s:2:"60";s:8:"ts_start";s:5:"09:00";s:6:"ts_end";s:5:"18:00";s:11:"autoarticle";i:1;s:6:"useset";i:0;s:10:"usesnumber";i:0;s:10:"usescanner";i:1;s:9:"useimages";i:0;s:9:"usebranch";i:0;s:10:"allowminus";i:1;s:6:"useval";i:0;s:6:"capcha";i:0;}');

INSERT INTO `options` (`optname`, `optvalue`) VALUES('shop', 'N;');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('printer', 'a:7:{s:6:"pwidth";s:0:"";s:9:"pricetype";s:6:"price1";s:11:"barcodetype";s:5:"EAN13";s:5:"pname";i:1;s:5:"pcode";i:0;s:8:"pbarcode";i:1;s:6:"pprice";i:0;}');

INSERT INTO `options` (`optname`, `optvalue`) VALUES('modules', 'a:11:{s:6:"ocsite";s:20:"http://local.ostore3";s:9:"ocapiname";s:5:"admin";s:5:"ockey";s:256:"Bf81dB8fY2waVxlhych4fFprGfxF2tULlSlHiwEXZqf45E6HDBoA6XjocGcziRsfCQsRovzzDAvMBImmrlzXqEJcMByQpkfeLYfZBDoYstDVuA0Qvx86YkeXVwQ6I2v8xEXS2ZL6ioH1l8qinySGZdRrO5mgFCFWKhgKxIfkNOYpvzIZdR2MdqkHKSzHGSfoDVmbts8slGNFqYzvkXQSP0VaHcw0fYmBZLo0HEvLb2EiBZ5A8EcGDZWWtndg2wlY";s:13:"occustomer_id";s:1:"8";s:11:"ocpricetype";s:6:"price1";s:6:"wcsite";s:15:"http://local.wp";s:6:"wckeyc";s:43:"ck_a36c9d5d8ef70a34001b6a44bc245a7665ca77e7";s:6:"wckeys";s:43:"cs_12b03012d9db469b45b1fc82e329a3bc995f3e36";s:5:"wcapi";s:2:"v3";s:13:"wccustomer_id";s:1:"8";s:11:"wcpricetype";s:6:"price1";}');
  
  
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(1, 4, 'Склади', 'StoreList', 'Товари', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(2, 4, 'Номенклатура', 'ItemList', 'Товари', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(3, 4, 'Співробітники', 'EmployeeList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(4, 4, 'Категорії товарів', 'CategoryList', 'Товари', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(5, 4, 'Контрагенти', 'CustomerList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(6, 1, 'Прибуткова накладна', 'GoodsReceipt', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(7, 1, 'Видаткова накладна', 'GoodsIssue', 'Продажі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(8, 3, 'Загальний журнал', 'DocList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(10, 1, 'Гарантійний талон', 'Warranty', 'Продажі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(12, 2, 'Рух по складу', 'ItemActivity', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(13, 2, 'ABC аналіз', 'ABC', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(14, 4, 'Послуги, роботи', 'ServiceList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(15, 1, 'Акт виконаних робіт', 'ServiceAct', 'Послуги', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(16, 1, 'Повернення від покупця', 'ReturnIssue', 'Продажі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(18, 3, 'Наряди', 'TaskList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(19, 1, 'Наряд', 'Task', 'Виробництво', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(20, 2, 'Оплата за нарядами', 'EmpTask', 'Виробництво', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(21, 2, 'Закупівлі', 'Income', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(22, 2, 'Продажі', 'Outcome', 'Продажі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(27, 3, 'Замовлення клієнтів', 'OrderList', 'Продажі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(28, 1, 'Замовлення', 'Order', 'Продажі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(30, 1, 'Оприбуткування з виробництва', 'ProdReceipt', 'Виробництво', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(31, 1, 'Списання на виробництво', 'ProdIssue', 'Виробництво', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(32, 2, 'Звіт по виробництву', 'Prod', 'Виробництво', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(33, 4, 'Виробничі дільниці', 'ProdAreaList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(35, 3, 'Продажі', 'GIList', 'Продажі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(36, 4, 'Основні фонди', 'EqList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(37, 3, 'Закупівлі', 'GRList', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(38, 1, 'Заявка постачальнику', 'OrderCust', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(39, 3, 'Заявки постачальникам', 'OrderCustList', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(40, 2, 'Прайс', 'Price', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(41, 1, 'Повернення постачальнику', 'RetCustIssue', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(44, 1, 'Перекомплектація ТМЦ', 'TransItem', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(46, 4, 'Каси', 'MFList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(47, 3, 'Журнал платежів', 'PayList', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(48, 2, 'Рух по грошовим рахунках', 'PayActivity', 'Платежі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(50, 1, 'Прибутковий ордер', 'IncomeMoney', 'Платежі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(51, 1, 'Видатковий ордер', 'OutcomeMoney', 'Платежі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(53, 2, 'Платіжний баланс', 'PayBalance', 'Платежі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(57, 1, 'Інвентаризація', 'Inventory', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(58, 1, 'Рахунок, вхідний', 'InvoiceCust', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(59, 1, 'Рахунок-фактура', 'Invoice', 'Продажі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(60, 5, 'Імпорт', 'Import', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(61, 3, 'Рух ТМЦ', 'StockList', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(62, 1, 'Касовий чек', 'POSCheck', 'Продажі', 1);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(63, 2, 'Товари в дорозі', 'CustOrder', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(64, 1, 'Списання ТМЦ', 'OutcomeItem', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(65, 1, 'Оприбуткування ТМЦ', 'IncomeItem', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(67, 5, 'АРМ касира', 'ARMPos', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(69, 3, 'Роботи, послуги', 'SerList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(70, 3, 'Розрахунки з контрагентами', 'PayCustList', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(71, 3, 'Товари на складі', 'ItemList', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(75, 5, 'Експорт', 'Export', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(76, 1, 'Виплата зарплати', 'OutSalary', 'Платежі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(77, 2, 'Звіт по  зарплаті', 'SalaryRep', 'Платежі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  2, 'Рух по  контрагентах', 'CustActivity', 'Платежі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  4, 'Контракти', 'ContractList', '', 0); 
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  1, 'Перемiщення ТМЦ', 'MoveItem', 'Склад', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  2, 'Робочий час', 'Timestat', '', 0);

