
SET NAMES 'utf8';

 
INSERT INTO `users` (`user_id`, `userlogin`, `userpass`, `createdon`, `email`, `acl`, `disabled`, `options`, `role_id`) VALUES(4, 'admin', '$2y$10$GsjC.thVpQAPMQMO6b4Ma.olbIFr2KMGFz12l5/wnmxI1PEqRDQf.', '2017-01-01', 'admin@admin.admin', 'a:2:{s:9:"aclbranch";N;s:6:"onlymy";N;}', 0, 'a:6:{s:8:"defstore";s:2:"19";s:7:"deffirm";i:0;s:5:"defmf";s:1:"2";s:8:"pagesize";s:2:"15";s:11:"hidesidebar";i:0;s:8:"mainpage";s:15:"\\App\\Pages\\Main";}', 1);

INSERT INTO `roles` (`role_id`, `rolename`, `acl`) VALUES(1, 'admins', 'a:9:{s:7:"aclview";N;s:7:"acledit";N;s:6:"aclexe";N;s:9:"aclcancel";N;s:8:"aclstate";N;s:9:"acldelete";N;s:7:"widgets";N;s:7:"modules";N;s:9:"smartmenu";s:3:"8,2";}');

UPDATE users set  role_id=(select role_id  from roles  where  rolename='admins' limit 0,1 )  where  userlogin='admin' ;

 
INSERT INTO `stores` (  `storename`, `description`) VALUES(  'Основной склад', '');
INSERT INTO `mfund` ( `mf_name`, `description`) VALUES( 'Касса', 'Основная касса');
INSERT INTO `firms` (  `firm_name`, `details`, `disabled`) VALUES(  'Наша  фирма', '', 0);
INSERT INTO `customers` ( `customer_name`, `detail`, `email`, `phone`, `status`, `city`, `leadstatus`, `leadsource`, `createdon`) VALUES( 'Физ. лицо', '<detail><code></code><discount></discount><bonus></bonus><type>0</type><fromlead>0</fromlead><jurid></jurid><shopcust_id></shopcust_id><isholding>0</isholding><holding>0</holding><viber></viber><nosubs>1</nosubs><user_id>4</user_id><holding_name><![CDATA[]]></holding_name><address><![CDATA[]]></address><comment><![CDATA[Условный контрагент если  надо  кого то  указать.]]></comment></detail>', '', '', 0, '', NULL, NULL, '2021-04-28');


