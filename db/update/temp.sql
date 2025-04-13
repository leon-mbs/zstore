ALTER VIEW custitems_view
AS
SELECT
  `s`.`custitem_id` AS `custitem_id`,
  `s`.`cust_name` AS `cust_name`,
  COALESCE(`s`.`item_id`, 0) AS `item_id`,
  `s`.`customer_id` AS `customer_id`,
  `s`.`quantity` AS `quantity`,
  `s`.`price` AS `price`,
  `s`.`cust_code` AS `cust_code`,
  `s`.`brand` AS `brand`,
  `s`.`store` AS `store`,
  `s`.`bar_code` AS `bar_code`,
  `s`.`details` AS `details`,
  `s`.`updatedon` AS `updatedon`,
  `c`.`customer_name` AS `customer_name`,
   i.item_code 
FROM `custitems` `s`
  JOIN `customers` `c`
    ON `s`.`customer_id` = `c`.`customer_id`
  LEFT JOIN items i ON  s.item_id = i.item_id 
  
  WHERE c.status <> 1 
 
  //ALTER TABLE prodstage  ADD  storder  int(11) DEFAULT null;
CREATE TABLE  prodentry (
  id int NOT NULL AUTO_INCREMENT,
  item_id int NOT NULL,
  pa_id int   NULL,
 
  quantity decimal(11, 3) DEFAULT 0,
  partion decimal(10, 2) DEFAULT 0,
  document_id int NOT NULL,
  KEY (pa_id) ,
  KEY (item_id) ,
  KEY (document_id) ,
  PRIMARY KEY (id)
) ENGINE = INNODB DEFAULT CHARSET = utf8 ;   
  
  

CREATE
VIEW prodentry_view
AS
SELECT
  p.id AS id,
  p.item_id AS item_id,
  p.pa_id AS pa_id,
  d.document_date AS document_date,
  p.quantity AS quantity,
  p.partion AS partion,
  p.document_id AS document_id,
  d.document_number AS document_number,
  d.notes AS notes ,
  i.itemname,
  i.item_code,
  a.pa_name
FROM prodentry p
  JOIN documents d
    ON p.document_id = d.document_id
  JOIN items i
    ON p.item_id = i.item_id
  LEFT JOIN parealist a
    ON p.pa_id = a.pa_id   ;
  
  INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Брак та вiдходи', 'ProdLost', 'Виробництво', 0);
  
  delete  from  options where  optname='version' ;
  insert  into options (optname,optvalue) values('version','6.14.0''); 
  
