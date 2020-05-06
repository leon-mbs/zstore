
ALTER TABLE `items` ADD `manufacturer` VARCHAR(255) NULL ;


ALTER VIEW `items_view` AS 
  select 
    `items`.`item_id` AS `item_id`,
    `items`.`itemname` AS `itemname`,
    `items`.`description` AS `description`,
    `items`.`detail` AS `detail`,
    `items`.`item_code` AS `item_code`,
    `items`.`bar_code` AS `bar_code`,
    `items`.`cat_id` AS `cat_id`,
    `items`.`msr` AS `msr`,
    `items`.`disabled` AS `disabled`,
    `items`.`minqty` AS `minqty`,
    `items`.`manufacturer` AS `manufacturer`,
    `item_cat`.`cat_name` AS `cat_name` 
  from 
    (`items` left join `item_cat` on((`items`.`cat_id` = `item_cat`.`cat_id`)));


