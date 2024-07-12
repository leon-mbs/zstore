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
  e.event_id AS event_id,
  e.user_id AS user_id,
  e.eventdate AS eventdate,
  e.title AS title,
  e.description AS description,
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
    
    
ALTER TABLE store_stock ADD customer_id int(11) DEFAULT NULL;    

DROP VIEW store_stock_view;

CREATE
VIEW store_stock_view
AS
SELECT
  st.stock_id AS stock_id,
  st.item_id AS item_id,
  st.partion AS partion,
  st.store_id AS store_id,
  st.customer_id AS customer_id,
  i.itemname AS itemname,
  i.item_code AS item_code,
  i.cat_id AS cat_id,
  i.msr AS msr,
  i.item_type AS item_type,
  i.bar_code AS bar_code,
  i.cat_name AS cat_name,
  i.disabled AS itemdisabled,
  stores.storename AS storename,
  st.qty AS qty,
  st.snumber AS snumber,
  st.sdate AS sdate
FROM ((store_stock st
  JOIN items_view i
    ON (((i.item_id = st.item_id)
    AND (i.disabled <> 1))))
  JOIN stores
    ON ((stores.store_id = st.store_id))) ;

DROP TABLE IF EXISTS custacc;
    
CREATE TABLE custacc (
  ca_id bigint(20) NOT NULL AUTO_INCREMENT,
  customer_id int(11) NOT NULL,
  document_id int(11) DEFAULT NULL,
  optype tinyint(4) NULL,
  amount decimal(10, 2) NOT NULL,
  createdon date DEFAULT NULL,
  PRIMARY KEY (ca_id),
  KEY customer_id (customer_id),
  KEY document_id (document_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;    
    
DROP VIEW IF EXISTS custacc_view;    
    
CREATE
VIEW custacc_view
AS
SELECT
  e.ca_id AS ca_id,
  e.customer_id AS customer_id,
  e.document_id AS document_id,
  e.optype AS optype,
  d.notes AS notes,
  e.amount AS amount,
  COALESCE(e.createdon, d.document_date) AS createdon,
  d.document_number AS document_number,
  c.customer_name AS customer_name
FROM ((custacc e
  LEFT JOIN documents d
    ON ((d.document_id = e.document_id)))
  JOIN customers c
    ON ((c.customer_id = e.customer_id))) ;
    
  

INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Повернення з виробництва', 'ProdReturn', 'Виробництво', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Комісійні товари', 'ItemComission', 'Закупівлі', 0);
  
delete  from  options where  optname='version' ;
insert  into options (optname,optvalue) values('version','6.11.0'); 
