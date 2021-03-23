ALTER TABLE `item_cat` ADD `parent_id` INT   NULL DEFAULT '0'  ;   

ALTER TABLE `shop_attributes` CHANGE `group_id` `cat_id` INT(11) NOT NULL;
ALTER TABLE `shop_attributevalues` CHANGE `product_id` `item_id` INT(11) NOT
ALTER TABLE `shop_prod_comments` CHANGE `product_id` `item_id` INT(11) NOT NULL;

ALTER VIEW `shop_attributes_view` AS 
  select 
    `shop_attributes`.`attribute_id` AS `attribute_id`,
    `shop_attributes`.`attributename` AS `attributename`,
    `shop_attributes`.`cat_id` AS `cat_id`,
    `shop_attributes`.`attributetype` AS `attributetype`,
    `shop_attributes`.`valueslist` AS `valueslist`,
    `shop_attributes`.`showinlist` AS `showinlist`,
    `shop_attributes_order`.`ordern` AS `ordern` 
  from 
    (`shop_attributes` join `shop_attributes_order` on(((`shop_attributes`.`attribute_id` = `shop_attributes_order`.`attr_id`) and (`shop_attributes`.`cat_id` = `shop_attributes_order`.`pg_id`)))) 
  order by 
    `shop_attributes_order`.`ordern`; 

    
    
ALTER   VIEW `shop_products_view` AS 
  select 
    `i`.`item_id` AS `item_id`,
    `i`.`itemname` AS `itemname`,
    `i`.`description` AS `description`,
    `i`.`detail` AS `detail`,
    `i`.`item_code` AS `item_code`,
    `i`.`bar_code` AS `bar_code`,
    `i`.`cat_id` AS `cat_id`,
    `i`.`msr` AS `msr`,
    `i`.`disabled` AS `disabled`,
    `i`.`minqty` AS `minqty`,
    `i`.`item_type` AS `item_type`,
    `i`.`manufacturer` AS `manufacturer`,
    `i`.`cat_name` AS `cat_name`,
    coalesce((
  select 
    sum(`store_stock`.`qty`) 
  from 
    `store_stock` 
  where 
    (`store_stock`.`item_id` = `i`.`item_id`)),0) AS `qty`,coalesce((
  select 
    count(0) 
  from 
    `shop_prod_comments` `c` 
  where 
    (`c`.`item_id` = `i`.`item_id`)),0) AS `comments`,coalesce((
  select 
    sum(`c`.`rating`) 
  from 
    `shop_prod_comments` `c` 
  where 
    (`c`.`item_id` = `i`.`item_id`)),0) AS `ratings` 
  from 
    `items_view` `i`;    
    
    
/* 
DROP TABLE `shop_products` ;
 
CREATE TABLE `shop_products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `productname` varchar(255) NOT NULL,
  `sef` varchar(64) DEFAULT NULL,
  `item_id` int(11) NOT NULL,
  `rating` smallint(6) DEFAULT '0',
  `comments` int(11) DEFAULT '0',
  `detailprod` longtext,
  PRIMARY KEY (`product_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `shop_products_fk1` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 
ALTER VIEW `shop_products_view` AS 
  select 
    `p`.`product_id` AS `product_id`,
 
    `p`.`productname` AS `productname`,
   
  
 
  
    `p`.`sef` AS `sef`,
    `p`.`item_id` AS `item_id`,
 
   
    `p`.`rating` AS `rating`,
    `i`.`item_code` AS `item_code`,
    `i`.`itemname` AS `itemname`,  
    `i`.`description` AS `description`,
  
    `p`.`comments` AS `comments`,
    `i`.`cat_name` AS `cat_name`,
    `i`.`manufacturer` AS `manufacturer` 
    
  from 
    (`shop_products` `p` join `items_view` `i` on((`p`.`item_id` = `i`.`item_id`))); 
 
     */