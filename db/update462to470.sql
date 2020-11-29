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

    