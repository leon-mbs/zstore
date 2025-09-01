CREATE TABLE acc_plan (
  acc_code char(5) NOT NULL,
  acc_name varchar(255) NOT NULL,
  iszab tinyint(4) NOT NULL default 0,
  iscustom tinyint(4) NOT NULL  default 0
) ENGINE = INNODB  DEFAULT CHARSET = utf8;  

ALTER TABLE acc_plan
ADD UNIQUE INDEX acc_code (acc_code);


CREATE VIEW acc_plan_view
AS
SELECT
  ap.acc_code AS acc_code,
  ap.acc_name AS acc_name,
  ap.iszab AS iszab,
  ap.iscustom AS iscustom,
  CONCAT(ap.acc_code, ' ', ap.acc_name) AS acc_fullname
FROM acc_plan ap;


CREATE TABLE acc_entry (
  id bigint NOT NULL AUTO_INCREMENT,
  createdon date NOT NULL,
  accdt char(5) NOT NULL,
  accct char(5) NOT NULL,
  amount decimal(11, 2) NOT NULL,
  document_id bigint NOT NULL,
  PRIMARY KEY (id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8; 

ALTER TABLE acc_entry
ADD INDEX createdon (createdon);

ALTER TABLE acc_entry
ADD INDEX document_id (document_id);


 

 
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('20', 'Виробничі запаси', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('22', 'МШП', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('23', 'Виробництво', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('26', 'Готова продукція', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('28', 'Товари', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('30', 'Каса', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('31', 'Рахунки в банках', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('36', 'Розрахунки з покупцями ', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('37', 'Розрахунки з рiзними дебiторами', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('40', 'Статутний капiтал', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('63', 'Розрахунки з постачальниками', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('64', 'Розрахунки за податками й платежами', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('66', 'Розрахунки зa виплатами працівникам', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('70', 'Доходи від реалізації', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('79', 'Фінансові результати', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('90', 'Собівартість реалізації', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('91', 'Загальновиробничі витрати', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('92', 'Адміністративні витрати', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('93', 'Витрати на збут', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('94', 'Інші витрати операційної діяльності', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('201', 'Сировина й матеріали',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('281', 'Товари на складі',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('282', 'Товари в торгівлі',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('33', 'Iншi кошти',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('372', 'Розрахунки з пiдзвiтними особами',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('641', 'Розрахунки за податками',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('642', 'Розрахунки за платежами',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('643', 'Податкові зобов’язання',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('644', 'Податковий кредит',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('98', 'Податок на прибуток', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('701', 'Дохід від реалізації готової продукції',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('702', 'Дохід від реалізації товарів',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('703', 'Дохід від реалізації робіт і послуг',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('901', 'Собівартість реалізованої готової продукції',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('902', 'Собівартість реалізованих товарів',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('903', 'Собівартість реалізованих робіт і послуг',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('97', 'Iншi витрати', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('65', 'Розрахунки за страхуванням', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('15', 'Капітальні інвестиції', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('71', 'Доходи операційної діяльності', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('12', 'Нематеріальні активи', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('13', 'Знос необоротних активів', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('371', 'Розрахунки за виданими авансами',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('68', 'Розрахунки за iншми операцiями', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('681', 'Розрахунки за отриманими авансами',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('44', 'Нерозподiлений прибуток', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('11', 'Iншi необоротнi активи', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('10', 'Основнi засоби', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('25', 'Напiвфабрикати', 0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('6412', 'Розрахунки за ПДВ',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('6414', 'Розрахунки по єдиному податку',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('106', 'Інструменти, прилади та інвентар',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('112', 'Малоцінні необоротні матеріальні активи',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('131', 'Знос основних засобів',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('104', 'Машини та обладнання',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('204', 'Тара',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('207', 'Запчастини',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('203', 'Паливо',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('132', 'Знос МНМА',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('337', 'Кошти в  касi  магазину',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('338', 'Кошти з оплати кредитками',  0,0);
INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('704', 'Вирахування з доходу', 0,0); 

INSERT INTO acc_plan (acc_code, acc_name, iszab,iscustom) VALUES('МЦ', 'Малоцінні активи в експлуатації', 1,0);


 
INSERT INTO metadata (  meta_type, description, meta_name, menugroup,   disabled) VALUES(38, 4, 'План рахункiв', 'AccountList', 'Бухоблiк',   0 );
INSERT INTO metadata (  meta_type, description, meta_name, menugroup,   disabled) VALUES(39, 3, 'Журнал проводок', 'AccountEntryList', 'Бухоблiк',   0 );
INSERT INTO metadata (  meta_type, description, meta_name, menugroup,   disabled) VALUES(42, 2, 'Оборотно-сальдова вiдомiсть', 'ObSaldo', 'Бухоблiк',     0);
INSERT INTO metadata ( meta_type, description, meta_name, menugroup,   disabled) VALUES(43, 2, 'Шахматна вiдомiсть', 'Shahmatka', 'Бухоблiк',   0 );
INSERT INTO metadata (  meta_type, description, meta_name, menugroup,   disabled) VALUES(44, 2, 'Рух по рахунку', 'AccountActivity', 'Бухоблiк',     0);
INSERT INTO metadata (meta_id, meta_type, description, meta_name, menugroup,   disabled) VALUES(41, 1, 'Ручна проводка ', 'ManualEntry', 'Бухоблiк',   0);
INSERT INTO metadata (meta_type, description,   meta_name, menugroup,   disabled) VALUES( 2, 'Фiн. звiт малого  пiдприємства', 'FinReportSmall', 'Бухоблiк',  , 0);
