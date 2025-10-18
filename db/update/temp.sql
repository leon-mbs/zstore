  
 alter
VIEW documents_view
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
  d.branch_id AS branch_id,
  b.branch_name AS branch_name,
    
  case 
    when d.state=9 then 1 
    when d.state=15 then 3  
    when d.state=22 then 15  
    when d.state=18 then 20  
    when d.state=14 then 30  
    when d.state=16 then 40  
    when d.state in(7,11,20) then 40  
    when d.state =3  then 70  
    when d.state = 21 then 75  
 
    when d.state in(19,2) then 80  
    when d.state = 8 then 90
    when d.state = 1 then 100
         
    else 50 end  AS priority ,
    
  d.lastupdate AS lastupdate,
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
 
 priory 
  
  
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  3, 'Реєстр ПН', 'TaxInvoiceList', '',    0);
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  1, 'Податкова накладна', 'TaxInvoice', 'Продажі',    0);
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  1, 'Додаток2 до ПН', 'TaxInvoice2', 'Продажі',    0);
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  1, 'Вхідна ПН', 'TaxInvoiceIncome', 'Закупівлі',     0);

 
 
******************************************* 
 
 
 
 
CREATE TABLE acc_entry (
  id bigint NOT NULL AUTO_INCREMENT,
  createdon DATE DEFAULT NULL,
  accdt varchar(4)  NULL,
  accct varchar(4)  NULL,
  amount decimal(11, 2) NOT NULL,
  document_id int NOT NULL,
  notes varchar(255)  NULL,
  tag int    NULL   ,
 
  PRIMARY KEY (id) ,
  KEY document_id (document_id),
  KEY accdt (accdt),
  KEY accct (accct),
  CONSTRAINT accentrylist_ibfk_1 FOREIGN KEY (document_id) REFERENCES documents (document_id) 
  
) ENGINE = INNODB  DEFAULT CHARSET = utf8  ; 
 
  

CREATE VIEW acc_entry_view
AS
SELECT
  e.id AS id,
 
  e.accdt AS accdt,
  e.accct AS accct,
  e.amount AS amount,
   case when e.createdon  is NULL  then d.document_date else e.createdon  end      AS createdon,
   case when e.notes  is NULL  then d.notes else e.notes  end      AS notes,
  
  e.document_id AS document_id,
  d.branch_id AS branch_id,
  e.tag AS tag,
   
  d.document_number AS document_number
FROM  acc_entry e
  JOIN documents d
    ON  d.document_id = e.document_id ;


    
    
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 4, 'План рахункiв', 'AccountList', 'Бухоблiк',   0 );
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 3, 'Журнал проводок', 'AccountEntryList', 'Бухоблiк',   0 );
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 2, 'Рух по рахунку', 'AccountActivity', 'Бухоблiк',     0);
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 1, 'Ручна проводка', 'ManualEntry', 'Бухоблiк',   0);
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 2, 'Оборотно-сальдова вiдомiсть', 'ObSaldo', 'Бухоблiк',     0);
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 2, 'Шахматна вiдомiсть', 'Shahmatka', 'Бухоблiк',   0 );
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 2, 'Фiн. звiт малого  пiдприємства', 'FinReportSmall', 'Бухоблiк',  0, 0);
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 1, 'Закриття перiоду', 'FinResult', 'Бухоблiк',   0);

30 31
36 63

20 по  складу
26 28 24 22
23  по  списанию и оприходованию

10 15 13

оставить с 64

66
