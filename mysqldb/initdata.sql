SET NAMES 'utf8';
   
 
INSERT INTO `users` (`user_id`, `userlogin`, `userpass`, `createdon`, `email`, `acl`, `disabled`, `options`, `role_id`) VALUES(4, 'admin', '$2y$10$GsjC.thVpQAPMQMO6b4Ma.olbIFr2KMGFz12l5/wnmxI1PEqRDQf.', '2017-01-01', 'admin@admin.admin', 'a:2:{s:9:"aclbranch";N;s:6:"onlymy";N;}', 0, 'a:6:{s:8:"defstore";s:2:"19";s:7:"deffirm";i:0;s:5:"defmf";s:1:"2";s:8:"pagesize";s:2:"15";s:11:"hidesidebar";i:0;s:8:"mainpage";s:15:"\\App\\Pages\\Main";}', 1);

INSERT INTO `roles` (`role_id`, `rolename`, `acl`) VALUES(1, 'admins', 'a:9:{s:7:"aclview";N;s:7:"acledit";N;s:6:"aclexe";N;s:9:"aclcancel";N;s:8:"aclstate";N;s:9:"acldelete";N;s:7:"widgets";N;s:7:"modules";N;s:9:"smartmenu";s:1:"8";}');

UPDATE users set  role_id=(select role_id  from roles  where  rolename='admins' limit 0,1 )  where  userlogin='admin' ;

 
INSERT INTO `stores` (  `storename`, `description`) VALUES(  'Основний склад', '');
INSERT INTO `mfund` (`mf_id`, `mf_name`, `description`, `branch_id`, `detail`) VALUES(2, 'Каса', '', NULL, '<detail><beznal>0</beznal><btran></btran><bank><![CDATA[]]></bank><bankacc><![CDATA[]]></bankacc></detail>');

