ALTER TABLE equipments ADD pa_id int(11) DEFAULT  NULL;
ALTER TABLE equipments ADD emp_id int(11) DEFAULT  NULL;
ALTER TABLE equipments ADD invnumber  varchar(255) NOT NULL;


 
CREATE
VIEW equipments_view
AS
SELECT
  e.eq_id AS eq_id,
  e.eq_name AS eq_name,
  e.detail AS detail,
  e.disabled AS disabled,
  e.description AS description,
  e.branch_id AS branch_id,
  e.invnumber AS invnumber,
  e.pa_id AS pa_id,
  e.emp_id AS emp_id,
  p.pa_name AS pa_name,
  employees.emp_name AS emp_name
FROM ((equipments e
  LEFT JOIN employees
    ON ((employees.employee_id = e.emp_id)))
  LEFT JOIN parealist p
    ON ((p.pa_id = e.pa_id))); 
 
id 
eq_id
updated
emp_id
pa_id
document_id
qtype
amount
notes

delete  from  options where  optname='version' ;
insert  into options (optname,optvalue) values('version','6.12.0'); 

