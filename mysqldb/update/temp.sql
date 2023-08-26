ALTER TABLE `users` ADD `lastlogin` DATETIME NULL  ;

CREATE TABLE zstore.departments (
  department_id int(11) NOT NULL AUTO_INCREMENT,
  department_name varchar(255) NOT NULL,
  details text NOT NULL,
  PRIMARY KEY (department_id)
)
ENGINE = INNODB ;

CREATE TABLE zstore.positions (
  position_id int(11) NOT NULL AUTO_INCREMENT,
  position_name varchar(255) NOT NULL,
  details text NOT NULL,
  PRIMARY KEY (position_id)
)
ENGINE = INNODB;

INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Офісний документ', 'OfficeDoc', '', 0);
INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, 'Офіс', 'Office', '', 0);






 