INSERT INTO `firms` (  `firm_name`, `details`, `disabled`) VALUES(  'Наша фiрма', '', 0);
INSERT INTO `customers` ( `customer_name`, `detail`, `email`, `phone`, `status`, `city`, `leadstatus`, `leadsource`, `createdon`) VALUES( 'Фiз. особа', '<detail><code></code><discount></discount><bonus></bonus><type>0</type><fromlead>0</fromlead><jurid></jurid><shopcust_id></shopcust_id><isholding>0</isholding><holding>0</holding><viber></viber><nosubs>1</nosubs><user_id>4</user_id><holding_name><![CDATA[]]></holding_name><address><![CDATA[]]></address><comment><![CDATA[Умовний контрагент (якщо треба когось зазначити)]]></comment></detail>', '', '', 0, '', NULL, NULL, '2021-04-28');


  
  
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 4, 'Склади', 'StoreList', 'Товари', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 4, 'Номенклатура', 'ItemList', 'Товари', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 4, 'Співробітники', 'EmployeeList', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 4, 'Категорії товарів', 'CategoryList', 'Товари', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 4, 'Контрагенти', 'CustomerList', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Прибуткова накладна', 'GoodsReceipt', 'Закупівлі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Видаткова накладна', 'GoodsIssue', 'Продажі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Журнал документiв', 'DocList', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Гарантійний талон', 'Warranty', 'Продажі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Рух по складу', 'ItemActivity', 'Склад', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'ABC аналіз', 'ABC', 'Аналітика', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 4, 'Послуги, роботи', 'ServiceList', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Акт виконаних робіт', 'ServiceAct', 'Послуги', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Повернення від покупця', 'ReturnIssue', 'Продажі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Наряди', 'TaskList', 'Виробництво', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Наряд', 'Task', 'Виробництво', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Оплата по виробництву', 'EmpTask', 'Виробництво', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Закупівлі', 'Income', 'Закупівлі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Продажі', 'Outcome', 'Продажі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Замовлення клієнтів', 'OrderList', 'Продажі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Замовлення', 'Order', 'Продажі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Оприбуткування з виробництва', 'ProdReceipt', 'Виробництво', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Списання на виробництво', 'ProdIssue', 'Виробництво', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Звіт по виробництву', 'Prod', 'Виробництво', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 4, 'Виробничі дільниці', 'ProdAreaList', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Продажі', 'GIList', 'Продажі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 4, 'Обладнання та ОЗ', 'EqList', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Закупівлі', 'GRList', 'Закупівлі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Заявка постачальнику', 'OrderCust', 'Закупівлі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Заявки постачальникам', 'OrderCustList', 'Закупівлі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Прайс', 'Price', 'Склад', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Повернення постачальнику', 'RetCustIssue', 'Закупівлі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Перекомплектація (расфасовка)', 'TransItem', 'Склад', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 4, 'Каси, рахунки', 'MFList', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Журнал платежів', 'PayList', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Рух по грошовим рахункам', 'PayActivity', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Прибутковий ордер', 'IncomeMoney', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Видатковий ордер', 'OutcomeMoney', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Фінансові результати', 'PayBalance', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Інвентаризація', 'Inventory', 'Склад', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Рахунок, вхідний', 'InvoiceCust', 'Закупівлі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Рахунок-фактура', 'Invoice', 'Продажі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 5, 'Імпорт', 'Import', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Рух ТМЦ', 'StockList', 'Склад', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Касовий чек', 'POSCheck', 'Продажі', 1);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Товари в дорозі', 'CustOrder', 'Закупівлі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Списання ТМЦ', 'OutcomeItem', 'Склад', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Оприбуткування ТМЦ', 'IncomeItem', 'Склад', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 5, 'АРМ касира', 'ARMPos', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Роботи, послуги', 'SerList', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Товари на складі', 'ItemList', 'Склад', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 5, 'Експорт', 'Export', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Виплата зарплати', 'OutSalary', 'Зарплата', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Звіт по зарплаті', 'SalaryRep', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 4, 'Договори', 'ContractList', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Перемiщення ТМЦ', 'MoveItem', 'Склад', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Робочий час', 'Timestat', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Товарно-транспортна накладна', 'TTN', 'Продажі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Нелiквiднi товари', 'NoLiq', 'Склад', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Розрахунки з постачальниками', 'PaySelList', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Розрахунки з покупцями', 'PayBayList', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Перемiщення грошей', 'MoveMoney', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Замовленя кафе', 'OrderFood', 'Кафе', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 5, 'АРМ касира (кафе)', 'ARMFood', 'Кафе', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Журнал доставок', 'DeliveryList', 'Кафе', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 5, 'АРМ кухнi (бару)', 'ArmProdFood', 'Кафе', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Прибутки та видатки', 'IOState', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Замовленi товари', 'ItemOrder', 'Продажі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 5, 'Знижки та акції', 'Discounts', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Нарахування зарплати', 'CalcSalary', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 4, 'Нарахування та утримання', 'SalaryTypeList', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Вир. процеси', 'ProdProcList', 'Виробництво', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Вир. етапи', 'ProdStageList', 'Виробництво', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Перемiщення партiй ТМЦ', 'MovePart', 'Склад', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Повернення покупцiв', 'Returnselled', 'Продажі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Повернення постачальникам', 'Returnbayed', 'Закупівлі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Надані послуги', 'IncomeService', 'Послуги', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Стан складiв', 'StoreItems', 'Склад', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Товари у постачальників', 'CustItems', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Акт звірки', 'CompareAct', 'Контрагенти', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Зарезервовані товари', 'Reserved', 'Склад', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'OLAP аналіз', 'OLAP', 'Аналітика', 0);
  


INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(2, 105, 'Основна зарплата', 'осн', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(3, 200, 'Всього нараховано', 'вс. нар', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(4, 600, 'Всього утримано', 'вс. утр', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(5, 900, 'До видачi', 'До видачi', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(6, 850, 'Аванс', 'Аванс', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(7, 220, 'НДФО', 'НДФО', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(8, 300, 'ЕСВ', 'ЕСВ', 0);


INSERT INTO `options` (`optname`, `optvalue`) VALUES('api', 'a:3:{s:3:\"exp\";s:0:\"\";s:3:\"key\";s:4:\"test\";s:5:\"atype\";s:1:\"1\";}');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('common', 'YTozMjp7czo5OiJxdHlkaWdpdHMiO3M6MToiMCI7czo4OiJhbWRpZ2l0cyI7czoxOiIwIjtzOjEwOiJkYXRlZm9ybWF0IjtzOjU6ImQubS5ZIjtzOjExOiJwYXJ0aW9udHlwZSI7czoxOiIxIjtzOjY6InBob25lbCI7czoyOiIxMCI7czo2OiJwcmljZTEiO3M6MTg6ItCg0L7Qt9C00YDRltCx0L3QsCI7czo2OiJwcmljZTIiO3M6MTI6ItCe0L/RgtC+0LLQsCI7czo2OiJwcmljZTMiO3M6MDoiIjtzOjY6InByaWNlNCI7czowOiIiO3M6NjoicHJpY2U1IjtzOjA6IiI7czo4OiJkZWZwcmljZSI7czowOiIiO3M6ODoic2hvcG5hbWUiO3M6MDoiIjtzOjg6InRzX2JyZWFrIjtzOjI6IjYwIjtzOjg6InRzX3N0YXJ0IjtzOjU6IjA5OjAwIjtzOjY6InRzX2VuZCI7czo1OiIxODowMCI7czoxMToiY2hlY2tzbG9nYW4iO3M6MDoiIjtzOjExOiJhdXRvYXJ0aWNsZSI7aToxO3M6MTA6InVzZXNudW1iZXIiO2k6MDtzOjEwOiJ1c2VzY2FubmVyIjtpOjA7czoxNjoidXNlbW9iaWxlc2Nhbm5lciI7aTowO3M6OToidXNlaW1hZ2VzIjtpOjA7czoxNDoicHJpbnRvdXRxcmNvZGUiO2k6MDtzOjE0OiJub2NoZWNrYXJ0aWNsZSI7aTowO3M6MTU6InNob3dhY3RpdmV1c2VycyI7aTowO3M6ODoic2hvd2NoYXQiO2k6MDtzOjEwOiJ1c2VjYXR0cmVlIjtpOjA7czo5OiJ1c2VicmFuY2giO2k6MDtzOjEwOiJub2FsbG93Zml6IjtpOjA7czoxMDoiYWxsb3dtaW51cyI7aTowO3M6NjoidXNldmFsIjtpOjA7czo2OiJjYXBjaGEiO2k6MDtzOjk6Im51bWJlcnR0biI7aTowO30=');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('disc', 'a:4:{s:8:\"firstbay\";s:2:\"11\";s:6:\"bonus1\";s:0:\"\";s:6:\"level2\";s:0:\"\";s:6:\"bonus2\";s:0:\"\";}');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('discount', 'a:6:{s:8:\"firstbay\";s:2:\"11\";s:6:\"bonus1\";s:3:\"1.1\";s:6:\"level2\";s:0:\"\";s:6:\"bonus2\";s:0:\"\";s:6:\"summa1\";s:3:\"100\";s:6:\"summa2\";s:0:\"\";}');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('food', 'a:5:{s:8:\"worktype\";s:1:\"2\";s:9:\"pricetype\";s:6:\"price1\";s:8:\"delivery\";i:1;s:6:\"tables\";i:1;s:4:\"pack\";i:1;}');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('printer', 'a:15:{s:7:\"pheight\";s:0:\"\";s:8:\"pa4width\";s:0:\"\";s:6:\"pwidth\";s:4:\"100%\";s:9:\"pdocwidth\";s:4:\"70mm\";s:8:\"pmaxname\";s:1:\"7\";s:9:\"pricetype\";s:6:\"price1\";s:11:\"barcodetype\";s:4:\"C128\";s:9:\"pfontsize\";s:2:\"28\";s:12:\"pdocfontsize\";s:2:\"16\";s:5:\"pname\";i:1;s:5:\"pcode\";i:1;s:8:\"pbarcode\";i:1;s:7:\"pqrcode\";i:1;s:6:\"pprice\";i:1;s:6:\"pcolor\";i:0;}');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('salary', 'YTo1OntzOjEzOiJjb2RlYmFzZWluY29tIjtzOjM6IjEwNSI7czoxMDoiY29kZXJlc3VsdCI7czozOiI5MDAiO3M6NDoiY2FsYyI7czoyMTk6InYyMDAgPSB2MTA1DQovL9C');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('shop', 'YToyMDp7czo3OiJkZWZjdXN0IjtzOjE6IjEiO3M6MTE6ImRlZmN1c3RuYW1lIjtzOjI5OiLQm9C10L7QvdC40LQg0JzQsNGA0YLRi9C90Y7QuiI7czo5OiJkZWZicmFuY2giO047czo5OiJvcmRlcnR5cGUiO3M6MToiMCI7czoxMjoiZGVmcHJpY2V0eXBlIjtzOjY6InByaWNlMSI7czo1OiJlbWFpbCI7czowOiIiO3M6ODoic2hvcG5hbWUiO3M6MTc6ItCd0LDRiCDQvNCw0LPQsNC3IjtzOjEyOiJjdXJyZW5jeW5hbWUiO3M6Njoi0YDRg9CxIjtzOjg6InVzZWxvZ2luIjtpOjA7czo5OiJ1c2VmaWx0ZXIiO2k6MDtzOjEzOiJjcmVhdGVuZXdjdXN0IjtpOjA7czoxMToidXNlZmVlZGJhY2siO2k6MDtzOjExOiJ1c2VtYWlucGFnZSI7aTowO3M6NzoiYWJvdXR1cyI7czoxNjoiUEhBK1BHSnlQand2Y0Q0PSI7czo3OiJjb250YWN0IjtzOjA6IiI7czo4OiJkZWxpdmVyeSI7czowOiIiO3M6NDoibmV3cyI7czowOiIiO3M6NToicGFnZXMiO2E6Mjp7czo0OiJuZXdzIjtPOjEyOiJBcHBcRGF0YUl0ZW0iOjI6e3M6MjoiaWQiO047czo5OiIAKgBmaWVsZHMiO2E6NDp7czo0OiJsaW5rIjtzOjQ6Im5ld3MiO3M6NToidGl0bGUiO3M6MTE6Imtra3JycnJycnJyIjtzOjU6Im9yZGVyIjtzOjE6IjIiO3M6NDoidGV4dCI7czoyNDoiUEhBK1pXVmxaV1ZsWldWbFBDOXdQZz09Ijt9fXM6ODoiYWJvdXRfdXMiO086MTI6IkFwcFxEYXRhSXRlbSI6Mjp7czoyOiJpZCI7TjtzOjk6IgAqAGZpZWxkcyI7YTo0OntzOjQ6ImxpbmsiO3M6ODoiYWJvdXRfdXMiO3M6NToidGl0bGUiO3M6OToi0J4g0L3QsNGBIjtzOjU6Im9yZGVyIjtzOjE6IjMiO3M6NDoidGV4dCI7czozMjoiUEhBK1BHSSswSjRnMEwzUXNOR0JQQzlpUGp3dmNEND0iO319fXM6NToicGhvbmUiO3M6MDoiIjtzOjEwOiJzYWxlc291cmNlIjtzOjE6IjAiO30=');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('val', 'a:2:{s:7:\"vallist\";a:2:{i:1642675955;O:12:\"App\\DataItem\":2:{s:2:\"id\";i:1642675955;s:9:\"\0*\0fields\";a:3:{s:4:\"code\";s:3:\"USD\";s:4:\"name\";s:10:\"Долар\";s:4:\"rate\";s:2:\"28\";}}i:1642676126;O:12:\"App\\DataItem\":2:{s:2:\"id\";i:1642676126;s:9:\"\0*\0fields\";a:3:{s:4:\"code\";s:4:\"EURO\";s:4:\"name\";s:8:\"Євро\";s:4:\"rate\";s:2:\"33\";}}}s:8:\"valprice\";i:0;}');
INSERT INTO `options` (`optname`, `optvalue`) values('version','6.8.0');  
