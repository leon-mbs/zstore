 INSERT INTO `metadata` (  `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  2, 'Заказаные товары', 'ItemOrder', 'Закупки', 0);
 INSERT INTO `metadata` (  `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 5, 'Скидки и акции', 'Discounts', '', 0);

   
 ALTER TABLE `services` ADD `category` VARCHAR(255) NULL ;
 ALTER TABLE `notifies` ADD `sender_id` INT NULL  ;
 

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
 
 
ALTER TABLE `paylist` ADD `bonus` INT NULL  ;
 
DROP VIEW paylist_view ;

CREATE VIEW paylist_view
AS
SELECT
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
FROM (((`paylist` `pl`
  JOIN `documents_view` `d`
    ON ((`pl`.`document_id` = `d`.`document_id`)))
  JOIN `users_view` `u`
    ON ((`pl`.`user_id` = `u`.`user_id`)))
  LEFT JOIN `mfund` `m`
    ON ((`pl`.`mf_id` = `m`.`mf_id`)));

CREATE TABLE shop_prod_var (
  var_id int(11) NOT NULL AUTO_INCREMENT,
  attr_id int(11) NOT NULL,
  details text DEFAULT NULL,
  var_name varchar(255) NOT NULL,
  PRIMARY KEY (var_id)
)
ENGINE = INNODB,
CHARACTER SET utf8,
COLLATE utf8_general_ci;