SET NAMES 'utf8';

ALTER TABLE promocodes ADD enddate  DATE DEFAULT null; 
ALTER TABLE note_topics ADD ispublic   tinyint(1) DEFAULT 0;
ALTER TABLE note_topicnode ADD islink  tinyint(1) DEFAULT 0;
ALTER TABLE parealist ADD notes  varchar(255) DEFAULT NULL;
ALTER TABLE equipments ADD pa_id int(11) DEFAULT  NULL;
ALTER TABLE equipments ADD emp_id int(11) DEFAULT  NULL;
ALTER TABLE equipments ADD invnumber  varchar(255) DEFAULT NULL;

 

DROP VIEW IF EXISTS item_cat_view  ;

CREATE VIEW item_cat_view
AS
SELECT
  ic.cat_id AS cat_id,
  ic.cat_name AS cat_name,
  ic.detail AS detail,
  ic.parent_id AS parent_id,
  COALESCE((SELECT
      COUNT(*)
    FROM items i
    WHERE i.cat_id = ic.cat_id), 0) AS itemscnt  ,
    COALESCE((SELECT
      COUNT(*)
    FROM item_cat ic2
    WHERE ic.cat_id = ic2.parent_id), 0) AS childcnt
FROM item_cat ic   ;


DROP VIEW IF EXISTS custitems_view  ;
DROP TABLE IF EXISTS custitems  ;

CREATE TABLE custitems (
  custitem_id int(11) NOT NULL AUTO_INCREMENT,
  item_id int(11)   NULL,
  customer_id int(11) NOT NULL,
  quantity decimal(10, 3) DEFAULT NULL,
  price decimal(10, 2) NOT NULL DEFAULT '0.00',
  cust_code varchar(255) NOT NULL,
  cust_name varchar(255) NOT NULL,
  brand varchar(255) NOT NULL,
  store varchar(255) NOT NULL,
  bar_code varchar(64) NOT NULL,
  details TEXT DEFAULT NULL,
  updatedon date NOT NULL,
  PRIMARY KEY (custitem_id),
  KEY item_id (item_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;


CREATE
VIEW custitems_view
AS
SELECT
  s.custitem_id AS custitem_id,
  s.cust_name AS cust_name,
  coalesce(s.item_id,0) AS item_id,
  s.customer_id AS customer_id,
  s.quantity AS quantity,
  s.price AS price,
  s.cust_code AS cust_code,
  s.brand AS brand,
  s.store AS store,
  s.bar_code AS bar_code,
  s.details AS details,
  s.updatedon AS updatedon,
  c.customer_name AS customer_name
FROM   custitems s
 
  JOIN customers c
    ON   s.customer_id = c.customer_id 
WHERE c.status <> 1 
 ;

 

 
CREATE
VIEW equipments_view
AS
SELECT
  e.eq_id AS eq_id,
  e.eq_name AS eq_name,
  e.detail AS detail,
  e.disabled AS disabled,
  e.description AS description,
  e.branch_id AS branch_id,
  e.invnumber AS invnumber,
  e.pa_id AS pa_id,
  e.emp_id AS emp_id,
  p.pa_name AS pa_name,
  employees.emp_name AS emp_name
FROM ((equipments e
  LEFT JOIN employees
    ON ((employees.employee_id = e.emp_id)))
  LEFT JOIN parealist p
    ON ((p.pa_id = e.pa_id))); 
 
 

 
delete  from  options where  optname='version' ;
insert  into options (optname,optvalue) values('version','6.12.0'); 

 