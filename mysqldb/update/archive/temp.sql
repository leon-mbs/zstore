 
ALTER TABLE `stores` ADD `disabled` tinyint(1)   DEFAULT 0  ;
ALTER TABLE `mfund`  ADD `disabled` tinyint(1)   DEFAULT 0  ;



 


SELECT iv.itemname,
ssv.storename,
iv.cat_name,
COALESCE(c.customer_name,'Фіз. особа') AS customer_name, 
dv.document_date ,
COALESCE(b.branch_name,'') AS branch_name,
COALESCE(ev.partion,0) AS partion, 
COALESCE(ev.outprice,0) AS outprice   
FROM entrylist_view ev   
JOIN documents dv ON ev.document_id = dv.document_id
JOIN items_view iv ON ev.item_id = iv.item_id
JOIN store_stock_view ssv ON ev.stock_id = ssv.stock_id
LEFT JOIN customers c ON dv.customer_id = c.customer_id
LEFT JOIN branches b ON dv.branch_id = b.branch_id     