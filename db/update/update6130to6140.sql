SET NAMES 'utf8';

ALTER TABLE store_stock ADD emp_id int(11) DEFAULT NULL;    

ALTER TABLE store_stock ADD INDEX  (emp_id) ; 

DROP VIEW store_stock_view;

CREATE VIEW store_stock_view
AS
SELECT
  st.stock_id AS stock_id,
  st.item_id AS item_id,
  st.partion AS partion,
  st.store_id AS store_id,
  st.customer_id AS customer_id,
  st.emp_id AS emp_id,
  i.itemname AS itemname,
  i.item_code AS item_code,
  i.cat_id AS cat_id,
  i.msr AS msr,
  i.item_type AS item_type,
  i.bar_code AS bar_code,
  i.cat_name AS cat_name,
  i.disabled AS itemdisabled,
  stores.storename AS storename,
  st.qty AS qty,
  st.snumber AS snumber,
  st.sdate AS sdate,
  employees.emp_name AS emp_name
FROM  store_stock st
  JOIN items_view i
    ON  i.item_id = st.item_id  AND  i.disabled <> 1 
  JOIN stores
    ON  stores.store_id = st.store_id  AND  stores.disabled <> 1 
  LEFT JOIN employees
    ON  employees.employee_id  = st.emp_id ;

DROP VIEW custitems_view;

CREATE VIEW custitems_view
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
  
  WHERE c.status <> 1   ;
 
DROP VIEW prodstage_view; 
 
CREATE VIEW  prodstage_view
AS
SELECT
  ps.st_id AS st_id,
  ps.pp_id AS pp_id,
  ps.pa_id AS pa_id,
  ps.state AS state,
  ps.stagename AS stagename,
  ps.detail AS detail,
  pr.procname AS procname,
  pr.state AS procstate,
  pa.pa_name AS pa_name
FROM prodstage ps
  JOIN prodproc pr
    ON pr.pp_id = ps.pp_id 
  JOIN parealist pa
    ON pa.pa_id = ps.pa_id ;  
 
DROP VIEW prodproc_view; 
 
CREATE
VIEW prodproc_view
AS
SELECT
  p.pp_id AS pp_id,
  p.procname AS procname,
  p.basedoc AS basedoc,
  
  p.state AS state,

  COALESCE((SELECT
      COUNT(0)
    FROM prodstage ps
    WHERE (ps.pp_id = p.pp_id)), NULL) AS stagecnt,
  p.detail AS detail
FROM prodproc p ;

 
  
  INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Перемiщення мiж етапами', 'ProdMove', 'Виробництво', 0);
  
  delete from options where  optname='version' ;
  insert into options (optname,optvalue) values('version','6.14.0'); 
  
