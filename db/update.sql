CREATE  VIEW `shop_products_view` AS
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
    (case when (`p`.`created` > (now() - interval 1 month)) then 1 else 0 end) AS `novelty`,
    `p`.`comments` AS `comments`,
    `g`.`groupname` AS `groupname`,
    `m`.`manufacturername` AS `manufacturername`,
    `i`.`qty` AS `qty` 
  from 
    (((`shop_products` `p` join `shop_productgroups` `g` on((`p`.`group_id` = `g`.`group_id`))) left join `shop_manufacturers` `m` on((`p`.`manufacturer_id` = `m`.`manufacturer_id`))) join `items_view` `i` on((`p`.`item_id` = `i`.`item_id`)));