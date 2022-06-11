CREATE TABLE shop_vars (
  var_id int(11) NOT NULL AUTO_INCREMENT,
  attr_id int(11) NOT NULL,
  varname varchar(255) DEFAULT NULL,
  PRIMARY KEY (var_id)
 
  
)
ENGINE = innodb DEFAULT CHARSET=utf8;


CREATE TABLE  shop_varitems (
  `varitem_id` int(11) NOT NULL AUTO_INCREMENT,
  `var_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  PRIMARY KEY (`varitem_id`),
  KEY `item_id` (`item_id`),
  KEY `var_id` (`var_id`)
)
ENGINE = innodb DEFAULT CHARSET=utf8; 
 

CREATE VIEW shop_vars_view
AS
    SELECT
      `shop_vars`.`var_id` AS `var_id`,
      `shop_vars`.`attr_id` AS `attr_id`,
      `shop_vars`.`varname` AS `varname`,
      `shop_attributes`.`attributename` AS `attributename`,
      `shop_attributes`.`cat_id`   ,
      (select count(*) from shop_varitems where  shop_varitems.var_id=shop_vars.var_id ) as cnt
    FROM ((`shop_vars`
      JOIN `shop_attributes`
        ON ((`shop_vars`.`attr_id` = `shop_attributes`.`attribute_id`)))
      JOIN `item_cat`
        ON ((`shop_attributes`.`cat_id` = `item_cat`.`cat_id`)));

        
       
        
CREATE VIEW shop_varitems_view
AS
SELECT
  `shop_varitems`.`varitem_id` AS `varitem_id`,
  `shop_varitems`.`var_id` AS `var_id`,
  `shop_varitems`.`item_id` AS `item_id`,
  `sv`.`attr_id` AS `attr_id`,
  `sa`.`attributevalue` AS `attributevalue`,
  `it`.`itemname` AS `itemname`,
  `it`.`item_code` AS `item_code`
FROM (((`shop_varitems`
  JOIN `shop_vars` `sv`
    ON ((`shop_varitems`.`var_id` = `sv`.`var_id`)))
  JOIN `shop_attributevalues` `sa`
    ON (((`sa`.`item_id` = `shop_varitems`.`item_id`)
    AND (`sv`.`attr_id` = `sa`.`attribute_id`))))
  JOIN `items` `it`
    ON ((`shop_varitems`.`item_id` = `it`.`item_id`)));      