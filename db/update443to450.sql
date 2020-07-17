

ALTER TABLE `entrylist` ADD `employee_id` INT NULL  ;

ALTER   VIEW `entrylist_view` AS
  select
    `entrylist`.`entry_id` AS `entry_id`,
    `entrylist`.`document_id` AS `document_id`,
    `entrylist`.`amount` AS `amount`,
    `entrylist`.`quantity` AS `quantity`,
    `documents`.`customer_id` AS `customer_id`,
    `entrylist`.`extcode` AS `extcode`,
    `entrylist`.`stock_id` AS `stock_id`,
    `entrylist`.`employee_id` AS `employee_id`,
    `entrylist`.`service_id` AS `service_id`,
    `store_stock`.`item_id` AS `item_id`,
    `documents`.`document_date` AS `document_date`
  from
    ((`entrylist` left join `store_stock` on((`entrylist`.`stock_id` = `store_stock`.`stock_id`))) join `documents` on((`entrylist`.`document_id` = `documents`.`document_id`)));


