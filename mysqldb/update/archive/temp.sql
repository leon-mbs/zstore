 
ALTER TABLE `stores` ADD `disabled` tinyint(1)   DEFAULT 0  ;
ALTER TABLE `mfund`  ADD `disabled` tinyint(1)   DEFAULT 0  ;


INSERT INTO `metadata` (`meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 2, 'OLAP аналіз', 'OLAP', 'Ааналітика', 0);
UPDATE `metadata`  set  menugroup ='Ааналітика' where meta_name = 'ABC' ; 

 


SELECT iv.itemname,
ssv.storename,
iv.cat_name,
COALESCE(c.customer_name,'Фіз. особа') AS customer_name, 
concat(month(dv.document_date),'-',year(dv.document_date)) as document_date ,
COALESCE(b.branch_name,'') AS branch_name,
COALESCE(f.firm_name,'') AS firm_name,
COALESCE(uv.username ,'') AS username,
COALESCE(ev.partion,0) AS partion, 
COALESCE(ev.outprice,0) AS outprice   
FROM entrylist_view ev   
JOIN documents dv ON ev.document_id = dv.document_id
JOIN items_view iv ON ev.item_id = iv.item_id
JOIN store_stock_view ssv ON ev.stock_id = ssv.stock_id
LEFT JOIN customers c ON dv.customer_id = c.customer_id
LEFT JOIN users_view uv  ON dv.user_id = uv.user_id 
LEFT JOIN firms f ON dv.firm_id = f.firm_id 
LEFT JOIN branches b ON dv.branch_id = b.branch_id


concat(DATE_PART( 'month',dv.document_date),'-',DATE_PART('year',dv.document_date)) as document_date ,
 