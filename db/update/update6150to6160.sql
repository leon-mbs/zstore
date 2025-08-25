
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

