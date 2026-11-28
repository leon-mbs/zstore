SET NAMES 'utf8mb4'; 


 
ALTER TABLE documents ADD tag int DEFAULT 0 ;
ALTER TABLE crontask ADD state int DEFAULT 0
  
 

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
   
  metadata.meta_name AS meta_name,
  metadata.description AS meta_desc
FROM documents d
  LEFT JOIN users_view u
    ON d.user_id = u.user_id
  LEFT JOIN customers c
    ON d.customer_id = c.customer_id
  JOIN metadata
    ON metadata.meta_id = d.meta_id
  ;
   
 
 
delete from metadata where  meta_name='Import' ;
delete from metadata where  meta_name='Export' ;

delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','8.3.0'); 

