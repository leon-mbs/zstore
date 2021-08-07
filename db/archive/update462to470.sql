ALTER TABLE `mfund` ADD `detail` LONGTEXT NULL  ;

ALTER TABLE `paylist` ADD `detail` LONGTEXT NULL  ;

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
    `pl`.`detail` AS `detail`,
    `d`.`document_number` AS `document_number`,
    `u`.`username` AS `username`,
    `m`.`mf_name` AS `mf_name`,
    `d`.`customer_id` AS `customer_id`,
    `d`.`customer_name` AS `customer_name` 
  from 
    (((`paylist` `pl` join `documents_view` `d` on((`pl`.`document_id` = `d`.`document_id`))) join `users_view` `u` on((`pl`.`user_id` = `u`.`user_id`))) join `mfund` `m` on((`pl`.`mf_id` = `m`.`mf_id`)));


ALTER TABLE `items` ADD `item_type` INT NULL  ;    
    
ALTER VIEW items_view AS 
  select 
    items.item_id AS item_id,
    items.itemname AS itemname,
    items.description AS description,
    items.detail AS detail,
    items.item_code AS item_code,
    items.bar_code AS bar_code,
    items.cat_id AS cat_id,
    items.msr AS msr,
    items.disabled AS disabled,
    items.minqty AS minqty,
    items.item_type AS item_type,
    items.manufacturer AS manufacturer,
    item_cat.cat_name AS cat_name 
  from 
    (items left join item_cat on((items.cat_id = item_cat.cat_id)));    
    
ALTER VIEW store_stock_view AS 
  select 
    st.stock_id AS stock_id,
    st.item_id AS item_id,
    st.partion AS partion,
    st.store_id AS store_id,
    i.itemname AS itemname,
    i.item_code AS item_code,
    i.cat_id AS cat_id,
    i.msr AS msr,
    i.item_type AS item_type,
    i.bar_code AS bar_code,
    i.cat_name AS cat_name,
    i.disabled AS itemdisabled,
    stores.storename AS storename,
    st.qty AS qty,
    st.snumber AS snumber,
    st.sdate AS sdate 
  from 
    ((store_stock st join items_view i on(((i.item_id = st.item_id) and (i.disabled <> 1)))) join stores on((stores.store_id = st.store_id)));    