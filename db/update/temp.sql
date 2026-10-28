SET NAMES 'utf8mb4'; 


ALTER TABLE store_stock ADD tag int DEFAULT 0 ;
ALTER TABLE documents ADD tag int DEFAULT 0 ;
ALTER TABLE crontask ADD state int DEFAULT 0 ;
  
 
DROP  VIEW store_stock_view; 
 
CREATE VIEW store_stock_view
AS
SELECT
  st.stock_id AS stock_id,
  st.item_id AS item_id,
  st.partion AS partion,
  st.store_id AS store_id,
  st.customer_id AS customer_id,
  st.emp_id AS emp_id,
  st.tag AS tag,
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
  st.sdate AS sdate,
  employees.emp_name AS emp_name
FROM  store_stock st
  JOIN items_view i
    ON  i.item_id = st.item_id  AND  i.disabled <> 1 
  JOIN stores
    ON  stores.store_id = st.store_id  AND  stores.disabled <> 1 
  LEFT JOIN employees
    ON  employees.employee_id  = st.emp_id ;
    

DROP  VIEW documents_view;     
    
CREATE VIEW documents_view
AS
SELECT
  d.document_id AS document_id,
  d.document_number AS document_number,
  d.document_date AS document_date,
  d.user_id AS user_id,
  d.content AS content,
  d.amount AS amount,
  d.meta_id AS meta_id,
  u.username AS username,
  d.customer_id AS customer_id,
  c.customer_name AS customer_name,
  d.state AS state,
  d.notes AS notes,
  d.payamount AS payamount,
  d.payed AS payed,
  d.parent_id AS parent_id,
  d.tag AS tag,
  d.branch_id AS branch_id,
  b.branch_name AS branch_name,
 
  metadata.meta_name AS meta_name,
  metadata.description AS meta_desc
FROM documents d
  LEFT JOIN users_view u
    ON d.user_id = u.user_id
  LEFT JOIN customers c
    ON d.customer_id = c.customer_id
  JOIN metadata
    ON metadata.meta_id = d.meta_id
  LEFT JOIN branches b
    ON d.branch_id = b.branch_id ;
   
  
 
DROP  VIEW acc_entry_view; 
 
CREATE VIEW acc_entry_view
AS
SELECT
  e.id AS id,
 
  e.accdt AS accdt,
  e.accct AS accct,
  e.amount AS amount,
  case when e.createdon  is NULL  then d.document_date else e.createdon  end      AS createdon,
    
  d.notes AS notes,
  e.document_id AS document_id,
  d.branch_id AS branch_id,
  e.tagdt AS tagdt,
  e.tagct AS tagct,
   
  d.document_number AS document_number
FROM  acc_entry e
  JOIN documents d
    ON  d.document_id = e.document_id ; 
 
DROP  VIEW docstatelog_view; 

CREATE VIEW docstatelog_view
AS
SELECT
  dl.log_id AS log_id,
  dl.user_id AS user_id,
  dl.document_id AS document_id,
  dl.docstate AS docstate,
  dl.createdon AS createdon,
  dl.hostname AS hostname,
  u.username AS username,
  d.document_number AS document_number 
 
 
FROM ((docstatelog dl
  LEFT JOIN users_view u
    ON ((dl.user_id = u.user_id)))
  JOIN documents d
    ON ((d.document_id = dl.document_id))) ;

 
 
delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','8.3.0'); 

