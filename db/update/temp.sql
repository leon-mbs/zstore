SET NAMES 'utf8';

ALTER TABLE promocodes ADD enddate  DATE DEFAULT null; 
ALTER TABLE note_topics ADD ispublic   tinyint(1) DEFAULT 0;
ALTER TABLE note_topicnode ADD islink  tinyint(1) DEFAULT 0;
 

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
    WHERE ic2.cat_id = ic.parent_id), 0) AS childcnt
FROM item_cat ic   ;


DROP VIEW IF EXISTS item_cat_view  ;
DROP TABLE IF EXISTS custitems  ;

CREATE TABLE custitems (
  custitem_id int(11) NOT NULL AUTO_INCREMENT,
  item_id int(11)   NULL,
  customer_id int(11) NOT NULL,
  quantity decimal(10, 3) DEFAULT NULL,
  price decimal(10, 2) NOT NULL DEFAULT '0.00',
  cust_code varchar(255) NOT NULL,
  brand varchar(255) NOT NULL,
  details vaTEXT DEFAULT NULL,
  updatedon date NOT NULL,
  PRIMARY KEY (custitem_id),
  KEY item_id (item_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;


CREATE
VIEW custitems_view
AS
SELECT
  s.custitem_id AS custitem_id,
  s.item_id AS item_id,
  s.customer_id AS customer_id,
  s.quantity AS quantity,
  s.price AS price,
  s.updatedon AS updatedon,
  s.cust_code AS cust_code,
  s.cust_itemname AS cust_itemname,
  s.details AS details,
  i.itemname AS itemname,
  i.item_code AS item_code,
  c.customer_name AS customer_name
FROM ((custitems s
  LEDT JOIN items i
    ON ((s.item_id = i.item_id)))
  JOIN customers c
    ON ((s.customer_id = c.customer_id)))
WHERE ((i.disabled <> 1) AND (c.status <> 1)) ;

 
delete  from  options where  optname='version' ;
insert  into options (optname,optvalue) values('version','6.12.0'); 

 