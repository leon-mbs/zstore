SET NAMES 'utf8';
   
 
INSERT INTO `users` (`user_id`, `userlogin`, `userpass`, `createdon`, `email`, `acl`, `disabled`, `options`, `role_id`) VALUES(4, 'admin', '$2y$10$GsjC.thVpQAPMQMO6b4Ma.olbIFr2KMGFz12l5/wnmxI1PEqRDQf.', '2017-01-01', 'admin@admin.admin', 'a:2:{s:9:"aclbranch";N;s:6:"onlymy";N;}', 0, 'a:6:{s:8:"defstore";s:2:"19";s:7:"deffirm";i:0;s:5:"defmf";s:1:"2";s:8:"pagesize";s:2:"15";s:11:"hidesidebar";i:0;s:8:"mainpage";s:15:"\\App\\Pages\\Main";}', 1);

INSERT INTO `roles` (`role_id`, `rolename`, `acl`) VALUES(1, 'admins', 'a:9:{s:7:"aclview";N;s:7:"acledit";N;s:6:"aclexe";N;s:9:"aclcancel";N;s:8:"aclstate";N;s:9:"acldelete";N;s:7:"widgets";N;s:7:"modules";N;s:9:"smartmenu";s:1:"8";}');

UPDATE users set  role_id=(select role_id  from roles  where  rolename='admins' limit 0,1 )  where  userlogin='admin' ;

 
INSERT  INTO `stores` (  `storename`, `description`) VALUES(  'Основний склад', '');
INSERT INTO `mfund` (  `mf_name`, `description`) VALUES(  'Каса', 'Основна каса');
INSERT INTO `firms` (  `firm_name`, `details`, `disabled`) VALUES(  'Наша  фiрма', '', 0);
INSERT INTO `customers` ( `customer_name`, `detail`, `email`, `phone`, `status`, `city`, `leadstatus`, `leadsource`, `createdon`) VALUES( 'Фiз. особа', '<detail><code></code><discount></discount><bonus></bonus><type>0</type><fromlead>0</fromlead><jurid></jurid><shopcust_id></shopcust_id><isholding>0</isholding><holding>0</holding><viber></viber><nosubs>1</nosubs><user_id>4</user_id><holding_name><![CDATA[]]></holding_name><address><![CDATA[]]></address><comment><![CDATA[Умовний контрагент якщо треба  когось  вказати.]]></comment></detail>', '', '', 0, '', NULL, NULL, '2021-04-28');


