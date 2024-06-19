SET NAMES 'utf8';


ALTER TABLE entrylist ADD createdon DATE DEFAULT NULL ;

DROP VIEW entrylist_view;
    
CREATE VIEW entrylist_view 
AS
SELECT
  entrylist.entry_id AS entry_id,
  entrylist.document_id AS document_id,
  entrylist.quantity AS quantity,
  documents.customer_id AS customer_id,
  entrylist.stock_id AS stock_id,
  entrylist.service_id AS service_id,
  entrylist.tag AS tag,
  entrylist.createdon AS createdon,
  store_stock.item_id AS item_id,
  store_stock.partion AS partion,
  case when entrylist.createdon  is NULL  then documents.document_date else entrylist.createdon  end      AS document_date,
  entrylist.outprice AS outprice
FROM ((entrylist
  LEFT JOIN store_stock
    ON ((entrylist.stock_id = store_stock.stock_id)))
  JOIN documents
    ON ((entrylist.document_id = documents.document_id)));    
    
    

ALTER TABLE eventlist ADD createdby int(11) DEFAULT NULL;
    
DROP VIEW eventlist_view;

CREATE VIEW eventlist_view
AS
SELECT
  e.user_id AS user_id,
  e.eventdate AS eventdate,
  e.title AS title,
  e.description AS description,
  e.event_id AS event_id,
  e.customer_id AS customer_id,
  e.isdone AS isdone,
  e.createdby AS createdby,
  e.event_type AS event_type,
  e.details AS details,
  c.customer_name AS customer_name,
  uv.username AS username,
  uv2.username AS createdname
FROM ((eventlist e
  LEFT JOIN customers c
    ON (e.customer_id = c.customer_id))
  LEFT JOIN users_view uv
    ON ((uv.user_id = e.user_id))      
  LEFT JOIN users_view uv2
    ON ((uv2.user_id = e.createdby))) ;    
    
    

INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Повернення з виробництва', 'ProdReturn', '', 0);
  
delete  from  options where  optname='version' ;
insert  into options (optname,optvalue) values('version','6.11.0'); 
