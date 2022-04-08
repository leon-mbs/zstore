INSERT INTO `metadata` (  `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES(  3, 'Товари у  постачальникIв', 'CustItems', '', 0);
 


CREATE TABLE custitems (
  custitem_id int(11) NOT NULL AUTO_INCREMENT,
  item_id int(11) NOT NULL,
  customer_id int(11) NOT NULL,
  quantity decimal(10, 3) NOT NULL DEFAULT 0.000,
  price decimal(10, 2) NOT NULL DEFAULT 0.00,
  cust_code varchar(255) DEFAULT NULL,
  comment varchar(255) DEFAULT NULL,
  PRIMARY KEY (custitem_id)
)
ENGINE = InnoDB,
COLLATE utf8_general_ci;



DROP VIEW custitems_view  ;

CREATE
VIEW custitems_view
AS
SELECT
  `s`.`custitem_id` AS `custitem_id`,
  `s`.`item_id` AS `item_id`,
  `s`.`customer_id` AS `customer_id`,
  `s`.`quantity` AS `quantity`,
  `s`.`price` AS `price`,
  `s`.`cust_code` AS `cust_code`,
  `s`.`comment` AS `comment`,
  `i`.`itemname` AS `itemname`,
  `i`.`item_code` AS `item_code`,
  `i`.`detail` AS `detail`,
  `c`.`customer_name` AS `customer_name`
FROM ((`custitems` `s`
  JOIN `items` `i`
    ON ((`s`.`item_id` = `i`.`item_id`)))
  JOIN `customers` `c`
    ON ((`s`.`customer_id` = `c`.`customer_id`)))
WHERE ((`i`.`disabled` <> 1)
AND (`c`.`status` <> 1));