INSERT INTO `options` (`optname`, `optvalue`) VALUES('common', 'a:30:{s:9:"qtydigits";s:1:"0";s:8:"amdigits";s:1:"0";s:10:"dateformat";s:5:"d.m.Y";s:11:"partiontype";s:1:"1";s:4:"curr";s:2:"gr";s:6:"phonel";s:2:"10";s:6:"price1";s:18:"Розничная";s:6:"price2";s:14:"Оптовая";s:6:"price3";s:0:"";s:6:"price4";s:0:"";s:6:"price5";s:0:"";s:8:"defprice";s:2:"10";s:8:"shopname";s:20:"Наша  фирма";s:8:"ts_break";s:2:"60";s:8:"ts_start";s:5:"09:00";s:6:"ts_end";s:5:"18:00";s:11:"checkslogan";s:8:"Тест";s:11:"autoarticle";i:1;s:10:"usesnumber";i:0;s:10:"usescanner";i:0;s:9:"useimages";i:0;s:15:"showactiveusers";i:0;s:10:"usecattree";i:0;s:9:"usebranch";i:0;s:10:"noallowfiz";i:0;s:10:"allowminus";i:1;s:6:"useval";i:0;s:6:"capcha";i:0;s:9:"numberttn";i:0;s:11:"salesources";a:1:{i:1620576897;O:12:"App\\DataItem":2:{s:2:"id";i:1620576897;s:9:"\0*\0fields";a:1:{s:4:"name";s:31:"Основной магазин";}}}}');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('api', 'a:3:{s:3:"exp";N;s:3:"key";N;s:5:"atype";s:1:"3";}');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('printer', 'a:8:{s:6:"pwidth";s:4:"100%";s:9:"pricetype";s:6:"price1";s:11:"barcodetype";s:4:"C128";s:9:"pfontsize";s:2:"16";s:5:"pname";i:1;s:5:"pcode";i:0;s:8:"pbarcode";i:1;s:6:"pprice";i:0;}');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('shop', 'a:8:{s:7:"defcust";s:1:"2";s:12:"defpricetype";s:6:"price1";s:5:"email";s:0:"";s:8:"shopname";s:17:"Наш магаз";s:12:"currencyname";s:6:"грн";s:8:"uselogin";i:0;s:9:"usefilter";i:1;s:11:"usefeedback";i:1;}');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('sms', 'a:7:{s:13:"turbosmstoken";s:0:"";s:12:"smssemytoken";s:0:"";s:12:"smssemydevid";s:0:"";s:11:"flysmslogin";s:0:"";s:10:"flysmspass";s:0:"";s:8:"flysmsan";s:0:"";s:7:"smstype";s:1:"0";}');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('salary', 'a:4:{s:13:\"codebaseincom\";s:3:\"105\";s:10:\"coderesult\";s:3:\"900\";s:4:\"calc\";s:212:\"v200 = v105\r\n//налоги\r\nv220 =  v200 * 0.18\r\nv300 =  v200 * 0.22\r\n//всего  удержано\r\nv600 =v200  - v220- v300\r\n//на руки\r\nv900 =v200 - v600-v850\r\n\r\n\r\n//пример\r\nif(invalid){\r\n   \r\n}  \";s:11:\"codeadvance\";s:3:\"850\";}');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('val', 'a:2:{s:7:\"vallist\";a:2:{i:1642675955;O:12:\"App\\DataItem\":2:{s:2:\"id\";i:1642675955;s:9:\"\0*\0fields\";a:3:{s:4:\"code\";s:3:\"USD\";s:4:\"name\";s:12:\"Доллар\";s:4:\"rate\";s:2:\"28\";}}i:1642676126;O:12:\"App\\DataItem\":2:{s:2:\"id\";i:1642676126;s:9:\"\0*\0fields\";a:3:{s:4:\"code\";s:4:\"EURO\";s:4:\"name\";s:8:\"Евро\";s:4:\"rate\";s:2:\"33\";}}}s:8:\"valprice\";i:0;}');;
  
  
  
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(1, 4, 'Склади', 'StoreList', 'Товари', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(2, 4, 'Номенклатура', 'ItemList', 'Товари', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(3, 4, 'Співробітники', 'EmployeeList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(4, 4, 'Категорії товарів', 'CategoryList', 'Товари', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(5, 4, 'Контрагенти', 'CustomerList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(6, 1, 'Прибуткова накладна', 'GoodsReceipt', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(7, 1, 'Видаткова накладна', 'GoodsIssue', 'Продажі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(8, 3, 'Журнал документiв', 'DocList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(10, 1, 'Гарантійний талон', 'Warranty', 'Продажі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(12, 2, 'Рух по складу', 'ItemActivity', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(13, 2, 'ABC аналіз', 'ABC', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(14, 4, 'Послуги, роботи', 'ServiceList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(15, 1, 'Акт виконаних робіт', 'ServiceAct', 'Послуги', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(16, 1, 'Повернення від покупця', 'ReturnIssue', 'Продажі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(18, 3, 'Наряди', 'TaskList', 'Виробництво', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(19, 1, 'Наряд', 'Task', 'Виробництво', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(20, 2, 'Оплата по виробництву', 'EmpTask', 'Виробництво', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(21, 2, 'Закупівлі', 'Income', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(22, 2, 'Продажі', 'Outcome', 'Продажі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(27, 3, 'Замовлення клієнтів', 'OrderList', 'Продажі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(28, 1, 'Замовлення', 'Order', 'Продажі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(30, 1, 'Оприбуткування з виробництва', 'ProdReceipt', 'Виробництво', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(31, 1, 'Списання на виробництво', 'ProdIssue', 'Виробництво', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(32, 2, 'Звіт по виробництву', 'Prod', 'Виробництво', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(33, 4, 'Виробничі дільниці', 'ProdAreaList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(35, 3, 'Продажі', 'GIList', 'Продажі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(36, 4, 'Обладнання та ОЗ', 'EqList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(37, 3, 'Закупівлі', 'GRList', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(38, 1, 'Заявка постачальнику', 'OrderCust', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(39, 3, 'Заявки постачальникам', 'OrderCustList', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(40, 2, 'Прайс', 'Price', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(41, 1, 'Повернення постачальнику', 'RetCustIssue', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(44, 1, 'Перекомплектація (расфасовка)', 'TransItem', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(46, 4, 'Каси, рахунки', 'MFList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(47, 3, 'Журнал платежів', 'PayList', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(48, 2, 'Рух по грошовим рахунках', 'PayActivity', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(50, 1, 'Прибутковий ордер', 'IncomeMoney', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(51, 1, 'Видатковий ордер', 'OutcomeMoney', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(53, 2, 'Фінансові  результати', 'PayBalance', '', 0);
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
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(71, 3, 'Товари на складі', 'ItemList', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(75, 5, 'Експорт', 'Export', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(76, 1, 'Виплата зарплати', 'OutSalary', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(77, 2, 'Звіт по  зарплаті', 'SalaryRep', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(78, 2, 'Рух по  контрагентах', 'CustActivity', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(79, 4, 'Контракти', 'ContractList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(80, 1, 'Перемiщення ТМЦ', 'MoveItem', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(81, 2, 'Робочий час', 'Timestat', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(84, 1, 'Товарно-транспортна накладна', 'TTN', 'Продажі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(85, 2, 'Нелiквiднi товари', 'NoLiq', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(86, 3, 'Розрахунки с  поставщиками', 'PaySelList', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(87, 3, 'Розрахунки с  покупателями', 'PayBayList', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(88, 1, 'Перемiщення грошей', 'MoveMoney', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(89, 1, 'Замовленя кафе', 'OrderFood', 'Кафе', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(91, 5, 'АРМ касира (кафе)', 'ARMFood', 'Кафе', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(95, 3, 'Журнал доставок', 'DeliveryList', 'Кафе', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(96, 5, 'АРМ кухнi (бару)', 'ArmProdFood', 'Кафе', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(97, 3, 'Прибутки та  видатки', 'IOState', '', 0);
INSERT INTO `metadata` (  `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  2, 'Замовленi товари', 'ItemOrder', 'Закупки', 0);
INSERT INTO `metadata` (  `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 5, 'Знижки та  акції', 'Discounts', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  1, 'Нарахування зарплати', 'CalcSalary', 'Каса та платежі', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  4, 'Нарахування та утримання', 'SalaryTypeList', '', 0);
INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Нарахування та утримання', 'SalTypeRep', 'Зарплата', 0);
INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Рух  по особовому рахунку', 'EmpAccRep', 'Зарплата', 0);
 INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Вир. процеси', 'ProdProcList', 'Виробництво', 0);
 INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Вир. етапи', 'ProdStageList', 'Виробництво', 0);
 INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Перемiщення  партiй ТМЦ', 'MovePart', 'Склад', 0);
INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Повернення  покупцiв', 'Returnselled', 'Продажi', 0);
INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Повернення  постачальникам', 'Returnbayed', 'Закупки', 0);
INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  1, 'Надані послуги', 'IncomeService', 'Послуги', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Стан  складiв', 'StoreItems', 'Склад', 0);
  


INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(2, 105, 'Основна  зарплата', 'осн', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(3, 200, 'Всього нараховано', 'вс. нар', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(4, 600, 'Всього  утримано', 'вс. ут', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(5, 900, 'До видачi', 'До видачi', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(6, 850, 'Аванс', 'Аванс', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(7, 220, 'НДФО', 'НДФО', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(8, 300, 'ЕСВ', 'ЕСВ', 0);
