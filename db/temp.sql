  
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
  `lastactive`,
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
 
 
CREATE TABLE `prodproc` (
  `pp_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `detail` LONGTEXT DEFAULT NULL,
   PRIMARY KEY (`pp_id`)
  
) engine=InnoDB DEFAULT CHARSET=utf8;



    
  

CREATE TABLE `empacc` (
  `ea_id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `optype` int(11) DEFAULT NULL,
  //`createdon` date NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
 
  PRIMARY KEY (`ea_id`),
  KEY `emp_id` (`emp_id`)
) engine=InnoDB DEFAULT CHARSET=utf8;



     
    