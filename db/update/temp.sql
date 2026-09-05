SET NAMES 'utf8'; 


ALTER TABLE users ADD otpcode int DEFAULT NULL ;
ALTER TABLE store_stock ADD tag int DEFAULT NULL ;


DROP  VIEW users_view; 

CREATE
VIEW users_view
AS
SELECT
  users.user_id AS user_id,
  users.userlogin AS userlogin,
  users.userpass AS userpass,
  users.createdon AS createdon,
  users.email AS email,
  users.acl AS acl,
  users.options AS options,
  users.disabled AS disabled,
  users.lastactive AS lastactive,
  roles.rolename AS rolename,
  users.role_id AS role_id,
  roles.acl AS roleacl,
  roles.otpcode AS otpcode,
  COALESCE(employees.employee_id, 0) AS employee_id,
  (CASE WHEN ISNULL(employees.emp_name) THEN users.userlogin ELSE employees.emp_name END) AS username
FROM ((users
  LEFT JOIN employees
    ON (((users.userlogin = employees.login)
    AND (employees.disabled <> 1))))
  LEFT JOIN roles
    ON ((users.role_id = roles.role_id))) ;





 
DROP  VIEW store_stock_view; 
 
CREATE VIEW store_stock_view
AS
SELECT
  st.stock_id AS stock_id,
  st.item_id AS item_id,
  st.partion AS partion,
  st.store_id AS store_id,
  st.customer_id AS customer_id,
  st.emp_id AS emp_id,
  st.tag AS tag,
  i.itemname AS itemname,
  i.item_code AS item_code,
  i.cat_id AS cat_id,
  i.msr AS msr,
  i.item_type AS item_type,
  i.bar_code AS bar_code,
  i.cat_name AS cat_name,
  i.disabled AS itemdisabled,
  stores.storename AS storename,
  st.qty AS qty,
  st.snumber AS snumber,
  st.sdate AS sdate,
  employees.emp_name AS emp_name
FROM  store_stock st
  JOIN items_view i
    ON  i.item_id = st.item_id  AND  i.disabled <> 1 
  JOIN stores
    ON  stores.store_id = st.store_id  AND  stores.disabled <> 1 
  LEFT JOIN employees
    ON  employees.employee_id  = st.emp_id ;
    
     
 
 

 
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
 
 
 
 
delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','8.3.0'); 

