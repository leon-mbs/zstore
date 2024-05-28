SET NAMES 'utf8';

CREATE TABLE promocodes (
  id int(11) NOT NULL AUTO_INCREMENT,
  code varchar(16) NOT NULL,
  type tinyint(4) NOT NULL,
  disabled tinyint(1) NOT NULL default 0,
  details text DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE = INNODB DEFAULT CHARSET = utf8;

ALTER TABLE promocodes
ADD UNIQUE INDEX code (code)   ; 

 
DROP VIEW IF EXISTS customers_view  ;

CREATE VIEW customers_view  
AS
SELECT 
  customers.customer_id AS customer_id,
  customers.customer_name AS customer_name,
  customers.detail AS detail,
  customers.email AS email,
  customers.phone AS phone,
  customers.status AS status,
  customers.city AS city,
  customers.leadstatus AS leadstatus,
  customers.leadsource AS leadsource,
  customers.createdon AS createdon,
  customers.country AS country,
  customers.passw AS passw,
  (SELECT
      COUNT(0)
    FROM messages m
    WHERE ((m.item_id = customers.customer_id)
    AND (m.item_type = 2))) AS mcnt,
  (SELECT
      COUNT(0)
    FROM files f
    WHERE ((f.item_id = customers.customer_id)
    AND (f.item_type = 2))) AS fcnt,
  (SELECT
      COUNT(0)
    FROM eventlist e
    WHERE ((e.customer_id = customers.customer_id)
    AND (e.eventdate >= NOW()))) AS ecnt
FROM customers;


ALTER TABLE equipments ADD branch_id INT NULL ;
ALTER TABLE ppo_zformstat ADD amount4 decimal(10, 2)  default 0;


INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Управлiнський баланс', 'Balance', '', 0);  
update  metadata set  description ='Програма лояльності' where  meta_name='Discounts';
update  metadata set  description ='Отримані послуги' where  meta_name='IncomeService';
update  metadata set  description ='Прибутки та видатки' where  meta_name='PayBalance';

delete  from  options where  optname='version' ;
insert  into options (optname,optvalue) values('version','6.9.0'); 