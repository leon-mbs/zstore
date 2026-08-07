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
 
CREATE TABLE subscribe_history (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  sub_id int(11) NOT NULL,
 
  createdon date  ,
  PRIMARY KEY (id),
  KEY customer_id (sub_id),
 
) ENGINE = INNODB  DEFAULT CHARSET = utf8;   


CREATE VIEW subscribe_history_view
AS
SELECT
  h.id AS id,
  h.sub_id AS sub_id 
  h.createdon AS createdon 
  
FROM subscribe_history h 
  LEFT JOIN subscribes s
    ON  h.sub_id = s.sub_id 
  ;    
    
    
 
delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','8.3.0'); 

