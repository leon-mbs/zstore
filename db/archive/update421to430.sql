CREATE TABLE  `roles` ( `role_id` INT NOT NULL AUTO_INCREMENT , `rolename` VARCHAR(255) NULL , `acl` MEDIUMTEXT  NULL , PRIMARY KEY (`role_id`)) ;

ALTER TABLE `users` ADD `role_id` INT NULL  ;  

CREATE  VIEW `roles_view` AS 
  select 
    `roles`.`role_id` AS `role_id`,
    `roles`.`rolename` AS `rolename`,
    `roles`.`acl` AS `acl`,
    (
  select 
    coalesce(count(*),   0) 
  from 
    `users` 
  where 
    (`users`.`role_id` = `roles`.`role_id`)) AS `cnt` 
  from 
    `roles`;
     
    
ALTER VIEW `users_view` AS 
  select 
    `users`.`user_id` AS `user_id`,
    `users`.`userlogin` AS `userlogin`,
    `users`.`userpass` AS `userpass`,
    `users`.`createdon` AS `createdon`,
    `users`.`email` AS `email`,
    `users`.`acl` AS `acl`,
    `users`.`options` AS `options`,
    `users`.`disabled` AS `disabled`,
    `roles`.`rolename` AS `rolename`,
    `users`.`role_id` AS `role_id`,
    `roles`.`acl` AS `roleacl`,
    coalesce(`employees`.`employee_id`,
    0) AS `employee_id`,
    (case when isnull(`employees`.`emp_name`) then `users`.`userlogin` else `employees`.`emp_name` end) AS `username` 
  from 
    ((`users` left join `employees` on(((`users`.`userlogin` = `employees`.`login`) and (`employees`.`disabled` <> 1)))) left join `roles` on((`users`.`role_id` = `roles`.`role_id`)));    
    

    INSERT INTO `roles` (`role_id`, `rolename`, `acl`) VALUES (NULL, 'admins', NULL);
    
    UPDATE users set  role_id=(select role_id  from roles  where  rolename='admins' limit 0,1 )  where  userlogin='admin'
        


