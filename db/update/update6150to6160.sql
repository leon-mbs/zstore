 

ALTER TABLE empacc ADD notes varchar(255) DEFAULT NULL;
ALTER TABLE contracts ADD state int(6) DEFAULT 0;
ALTER TABLE files ADD user_id int  DEFAULT NULL;


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

DROP VIEW contracts_view ;    
    
CREATE VIEW contracts_view
AS
SELECT
  co.contract_id AS contract_id,
  co.customer_id AS customer_id,
 
  co.createdon AS createdon,
  co.contract_number AS contract_number,
  co.state AS state,
 
  co.details AS details,
  cu.customer_name AS customer_name

FROM contracts co
  JOIN customers cu
    ON co.customer_id = cu.customer_id  ;    
    
    
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  2, 'Закриття дня', 'EndDay', '',     0);
    
 

delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','6.16.0'); 
