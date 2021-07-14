 INSERT INTO `metadata` (  `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  2, 'Заказаные товары', 'ItemOrder', 'Продажи', 0);
 INSERT INTO `metadata` (  `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 5, 'Скидки и акции', 'Discounts', '', 0);

   
 ALTER TABLE `users` ADD `lastactive` DATETIME NULL  ;
 
 DROP VIEW users_view  ;
 
 CREATE VIEW users_view AS
SELECT
  `users`.`user_id` AS `user_id`,
  `users`.`userlogin` AS `userlogin`,
  `users`.`userpass` AS `userpass`,
  `users`.`createdon` AS `createdon`,
  `users`.`email` AS `email`,
  `users`.`acl` AS `acl`,
  `users`.`options` AS `options`,
  `users`.`disabled` AS `disabled`,
  `users`.`lastactive` AS `lastactive`,
 
  `roles`.`rolename` AS `rolename`,
  `users`.`role_id` AS `role_id`,
  `roles`.`acl` AS `roleacl`,
  COALESCE(`employees`.`employee_id`, 0) AS `employee_id`,
  (CASE WHEN ISNULL(`employees`.`emp_name`) THEN `users`.`userlogin` ELSE `employees`.`emp_name` END) AS `username`
FROM ((`users`
  LEFT JOIN `employees`
    ON (((`users`.`userlogin` = `employees`.`login`)
    AND (`employees`.`disabled` <> 1))))
  LEFT JOIN `roles`
    ON ((`users`.`role_id` = `roles`.`role_id`)));
 
 
 