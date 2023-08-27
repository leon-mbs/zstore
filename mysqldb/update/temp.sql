ALTER TABLE `users` ADD `lastlogin` DATETIME NULL  ;
ALTER TABLE `employees` ADD `department_id` INT NULL  ;
ALTER TABLE `employees` ADD `position_id` INT NULL  ;
ALTER TABLE `parealist` ADD  details text   NULL ;



CREATE TABLE departments (
  department_id int(11) NOT NULL AUTO_INCREMENT,
  department_name varchar(255) NOT NULL,
  details text NOT NULL,
  PRIMARY KEY (department_id)
)
ENGINE = INNODB;

CREATE TABLE positions (
  position_id int(11) NOT NULL AUTO_INCREMENT,
  position_name varchar(255) NOT NULL,
  details text NOT NULL,
  PRIMARY KEY (position_id)
)
ENGINE = INNODB;
 

CREATE VIEW employees_view
AS
SELECT
  `e`.`employee_id` AS `employee_id`,
  `e`.`login` AS `login`,
  `e`.`detail` AS `detail`,
  `e`.`disabled` AS `disabled`,
  `e`.`emp_name` AS `emp_name`,
  `e`.`branch_id` AS `branch_id`,
  `e`.`department_id` AS `department_id`,
  `e`.`position_id` AS `position_id`
FROM ((`employees` `e`
  LEFT JOIN `departments` `d`
    ON ((`e`.`department_id` = `d`.`department_id`)))
  LEFT JOIN `positions` `p`
    ON ((`e`.`position_id` = `p`.`position_id`)));

INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Офісний документ', 'OfficeDoc', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Офіс', 'Office', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 4, 'Відділи', 'DepartmentList', 'Кадри', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 4, 'Посади', 'PositionList', 'Кадри', 0);
UPDATE  metadata set  menugroup ='Кадри' where meta_type=4 and  meta_name = 'EmployeeList';







 