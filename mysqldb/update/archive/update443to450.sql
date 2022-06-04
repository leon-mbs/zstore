
INSERT   INTO `metadata` (  `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  2, 'Рабочее время', 'Timestat', '', 0);


ALTER TABLE `documents` ADD `firm_id` INT NULL  ;
 
ALTER  VIEW documents_view
AS
SELECT
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
   d.firm_id,
   f.firm_name,
  `metadata`.`meta_name` AS `meta_name`,
  `metadata`.`description` AS `meta_desc`
FROM  `documents` `d`
  LEFT JOIN `users_view` `u`
    ON  `d`.`user_id` = `u`.`user_id` 
  LEFT JOIN `customers` `c`
    ON  `d`.`customer_id` = `c`.`customer_id` 
  JOIN `metadata`
    ON  `metadata`.`meta_id` = `d`.`meta_id` 
  LEFT JOIN `branches` `b`
    ON  `d`.`branch_id` = `b`.`branch_id` 
  LEFT JOIN firms `f`
    ON  `d`.`firm_id` = `f`.`firm_id`  ;


CREATE TABLE `timesheet` (
  time_id int(11) NOT NULL AUTO_INCREMENT,
  emp_id int(11) NOT NULL,
  description varchar(255)   DEFAULT NULL,
  t_start datetime DEFAULT NULL,
  t_end datetime DEFAULT NULL,
  t_type smallint(11) DEFAULT '0',
  t_break smallint(6) DEFAULT '0',
  PRIMARY KEY (time_id),
  KEY emp_id (emp_id)
)    ;    

CREATE  VIEW `timesheet_view` AS 

  select 
    `t`.`time_id` AS `time_id`,
    `t`.`emp_id` AS `emp_id`,
    `t`.`description` AS `description`,
    `t`.`t_start` AS `t_start`,
    `t`.`t_end` AS `t_end`,
    `t`.`t_type` AS `t_type`,
    `t`.`t_break` AS `t_break`,
    `e`.`emp_name` AS `emp_name`,
    `e`.`disabled` AS `disabled`,
    `e`.`branch_id` AS `branch_id` 
  from 
    (`timesheet` `t` join `employees` `e` on((`t`.`emp_id` = `e`.`employee_id`)));
    