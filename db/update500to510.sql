
   
ALTER TABLE `customers` ADD `country` VARCHAR(255) NULL  ;     
ALTER TABLE `timesheet` ADD `branch_id` INT NULL  ;

DROP  VIEW timesheet_view;
CREATE VIEW `timesheet_view` AS 
  select 
    `t`.`time_id` AS `time_id`,
    `t`.`emp_id` AS `emp_id`,
    `t`.`description` AS `description`,
    `t`.`t_start` AS `t_start`,
    `t`.`t_end` AS `t_end`,
    `t`.`t_type` AS `t_type`,
    `t`.`t_break` AS `t_break`,
    `e`.`emp_name` AS `emp_name`,
    `b`.`branch_name` AS `branch_name`,
    `e`.`disabled` AS `disabled`,
    `t`.`branch_id` AS `branch_id` 
  from 
    `timesheet` `t` join `employees` `e` on `t`.`emp_id` = `e`.`employee_id`
     left join branches  b  on t.branch_id = b.branch_id;

DROP  VIEW customers_view;    
CREATE VIEW `customers_view` AS 
  select 
    `customers`.`customer_id` AS `customer_id`,
    `customers`.`customer_name` AS `customer_name`,
    `customers`.`detail` AS `detail`,
    `customers`.`email` AS `email`,
    `customers`.`phone` AS `phone`,
    `customers`.`status` AS `status`,
    `customers`.`city` AS `city`,
    `customers`.`leadsource` AS `leadsource`,
    `customers`.`leadstatus` AS `leadstatus`,
    `customers`.`country` AS `country`,
    (
  select 
    count(0) 
  from 
    `messages` `m` 
  where 
    ((`m`.`item_id` = `customers`.`customer_id`) and (`m`.`item_type` = 2))) AS `mcnt`,(
  select 
    count(0) 
  from 
    `files` `f` 
  where 
    ((`f`.`item_id` = `customers`.`customer_id`) and (`f`.`item_type` = 2))) AS `fcnt`,(
  select 
    count(0) 
  from 
    `eventlist` `e` 
  where 
    ((`e`.`customer_id` = `customers`.`customer_id`) and (`e`.`eventdate` >= now()))) AS `ecnt` 
  from 
    `customers`;     
     
     
