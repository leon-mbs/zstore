ALTER TABLE `customers` 
  ADD `createdon` Date , 
  ADD `leadstatus` VARCHAR(255) NULL , 
  ADD `leadsource` VARCHAR(255) NULL ;

ALTER VIEW `customers_view` AS 
  select 
    `customers`.`customer_id` AS `customer_id`,
    `customers`.`customer_name` AS `customer_name`,
    `customers`.`detail` AS `detail`,
    `customers`.`email` AS `email`,
    `customers`.`phone` AS `phone`,
    `customers`.`status` AS `status`,
    `customers`.`city` AS `city`,
    `customers`.`leadsource` AS `leadsource`,
    `customers`.`leadstatus` AS `leadstatus`,
    `customers`.`createdon` AS `createdon`,
    (
  select 
    count(0) 
  from 
    `messages` `m` 
  where 
    ((`m`.`item_id` = `customers`.`customer_id`) and (`m`.`item_type` = 2))) AS `mcnt`,(
  select 
    count(0) 
  from 
    `files` `f` 
  where 
    ((`f`.`item_id` = `customers`.`customer_id`) and (`f`.`item_type` = 2))) AS `fcnt`,(
  select 
    count(0) 
  from 
    `eventlist` `e` 
  where 
    ((`e`.`customer_id` = `customers`.`customer_id`) and (`e`.`eventdate` >= now()))) AS `ecnt` 
  from 
    `customers`;