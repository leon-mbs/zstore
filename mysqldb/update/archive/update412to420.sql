INSERT INTO `metadata` (  `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  2, 'Движение  по  контрагентам', 'CustActivity', 'Платежи', 0);


ALTER  VIEW `paylist_view` AS
  select 
    `pl`.`pl_id` AS `pl_id`,
    `pl`.`document_id` AS `document_id`,
    `pl`.`amount` AS `amount`,
    `pl`.`mf_id` AS `mf_id`,
    `pl`.`notes` AS `notes`,
    `pl`.`user_id` AS `user_id`,
    `pl`.`paydate` AS `paydate`,
    `pl`.`paytype` AS `paytype`,
    `d`.`document_number` AS `document_number`,
    `u`.`username` AS `username`,
    `m`.`mf_name` AS `mf_name`,
    `d`.`customer_id` AS `customer_id`,
    `d`.`customer_name` AS `customer_name` 
  from 
    (((`paylist` `pl` join `documents_view` `d` on((`pl`.`document_id` = `d`.`document_id`))) join `users_view` `u` on((`pl`.`user_id` = `u`.`user_id`))) left join `mfund` `m` on((`pl`.`mf_id` = `m`.`mf_id`)));
    
ALTER VIEW `shop_products_view` AS 
  select 
    `p`.`product_id` AS `product_id`,
    `p`.`group_id` AS `group_id`,
    `p`.`productname` AS `productname`,
    `p`.`manufacturer_id` AS `manufacturer_id`,
    `p`.`price` AS `price`,
    `p`.`sold` AS `sold`,
    `p`.`deleted` AS `deleted`,
    `p`.`sef` AS `sef`,
    `p`.`item_id` AS `item_id`,
    `p`.`created` AS `created`,
    `p`.`detail` AS `detail`,
    `p`.`rating` AS `rating`,
    `i`.`item_code` AS `item_code`,
    (case when (`p`.`created` > (now() - interval 1 month)) then 1 else 0 end) AS `novelty`,
    `p`.`comments` AS `comments`,
    `g`.`groupname` AS `groupname`,
    `m`.`manufacturername` AS `manufacturername`,
    0 AS `qty` 
  from 
    (((`shop_products` `p` join `shop_productgroups` `g` on((`p`.`group_id` = `g`.`group_id`))) left join `shop_manufacturers` `m` on((`p`.`manufacturer_id` = `m`.`manufacturer_id`))) join `items` `i` on((`p`.`item_id` = `i`.`item_id`)));    

    