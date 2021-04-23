INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 5, 'АРМ кассира', 'ARMFood', 'Общепит', 0);
INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`)  VALUES( 1, 'Заказ (общепит)', 'OrderFood', '', 0);
 
ALTER TABLE `paylist` CHANGE `paydate` `paydate` DATETIME NULL DEFAULT NULL;
ALTER TABLE `entrylist` CHANGE `extcode` `extcode` INT(11) NULL DEFAULT NULL;
ALTER TABLE `entrylist` ADD `outprice` DECIMAL(10,2) NULL  ;
ALTER VIEW `entrylist_view` AS
  select 
    `entrylist`.`entry_id` AS `entry_id`,
    `entrylist`.`document_id` AS `document_id`,
    `entrylist`.`amount` AS `amount`,
    `entrylist`.`quantity` AS `quantity`,
    `documents`.`customer_id` AS `customer_id`,
    `entrylist`.`extcode` AS `extcode`,
    `entrylist`.`stock_id` AS `stock_id`,
    `entrylist`.`service_id` AS `service_id`,
    `store_stock`.`item_id` AS `item_id`,
    `store_stock`.`partion` AS `partion`,
    `documents`.`document_date` AS `document_date`,
    `entrylist`.`outprice` AS `outprice` 
  from 
    ((`entrylist` left join `store_stock` on((`entrylist`.`stock_id` = `store_stock`.`stock_id`))) join `documents` on((`entrylist`.`document_id` = `documents`.`document_id`)));
    