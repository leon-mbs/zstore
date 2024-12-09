 
CREATE TABLE  eqentry (
  id int NOT NULL AUTO_INCREMENT,
  eq_id int NOT NULL,
  updatedon date NOT NULL,
  optype smallint NOT NULL,
  amount decimal(10, 2) DEFAULT NULL,
  emp_id int DEFAULT NULL,
  pa_id int DEFAULT NULL,
  document_id int DEFAULT NULL,
  KEY (eq_id) ,
  PRIMARY KEY (id)
) ENGINE = INNODB DEFAULT CHARSET = utf8 ;  

 

CREATE
VIEW eqentry_view
AS
SELECT
  e.id AS id,
  e.eq_id AS eq_id,
 COALESCE(d.document_date, e.updatedon) AS updatedon,
  e.optype AS optype,
  e.amount AS amount,
  e.emp_id AS emp_id,
  e.pa_id AS pa_id,
  e.document_id AS document_id,
  d.document_number AS document_number,
  em.emp_name AS emp_name,
  pa.pa_name AS pa_name
FROM ((((eqentry e
  JOIN equipments eq
    ON ((e.eq_id = eq.eq_id)))
  LEFT JOIN employees em
    ON ((e.emp_id = em.employee_id)))
  LEFT JOIN parealist pa
    ON ((e.pa_id = pa.pa_id)))
  LEFT JOIN documents d
    ON ((e.document_id = d.document_id)));
    
    
    DROP VIEW if exists cust_acc_view;