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
 
  
  INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Брак та вiдходи', 'ProdLost', 'Виробництво', 0);
  
  delete  from  options where  optname='version' ;
  insert  into options (optname,optvalue) values('version','6.14.0''); 
  
