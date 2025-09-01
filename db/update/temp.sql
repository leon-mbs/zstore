 

ALTER TABLE empacc ADD notes varchar(255) DEFAULT NULL;


DROP VIEW empacc_view ;

CREATE VIEW empacc_view
AS
SELECT
  e.ea_id AS ea_id,
  e.emp_id AS emp_id,
  e.document_id AS document_id,
  e.optype AS optype,
  case when e.notes is not null then e.notes else d.notes end AS notes,
  e.amount AS amount,
  COALESCE(e.createdon, d.document_date) AS createdon,
  d.document_number AS document_number,
  em.emp_name AS emp_name
FROM ((empacc e
  LEFT JOIN documents d
    ON ((d.document_id = e.document_id)))
  JOIN employees em
    ON ((em.employee_id = e.emp_id))) ;


INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  3, 'Реєстр ПН', 'TaxInvoiceList', '',    0);
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  1, 'Податкова накладна', 'TaxInvoice', 'Продажі',    0);
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  1, 'Додаток2 до ПН', 'TaxInvoice2', 'Продажі',    0);
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  1, 'Вхідна ПН', 'TaxInvoiceIncome', 'Закупівлі',     0);

delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','6.16.0'); 

 
 
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