INSERT INTO `options` (`optname`, `optvalue`) VALUES('common', 'a:30:{s:9:"qtydigits";s:1:"0";s:8:"amdigits";s:1:"0";s:10:"dateformat";s:5:"d.m.Y";s:11:"partiontype";s:1:"1";s:4:"curr";s:2:"gr";s:6:"phonel";s:2:"10";s:6:"price1";s:18:"Розничная";s:6:"price2";s:14:"Оптовая";s:6:"price3";s:0:"";s:6:"price4";s:0:"";s:6:"price5";s:0:"";s:8:"defprice";s:2:"10";s:8:"shopname";s:20:"Наша  фирма";s:8:"ts_break";s:2:"60";s:8:"ts_start";s:5:"09:00";s:6:"ts_end";s:5:"18:00";s:11:"checkslogan";s:8:"Тест";s:11:"autoarticle";i:1;s:10:"usesnumber";i:0;s:10:"usescanner";i:0;s:9:"useimages";i:0;s:15:"showactiveusers";i:0;s:10:"usecattree";i:0;s:9:"usebranch";i:0;s:10:"noallowfiz";i:0;s:10:"allowminus";i:1;s:6:"useval";i:0;s:6:"capcha";i:0;s:9:"numberttn";i:0;s:11:"salesources";a:1:{i:1620576897;O:12:"App\\DataItem":2:{s:2:"id";i:1620576897;s:9:"\0*\0fields";a:1:{s:4:"name";s:31:"Основной магазин";}}}}');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('api', 'a:3:{s:3:"exp";N;s:3:"key";N;s:5:"atype";s:1:"3";}');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('printer', 'a:8:{s:6:"pwidth";s:4:"100%";s:9:"pricetype";s:6:"price1";s:11:"barcodetype";s:4:"C128";s:9:"pfontsize";s:2:"16";s:5:"pname";i:1;s:5:"pcode";i:0;s:8:"pbarcode";i:1;s:6:"pprice";i:0;}');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('shop', 'a:8:{s:7:"defcust";s:1:"2";s:12:"defpricetype";s:6:"price1";s:5:"email";s:0:"";s:8:"shopname";s:17:"Наш магаз";s:12:"currencyname";s:6:"грн";s:8:"uselogin";i:0;s:9:"usefilter";i:1;s:11:"usefeedback";i:1;}');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('sms', 'a:7:{s:13:"turbosmstoken";s:0:"";s:12:"smssemytoken";s:0:"";s:12:"smssemydevid";s:0:"";s:11:"flysmslogin";s:0:"";s:10:"flysmspass";s:0:"";s:8:"flysmsan";s:0:"";s:7:"smstype";s:1:"0";}');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('salary', 'a:4:{s:13:\"codebaseincom\";s:3:\"105\";s:10:\"coderesult\";s:3:\"900\";s:4:\"calc\";s:212:\"v200 = v105\r\n//налоги\r\nv220 =  v200 * 0.18\r\nv300 =  v200 * 0.22\r\n//всего  удержано\r\nv600 =v200  - v220- v300\r\n//на руки\r\nv900 =v200 - v600-v850\r\n\r\n\r\n//пример\r\nif(invalid){\r\n   \r\n}  \";s:11:\"codeadvance\";s:3:\"850\";}');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('val', 'a:2:{s:7:\"vallist\";a:2:{i:1642675955;O:12:\"App\\DataItem\":2:{s:2:\"id\";i:1642675955;s:9:\"\0*\0fields\";a:3:{s:4:\"code\";s:3:\"USD\";s:4:\"name\";s:12:\"Доллар\";s:4:\"rate\";s:2:\"28\";}}i:1642676126;O:12:\"App\\DataItem\":2:{s:2:\"id\";i:1642676126;s:9:\"\0*\0fields\";a:3:{s:4:\"code\";s:4:\"EURO\";s:4:\"name\";s:8:\"Евро\";s:4:\"rate\";s:2:\"33\";}}}s:8:\"valprice\";i:0;}');;

  
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(1, 4, 'Склады', 'StoreList', 'ТМЦ', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(2, 4, 'Номенклатура', 'ItemList', 'ТМЦ', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(3, 4, 'Сотрудники', 'EmployeeList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(4, 4, 'Категории ', 'CategoryList', 'ТМЦ', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(5, 4, 'Контрагенты', 'CustomerList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(6, 1, 'Приходная накладная', 'GoodsReceipt', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(7, 1, 'Расходная накладная', 'GoodsIssue', 'Продажи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(8, 3, 'Журнал документов', 'DocList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(10, 1, 'Гарантийный талон', 'Warranty', 'Продажи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(12, 2, 'Движение по складу', 'ItemActivity', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(13, 2, 'ABC анализ', 'ABC', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(14, 4, 'Услуги, работы', 'ServiceList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(15, 1, 'Заказ (услуги)', 'ServiceAct', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(16, 1, 'Возврат от покупателя', 'ReturnIssue', 'Продажи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(18, 3, 'Наряды', 'TaskList', 'Производство', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(19, 1, 'Наряд', 'Task', 'Производство', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(20, 2, 'Оплата по производству', 'EmpTask', 'Производство', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(21, 2, 'Закупки', 'Income', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(22, 2, 'Продажи', 'Outcome', 'Продажи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(46, 4, 'Кассы', 'MFList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(27, 3, 'Заказы клиентов', 'OrderList', 'Продажи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(28, 1, 'Заказ', 'Order', 'Продажи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(30, 1, 'Оприходование  с  производства', 'ProdReceipt', 'Производство', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(31, 1, 'Списание на  производство', 'ProdIssue', 'Производство', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(32, 2, 'Отчет по производству', 'Prod', 'Производство', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(33, 4, 'Производственные участки', 'ProdAreaList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(38, 1, 'Заявка  поставщику', 'OrderCust', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(35, 3, 'Продажи', 'GIList', 'Продажи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(36, 4, 'Оборудование и ОС', 'EqList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(37, 3, 'Закупки', 'GRList', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(39, 3, 'Заявки поставщикам', 'OrderCustList', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(40, 2, 'Прайс', 'Price', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(41, 1, 'Возврат поставщику', 'RetCustIssue', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(69, 3, 'Работы, услуги', 'SerList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(44, 1, 'Перекомплектация ТМЦ', 'TransItem', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(47, 3, 'Журнал платежей', 'PayList', 'Касса и платежи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(48, 2, 'Движение по денежным счетам', 'PayActivity', 'Платежи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(64, 1, 'Списание ТМЦ', 'OutcomeItem', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(50, 1, 'Приходный ордер', 'IncomeMoney', 'Касса и платежи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(51, 1, 'Расходный ордер', 'OutcomeMoney', 'Касса и платежи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(53, 2, 'Финансовые результаты', 'PayBalance', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(57, 1, 'Инвентаризация', 'Inventory', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(58, 1, 'Счет входящий', 'InvoiceCust', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(59, 1, 'Счет-фактура', 'Invoice', 'Продажи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(60, 5, 'Импорт', 'Import', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(61, 3, 'Движение  ТМЦ', 'StockList', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(62, 1, 'Кассовый чек', 'POSCheck', 'Продажи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(63, 2, 'Товары в  пути', 'CustOrder', 'Закупки', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(65, 1, 'Оприходование ТМЦ', 'IncomeItem', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(75, 5, 'Экспорт', 'Export', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(67, 5, 'АРМ кассира', 'ARMPos', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(71, 3, 'Товары  на  складе', 'ItemList', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(76, 1, 'Выплата зарплаты', 'OutSalary', 'Касса и платежи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(77, 2, 'Отчет по  зарплате', 'SalaryRep', 'Зарплата', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(78, 2, 'Движение  по  контрагентам', 'CustActivity', 'Касса и платежи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(81, 4, 'Договора', 'ContractList', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(82, 1, 'Перемещение товара', 'MoveItem', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(83, 2, 'Рабочее время', 'Timestat', 'Зарплата', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(84, 1, 'Товарно-транспортная накладная', 'TTN', 'Продажи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(85, 2, 'Неликвидные товары', 'NoLiq', 'Склад', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(86, 3, 'Расчеты с поставщиками', 'PaySelList', 'Касса и платежи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(87, 3, 'Расчеты с покупателями', 'PayBayList', 'Касса и платежи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(88, 1, 'Перемещение  денег', 'MoveMoney', 'Касса и платежи', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(89, 1, 'Заказ (общепит)', 'OrderFood', '', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(91, 5, 'АРМ кассира', 'ARMFood', 'Общепит', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(95, 3, 'Журнал доставок', 'DeliveryList', 'Общепит', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(96, 5, 'АРМ кухни (бара)', 'ArmProdFood', 'Общепит', 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(97, 3, 'Доходы и расходы', 'IOState', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  2, 'Заказаные товары', 'ItemOrder', 'Закупки', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 5, 'Скидки и акции', 'Discounts', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  1, 'Начисление зарплаты', 'CalcSalary', 'Касса и платежи', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  4, 'Начисления и удержания', 'SalaryTypeList', '', 0);
INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Начисления и удержания', 'SalTypeRep', 'Зарплата', 0);
INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Движение  по л/с', 'EmpAccRep', 'Зарплата', 0);
 INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Пр. процессы', 'ProdProcList', 'Производство', 0);
 INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Пр. этапы', 'ProdStageList', 'Производство', 0);
 INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Перемещение  партий ТМЦ', 'MovePart', 'Склад', 0);
 INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Возвраты  покупателей', 'Returnselled', 'Продажи', 0);
INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Возвраты  поставщикам', 'Returnbayed', 'Закупки', 0);
INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  1, 'Полученные услуги', 'IncomeService', 'Услуги', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Состояние  складов', 'StoreItems', 'Склад', 0);
 


INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(2, 105, 'Основная  зарплата', 'осн', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(3, 200, 'Всего начислено', 'Всего. нач', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(4, 600, 'Всего  удержано', 'всего удер', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(5, 900, 'К выдаче', 'К выдаче', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(6, 850, 'Аванс', 'Аванс', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(7, 220, 'НДФЛ', 'НДФЛ', 0);
INSERT INTO `saltypes` (`st_id`, `salcode`, `salname`, `salshortname`, `disabled`) VALUES(8, 300, 'ЕСВ', 'ЕСВ', 0);
 