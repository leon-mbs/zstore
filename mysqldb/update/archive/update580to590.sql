INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'Состояние  складов', 'StoreItems', 'Склад', 0);

DELETE FROM `options` WHERE `options`.`optname` = 'val' ;
INSERT INTO `options` (`optname`, `optvalue`) VALUES('val', 'a:2:{s:7:\"vallist\";a:2:{i:1642675955;O:12:\"App\\DataItem\":2:{s:2:\"id\";i:1642675955;s:9:\"\0*\0fields\";a:3:{s:4:\"code\";s:3:\"USD\";s:4:\"name\";s:12:\"Доллар\";s:4:\"rate\";s:2:\"28\";}}i:1642676126;O:12:\"App\\DataItem\":2:{s:2:\"id\";i:1642676126;s:9:\"\0*\0fields\";a:3:{s:4:\"code\";s:4:\"EURO\";s:4:\"name\";s:8:\"Евро\";s:4:\"rate\";s:2:\"33\";}}}s:8:\"valprice\";i:0;}');;


ALTER TABLE `entrylist` ADD `tag` INT NULL DEFAULT '0'  ;

DROP VIEW entrylist_view  ;

CREATE VIEW entrylist_view
AS
SELECT
  `entrylist`.`entry_id` AS `entry_id`,
  `entrylist`.`document_id` AS `document_id`,
  `entrylist`.`quantity` AS `quantity`,
  `documents`.`customer_id` AS `customer_id`,
  `entrylist`.`stock_id` AS `stock_id`,
  `entrylist`.`service_id` AS `service_id`,
  `entrylist`.`tag` AS `tag`,
  `store_stock`.`item_id` AS `item_id`,
  `store_stock`.`partion` AS `partion`,
  `documents`.`document_date` AS `document_date`,
  `entrylist`.`outprice` AS `outprice`
FROM ((`entrylist`
  LEFT JOIN `store_stock`
    ON ((`entrylist`.`stock_id` = `store_stock`.`stock_id`)))
  JOIN `documents`
    ON ((`entrylist`.`document_id` = `documents`.`document_id`)));
    
    
ALTER TABLE `ppo_zformstat` ADD   `tag` INT NULL DEFAULT '0'  ;    
