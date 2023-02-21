
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


ALTER VIEW `documents_view` AS
  select 
    `d`.`document_id` AS `document_id`,
    `d`.`document_number` AS `document_number`,
    `d`.`document_date` AS `document_date`,
    `d`.`user_id` AS `user_id`,
    `d`.`content` AS `content`,
    `d`.`amount` AS `amount`,
    `d`.`meta_id` AS `meta_id`,
    `u`.`username` AS `username`,
    `c`.`customer_id` AS `customer_id`,
    `c`.`customer_name` AS `customer_name`,
    `d`.`state` AS `state`,
    `d`.`notes` AS `notes`,
    `d`.`payamount` AS `payamount`,
    `d`.`payed` AS `payed`,
    `d`.`parent_id` AS `parent_id`,
    `d`.`branch_id` AS `branch_id`,
    `b`.`branch_name` AS `branch_name`,
    `metadata`.`meta_name` AS `meta_name`,
    `metadata`.`description` AS `meta_desc` 
  from 
    ((((`documents` `d` left join `users_view` `u` on((`d`.`user_id` = `u`.`user_id`))) left join `customers` `c` on((`d`.`customer_id` = `c`.`customer_id`))) join `metadata` on((`metadata`.`meta_id` = `d`.`meta_id`))) left join `branches` `b` on((`d`.`branch_id` = `b`.`branch_id`)));