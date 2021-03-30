ALTER TABLE `entrylist` CHANGE `extcode` `extcode` DECIMAL(11,2) NULL DEFAULT 0; 


ALTER VIEW entrylist_view AS 
  select 
    entrylist.entry_id AS entry_id,
    entrylist.document_id AS document_id,
    entrylist.amount AS amount,
    entrylist.quantity AS quantity,
    documents.customer_id AS customer_id,
    entrylist.extcode AS extcode,
    entrylist.stock_id AS stock_id,
    entrylist.service_id AS service_id,
    store_stock.item_id AS item_id,
    store_stock.partion AS partion,
    documents.document_date AS document_date 
  from 
    ((entrylist left join store_stock on((entrylist.stock_id = store_stock.stock_id))) join documents on((entrylist.document_id = documents.document_id)));

ALTER TABLE `item_cat` ADD `parent_id` INT   NULL DEFAULT '0'  ;   



ALTER TABLE `shop_attributes` CHANGE `group_id` `cat_id` INT(11) NOT NULL;
ALTER TABLE `shop_attributevalues` CHANGE `product_id` `item_id` INT(11) NOT  NULL;
ALTER TABLE `shop_prod_comments` CHANGE `product_id` `item_id` INT(11) NOT NULL;
 
    
ALTER TABLE `shop_attributes` CHANGE `valueslist` `valueslist` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;    
ALTER TABLE `shop_attributes` ADD `showincompare` TINYINT(1) NOT NULL DEFAULT '0'  ;    

ALTER VIEW shop_attributes_view AS 
  select 
    shop_attributes.attribute_id AS attribute_id,
    shop_attributes.attributename AS attributename,
    shop_attributes.cat_id AS cat_id,
    shop_attributes.attributetype AS attributetype,
    shop_attributes.valueslist AS valueslist,
    shop_attributes.showinlist AS showinlist,
    shop_attributes.showincompare AS showincompare,
    shop_attributes_order.ordern AS ordern 
  from 
    (shop_attributes join shop_attributes_order on(((shop_attributes.attribute_id = shop_attributes_order.attr_id) and (shop_attributes.cat_id = shop_attributes_order.pg_id)))) 
  order by 
    shop_attributes_order.ordern;

    
