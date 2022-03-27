INSERT INTO `metadata` (  `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  5, 'Постачальники', 'SupplierList', 'Дропшипінг', 0);
INSERT INTO `metadata` (  `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  5, 'Товари', 'SupItems', 'Дропшипінг', 0);

CREATE TABLE  suppliers (
  sup_id int(11) NOT NULL AUTO_INCREMENT,
  sup_name varchar(255) NOT NULL,
  disabled tinyint(1) NOT NULL DEFAULT 0,
  detail text NOT NULL, 
 
  PRIMARY KEY (sup_id)
)
ENGINE = InnoDB,
CHARACTER SET utf8 ;


CREATE TABLE  supitems (
  supitem_id int(11) NOT NULL AUTO_INCREMENT,
  item_id int(11) NOT NULL,
  sup_id int(11) NOT NULL,
  quantity decimal(10, 3) NOT NULL DEFAULT 0,
  price decimal(10, 2) NOT NULL DEFAULT 0,
  sup_code varchar(255) DEFAULT NULL,
  comment varchar(255) DEFAULT NULL,
  PRIMARY KEY (supitem_id)
)
ENGINE = InnoDB,
COLLATE utf8_general_ci;



DROP VIEW supitems_view  ;

CREATE
VIEW supitems_view
AS
SELECT
  `s`.`supitem_id` AS `supitem_id`,
  `s`.`item_id` AS `item_id`,
  `s`.`sup_id` AS `sup_id`,
  `s`.`quantity` AS `quantity`,
  `s`.`price` AS `price`,
  `s`.`sup_code` AS `sup_code`,
  `s`.`comment` AS `comment`,
  `i`.`itemname` AS `itemname`,
  `i`.`item_code` AS `item_code`,
  `i`.`detail` AS `detail`,
  `sp`.`sup_name` AS `sup_name`
FROM ((`supitems` `s`
  JOIN `items` `i`
    ON ((`s`.`item_id` = `i`.`item_id`)))
  JOIN `suppliers` `sp`
    ON ((`s`.`sup_id` = `sp`.`sup_id`)));