SET NAMES 'utf8'; 


ALTER TABLE users ADD otpcode int DEFAULT NULL ;
ALTER TABLE store_stock ADD tag int DEFAULT NULL ;
 
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
 
история  подписок
 
delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','8.3.0'); 

