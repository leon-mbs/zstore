SET NAMES 'utf8'; 

убрать  amount c меток

ALTER TABLE iostate ADD iodate DATE DEFAULT NULL;


DROP VIEW iostate_view CASCADE;

CREATE VIEW iostate_view
AS
SELECT
  s.id AS id,
  s.document_id AS document_id,
  s.iotype AS iotype,
  s.amount AS amount,
  coalesce(s.iodate, d.document_date) AS document_date,
  d.branch_id AS branch_id
FROM (iostate s
  JOIN documents d
    ON ((s.document_id = d.document_id)));  

delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','8.2.0'); 
