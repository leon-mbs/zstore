SET NAMES 'utf8';

CREATE TABLE branches (
  branch_id int(11) NOT NULL AUTO_INCREMENT,
  branch_name varchar(255) NOT NULL,
  details longtext NOT NULL,
  disabled tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (branch_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;


CREATE TABLE contracts (
  contract_id int(11) NOT NULL AUTO_INCREMENT,
  customer_id int(11) DEFAULT 0,
  
  createdon date NOT NULL,
  contract_number varchar(64) NOT NULL,
  state int(6) DEFAULT 0,
  details longtext NOT NULL,
  PRIMARY KEY (contract_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE custitems (
  custitem_id int(11) NOT NULL AUTO_INCREMENT,
  item_id int(11)   NULL,
  customer_id int(11) NOT NULL,
  quantity decimal(10, 3) DEFAULT NULL,
  price decimal(10, 2) NOT NULL DEFAULT 0,
  cust_code varchar(255) NOT NULL,
  cust_name varchar(255) NOT NULL,
  brand varchar(255) NOT NULL,
  store varchar(255) NOT NULL,
  bar_code varchar(64) NOT NULL,
  details TEXT DEFAULT NULL,
  updatedon date NOT NULL,
  PRIMARY KEY (custitem_id),
  KEY customer_id (customer_id),
  KEY item_id (item_id)
 
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE customers (
  customer_id int(11) NOT NULL AUTO_INCREMENT,
  customer_name varchar(255) DEFAULT NULL,
  detail mediumtext NOT NULL,
  email varchar(64) DEFAULT NULL,
  phone varchar(64) DEFAULT NULL,
  status smallint(4) NOT NULL DEFAULT 0,
  city varchar(255) DEFAULT NULL,
  leadstatus varchar(255) DEFAULT NULL,
  leadsource varchar(255) DEFAULT NULL,
  createdon date DEFAULT NULL,
  country varchar(255) DEFAULT NULL,
  passw varchar(255) DEFAULT NULL,
  PRIMARY KEY (customer_id),
  KEY phone (phone)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE docstatelog (
  log_id bigint(20) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  document_id int(11) NOT NULL,
  docstate smallint(6) NOT NULL,
  createdon datetime NOT NULL,
  hostname varchar(64) NOT NULL,
  PRIMARY KEY (log_id),
  KEY document_id (document_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE users (
  user_id int(11) NOT NULL AUTO_INCREMENT,
  userlogin varchar(32) NOT NULL,
  userpass varchar(255) NOT NULL,
  createdon date NOT NULL,
  email varchar(255) DEFAULT NULL,
  acl mediumtext NOT NULL,
  disabled int(1) NOT NULL DEFAULT 0,
  options longtext,
  role_id int(11) DEFAULT NULL,
  lastactive datetime DEFAULT NULL,
  PRIMARY KEY (user_id),
  UNIQUE KEY userlogin (userlogin)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;


CREATE TABLE documents (
  document_id int(11) NOT NULL AUTO_INCREMENT,
  document_number varchar(45) NOT NULL,
  document_date date NOT NULL,
  user_id int(11) NOT NULL,
  content longtext,
  amount decimal(11, 2) DEFAULT NULL,
  meta_id int(11) NOT NULL,
  state tinyint(4) NOT NULL,
  notes varchar(1024)  NULL,
  customer_id int(11) DEFAULT 0,
  payamount decimal(11, 2) DEFAULT 0,
  payed decimal(11, 2) DEFAULT 0,
  branch_id int(11) DEFAULT 0,
  parent_id bigint(20) DEFAULT 0,
   
 
  lastupdate datetime DEFAULT NULL,
  PRIMARY KEY (document_id),

  KEY document_date (document_date),
  KEY customer_id (customer_id),
  KEY user_id (user_id),
  KEY branch_id (branch_id),
  KEY parent_id (parent_id),
  
  KEY document_number (document_number),
  KEY state (state),
  CONSTRAINT documents_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (user_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE items (
  item_id int(11) NOT NULL AUTO_INCREMENT,
  itemname varchar(255) DEFAULT NULL,
  description longtext,
  detail longtext NOT NULL,
  item_code varchar(64) DEFAULT NULL,
  bar_code varchar(64) DEFAULT NULL,
  cat_id int(11) NOT NULL,
  msr varchar(64) DEFAULT NULL,
  disabled tinyint(1) DEFAULT 0,
  minqty decimal(11, 3) DEFAULT 0,
  manufacturer varchar(355) DEFAULT NULL,
  item_type int(11) DEFAULT NULL,
  PRIMARY KEY (item_id),
  KEY item_code (item_code),
  KEY itemname (itemname),
  KEY cat_id (cat_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE stores (
  store_id int(11) NOT NULL AUTO_INCREMENT,
  storename varchar(64) DEFAULT NULL,
  description varchar(255) DEFAULT NULL,
  branch_id int(11) DEFAULT 0,
  disabled tinyint(1) DEFAULT 0,
  PRIMARY KEY (store_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE store_stock (
  stock_id int(11) NOT NULL AUTO_INCREMENT,
  item_id int(11) NOT NULL,
  partion decimal(11, 2) DEFAULT NULL,
  store_id int(11) NOT NULL,
  customer_id int(11) DEFAULT NULL,
  emp_id int(11) DEFAULT NULL,
  qty decimal(11, 3) DEFAULT 0,
  snumber varchar(64) DEFAULT NULL,
  sdate date DEFAULT NULL,
  PRIMARY KEY (stock_id),
  KEY item_id (item_id),
  KEY emp_id (emp_id),
  KEY store_id (store_id),
  CONSTRAINT store_stock_fk FOREIGN KEY (store_id) REFERENCES stores (store_id),
  CONSTRAINT store_stock_ibfk_1 FOREIGN KEY (item_id) REFERENCES items (item_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;




CREATE TABLE empacc (
  ea_id int(11) NOT NULL AUTO_INCREMENT,
  emp_id int(11) NOT NULL,
  document_id int(11) DEFAULT NULL,
  optype int(11) DEFAULT NULL,
  notes varchar(255) DEFAULT NULL, 
  amount decimal(10, 2) NOT NULL,
  createdon date DEFAULT NULL,
  PRIMARY KEY (ea_id),
  KEY emp_id (emp_id),
  KEY document_id (document_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE employees (
  employee_id int(11) NOT NULL AUTO_INCREMENT,
  login varchar(64) DEFAULT NULL,
  detail mediumtext,
  disabled tinyint(1) DEFAULT 0,
  emp_name varchar(64) NOT NULL,
  branch_id int(11) DEFAULT 0,
  KEY (login) ,
  PRIMARY KEY (employee_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE entrylist (
  entry_id bigint(20) NOT NULL AUTO_INCREMENT,
  document_id int(11) NOT NULL,
  quantity decimal(11, 3) DEFAULT 0,
  cost decimal(11, 2) DEFAULT 0,
  stock_id int(11) DEFAULT NULL,
  service_id int(11) DEFAULT NULL,
  outprice decimal(10, 2) DEFAULT NULL,
  createdon DATE DEFAULT NULL,
  tag int(11) DEFAULT 0,
  PRIMARY KEY (entry_id),
  KEY document_id (document_id),
  KEY stock_id (stock_id),
  CONSTRAINT entrylist_ibfk_1 FOREIGN KEY (document_id) REFERENCES documents (document_id),
  CONSTRAINT entrylist_ibfk_2 FOREIGN KEY (stock_id) REFERENCES store_stock (stock_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;


DELIMITER ;;
CREATE TRIGGER entrylist_after_ins_tr
    AFTER INSERT
    ON entrylist
    FOR EACH ROW
BEGIN

  IF NEW.stock_id > 0 THEN

    UPDATE store_stock
    SET qty = (SELECT
        COALESCE(SUM(quantity), 0)
      FROM entrylist
      WHERE stock_id = NEW.stock_id)
    WHERE store_stock.stock_id = NEW.stock_id;

  END IF;

END;;
DELIMITER ;
DELIMITER ;;

CREATE TRIGGER  entrylist_after_del_tr
    AFTER DELETE
    ON  entrylist
    FOR EACH ROW
BEGIN


  IF OLD.stock_id > 0 THEN

    UPDATE store_stock
    SET qty = (SELECT
        COALESCE(SUM(quantity), 0)
      FROM entrylist
      WHERE stock_id = OLD.stock_id)
    WHERE store_stock.stock_id = OLD.stock_id;

  END IF;

END;;
DELIMITER ;

CREATE TABLE equipments (
  eq_id int(11) NOT NULL AUTO_INCREMENT,
  eq_name varchar(255) DEFAULT NULL,
  detail mediumtext,
  invnumber  varchar(255) DEFAULT NULL,
  disabled tinyint(1) DEFAULT 0,
  type  smallint DEFAULT 0,
  description text,
  branch_id INT NULL,  
  PRIMARY KEY (eq_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE eventlist (
  user_id int(11) DEFAULT NULL,
  eventdate datetime NOT NULL,
  title varchar(255) NOT NULL,
  description text NOT NULL,
  event_id int(11) NOT NULL AUTO_INCREMENT,
  customer_id int(11) DEFAULT NULL,
  isdone tinyint(1) NOT NULL DEFAULT 0,
  event_type tinyint(4) DEFAULT NULL,
  createdby int(11) DEFAULT NULL,
  details text,
  PRIMARY KEY (event_id),
  KEY user_id (user_id),
  KEY customer_id (customer_id)
) ENGINE = INNODB   DEFAULT CHARSET = utf8;

CREATE TABLE files (
  file_id int(11) NOT NULL AUTO_INCREMENT,
  item_id int(11) DEFAULT NULL,
  user_id int(11) DEFAULT NULL,
  filename varchar(255) DEFAULT NULL,
  description varchar(255) DEFAULT NULL,
  item_type int(11) NOT NULL,
  mime varchar(16) DEFAULT NULL,
  PRIMARY KEY (file_id),
  KEY item_id (item_id)
)  ENGINE = INNODB DEFAULT CHARSET = utf8;

CREATE TABLE filesdata (
  file_id int(11) DEFAULT NULL,
  filedata longblob,
  UNIQUE KEY file_id (file_id)
) ENGINE = MYISAM DEFAULT CHARSET = utf8;

 

CREATE TABLE images (
  image_id int(11) NOT NULL AUTO_INCREMENT,
  content longblob NOT NULL,
  mime varchar(16) DEFAULT NULL,
  thumb longblob,
  PRIMARY KEY (image_id)
) ENGINE = MYISAM  DEFAULT CHARSET = utf8;

CREATE TABLE iostate (
  id int(11) NOT NULL AUTO_INCREMENT,
  document_id int(11) NOT NULL,
  iotype smallint(6) NOT NULL,
  amount decimal(10, 2) NOT NULL,
  PRIMARY KEY (id),
  KEY document_id (document_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE issue_history (
  hist_id bigint(20) NOT NULL AUTO_INCREMENT,
  issue_id int(11) NOT NULL,
  createdon date NOT NULL,
  user_id int(11) NOT NULL,
  description varchar(255) NOT NULL,
  PRIMARY KEY (hist_id),
  KEY issue_id (issue_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE issue_issuelist (
  issue_id int(11) NOT NULL AUTO_INCREMENT,
  issue_name varchar(255) NOT NULL,
  details longtext NOT NULL,
  status smallint(6) NOT NULL,
  priority tinyint(4) NOT NULL,
  user_id int(11) NOT NULL,
  lastupdate datetime DEFAULT NULL,
  project_id int(11) NOT NULL,
  PRIMARY KEY (issue_id),
  KEY project_id (project_id),
  KEY user_id (user_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE issue_projectacc (
  id int(11) NOT NULL AUTO_INCREMENT,
  project_id int(11) NOT NULL,
  user_id int(11) NOT NULL,
  PRIMARY KEY (id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE issue_projectlist (
  project_id int(11) NOT NULL AUTO_INCREMENT,
  project_name varchar(255) NOT NULL,
  details longtext NOT NULL,
  customer_id int(11) DEFAULT NULL,
  status smallint(6) DEFAULT NULL,
  PRIMARY KEY (project_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE issue_time (
  id int(11) NOT NULL AUTO_INCREMENT,
  issue_id int(11) NOT NULL,
  createdon datetime NOT NULL,
  user_id int(11) NOT NULL,
  duration decimal(10, 2) DEFAULT NULL,
  notes varchar(255) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY issue_id (issue_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE item_cat (
  cat_id int(11) NOT NULL AUTO_INCREMENT,
  cat_name varchar(255) NOT NULL,
  detail longtext,
  parent_id int(11) DEFAULT 0,
  PRIMARY KEY (cat_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE item_set (
  set_id int(11) NOT NULL AUTO_INCREMENT,
  item_id int(11) DEFAULT 0,
  pitem_id int(11) NOT NULL DEFAULT 0,
  qty decimal(11, 3) DEFAULT 0,
  service_id int(11) DEFAULT 0,
  cost decimal(10, 2) DEFAULT 0 ,
  PRIMARY KEY (set_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;


CREATE TABLE keyval (
  keyd varchar(255) NOT NULL,
  vald text NOT NULL,
  PRIMARY KEY (keyd)
) ENGINE = INNODB DEFAULT CHARSET = utf8;

CREATE TABLE messages (
  message_id bigint(20) NOT NULL AUTO_INCREMENT,
  user_id int(11) DEFAULT NULL,
  created datetime DEFAULT NULL,
  message text,
  item_id int(11) NOT NULL,
  item_type int(11) DEFAULT NULL,
  checked tinyint(1) DEFAULT NULL,
  PRIMARY KEY (message_id),
  KEY item_id (item_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE metadata (
  meta_id int(11) NOT NULL AUTO_INCREMENT,
  meta_type tinyint(11) NOT NULL,
  description varchar(255) DEFAULT NULL,
  meta_name varchar(255) NOT NULL,
  menugroup varchar(255) DEFAULT NULL,
  disabled tinyint(4) NOT NULL,
  KEY (meta_name) ,
  PRIMARY KEY (meta_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE mfund (
  mf_id int(11) NOT NULL AUTO_INCREMENT,
  mf_name varchar(255) NOT NULL,
  description varchar(255) DEFAULT NULL,
  branch_id int(11) DEFAULT 0,
  detail longtext,
  disabled tinyint(1) DEFAULT 0,
  PRIMARY KEY (mf_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE note_fav (
  fav_id int(11) NOT NULL AUTO_INCREMENT,
  topic_id int(11) NOT NULL,
  user_id int(11) NOT NULL,
  PRIMARY KEY (fav_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE note_nodes (
  node_id int(11) NOT NULL AUTO_INCREMENT,
  pid int(11) NOT NULL,
  title varchar(50) NOT NULL,
  mpath varchar(255) CHARACTER SET latin1 NOT NULL,
  user_id int(11) DEFAULT NULL,
  ispublic tinyint(1) DEFAULT 0,
  PRIMARY KEY (node_id),
  KEY user_id (user_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE note_tags (
  tag_id int(11) NOT NULL AUTO_INCREMENT,
  topic_id int(11) NOT NULL,
  tagvalue varchar(255) NOT NULL,
  PRIMARY KEY (tag_id),
  KEY topic_id (topic_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE note_topicnode (
  topic_id int(11) NOT NULL,
  node_id int(11) NOT NULL,
  tn_id int(11) NOT NULL AUTO_INCREMENT,
  islink  tinyint(1) DEFAULT 0,
  PRIMARY KEY (tn_id),
  KEY topic_id (topic_id),
  KEY node_id (node_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE note_topics (
  topic_id int(11) NOT NULL AUTO_INCREMENT,
  title varchar(255) NOT NULL,
  content longtext NOT NULL,
  acctype smallint(4) DEFAULT 0,
  user_id int(11) NOT NULL,
  ispublic   tinyint(1) DEFAULT 0 , 
  PRIMARY KEY (topic_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE notifies (
  notify_id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) DEFAULT NULL,
  dateshow datetime NOT NULL,
  message text,
  sender_id int(11) DEFAULT NULL,
  checked tinyint(1) NOT NULL,
  PRIMARY KEY (notify_id),
  KEY user_id (user_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE options (
  optname varchar(64) NOT NULL,
  optvalue longtext NOT NULL,
  UNIQUE KEY optname (optname)
) ENGINE = INNODB DEFAULT CHARSET = utf8;

CREATE TABLE parealist (
  pa_id int(11) NOT NULL AUTO_INCREMENT,
  pa_name varchar(255) NOT NULL,
  disabled  tinyint(1) DEFAULT 0,
  branch_id  int(11) DEFAULT null,
  notes varchar(255) DEFAULT NULL,
  PRIMARY KEY (pa_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE paylist (
  pl_id bigint(20) NOT NULL AUTO_INCREMENT,
  document_id int(11) NOT NULL,
  amount decimal(11, 2) NOT NULL,
  mf_id int(11) DEFAULT NULL,
  notes varchar(255) DEFAULT NULL,
  paydate datetime DEFAULT NULL,
  user_id int(11) NOT NULL,
  paytype smallint(6) NOT NULL,
  detail longtext,
  bonus int(11) DEFAULT NULL,
  PRIMARY KEY (pl_id),
  KEY document_id (document_id),
  CONSTRAINT paylist_ibfk_1 FOREIGN KEY (document_id) REFERENCES documents (document_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE poslist (
  pos_id int(11) NOT NULL AUTO_INCREMENT,
  pos_name varchar(255) NOT NULL,
  details longtext NOT NULL,
  branch_id int(11) DEFAULT 0,
  PRIMARY KEY (pos_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE ppo_zformrep (
  id int(11) NOT NULL AUTO_INCREMENT,
  createdon date NOT NULL,
  fnpos varchar(255) NOT NULL,
  fndoc varchar(255) NOT NULL,
  amount decimal(10, 2) NOT NULL,
  cnt smallint(6) NOT NULL,
  ramount decimal(10, 2) NOT NULL,
  rcnt smallint(6) NOT NULL,
  sentxml longtext NOT NULL,
  taxanswer longblob NOT NULL,
  PRIMARY KEY (id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE ppo_zformstat (
  zf_id int(11) NOT NULL AUTO_INCREMENT,
  pos_id int(11) NOT NULL,
  checktype int(11) NOT NULL,
  createdon datetime NOT NULL,
  amount0 decimal(10, 2) NOT NULL,
  amount1 decimal(10, 2) NOT NULL,
  amount2 decimal(10, 2) NOT NULL,
  amount3 decimal(10, 2) NOT NULL,
  amount4 decimal(10, 2) default 0,
  document_number varchar(255) DEFAULT NULL,
  fiscnumber varchar(255) DEFAULT NULL,
  PRIMARY KEY (zf_id)
) ENGINE = INNODB DEFAULT CHARSET = utf8;

CREATE TABLE prodproc (
  pp_id int(11) NOT NULL AUTO_INCREMENT,
  procname varchar(255) NOT NULL,
  basedoc varchar(255) DEFAULT NULL,
   
  state smallint(4) DEFAULT 0,
  detail longtext,
  PRIMARY KEY (pp_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE prodstage (
  st_id int(11) NOT NULL AUTO_INCREMENT,
  pp_id int(11) NOT NULL,
  pa_id int(11) NOT NULL,
  state smallint(6) NOT NULL,
  stagename varchar(255) NOT NULL,
  detail longtext,
  PRIMARY KEY (st_id),
  KEY pp_id (pp_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

 

CREATE TABLE crontask (
  id int(11) NOT NULL AUTO_INCREMENT,
  created datetime NOT NULL,
  tasktype varchar(64) DEFAULT NULL,
  taskdata text DEFAULT NULL,
  starton datetime DEFAULT NULL,

  PRIMARY KEY (id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE roles (
  role_id int(11) NOT NULL AUTO_INCREMENT,
  rolename varchar(255) DEFAULT NULL,
  acl mediumtext,
  disabled tinyint(1),
  PRIMARY KEY (role_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE saltypes (
  st_id int(11) NOT NULL AUTO_INCREMENT,
  salcode int(11) NOT NULL,
  salname varchar(255) NOT NULL,
  salshortname varchar(255) DEFAULT NULL,
  acccode varchar(4) DEFAULT NULL,
  disabled tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (st_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE services (
  service_id int(11) NOT NULL AUTO_INCREMENT,
  service_name varchar(255) NOT NULL,
  detail text,
  disabled tinyint(1) DEFAULT 0,
  category varchar(255) DEFAULT NULL,
  PRIMARY KEY (service_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE shop_attributes (
  attribute_id int(11) NOT NULL AUTO_INCREMENT,
  attributename varchar(64) NOT NULL,
  cat_id int(11) NOT NULL,
  attributetype tinyint(4) NOT NULL,
  valueslist text,
  PRIMARY KEY (attribute_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE shop_attributes_order (
  order_id int(11) NOT NULL AUTO_INCREMENT,
  attr_id int(11) NOT NULL,
  pg_id int(11) NOT NULL,
  ordern int(11) NOT NULL,
  PRIMARY KEY (order_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE shop_attributevalues (
  attributevalue_id int(11) NOT NULL AUTO_INCREMENT,
  attribute_id int(11) NOT NULL,
  item_id int(11) NOT NULL,
  attributevalue varchar(255) NOT NULL,
  PRIMARY KEY (attributevalue_id),
  KEY attribute_id (attribute_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE shop_prod_comments (
  comment_id int(11) NOT NULL AUTO_INCREMENT,
  item_id int(11) NOT NULL,
  author varchar(64) NOT NULL,
  comment text NOT NULL,
  created timestamp NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  rating tinyint(4) NOT NULL DEFAULT 0,
  moderated tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (comment_id),
  KEY product_id (item_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE shop_varitems (
  varitem_id int(11) NOT NULL AUTO_INCREMENT,
  var_id int(11) NOT NULL,
  item_id int(11) NOT NULL,
  PRIMARY KEY (varitem_id),
  KEY item_id (item_id),
  KEY var_id (var_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE shop_vars (
  var_id int(11) NOT NULL AUTO_INCREMENT,
  attr_id int(11) NOT NULL,
  varname varchar(255) DEFAULT NULL,
  PRIMARY KEY (var_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE stats (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  category smallint(6) NOT NULL,
  keyd int(11) NOT NULL,
  vald int(11) NOT NULL,
  dt datetime DEFAULT NULL,
  PRIMARY KEY (id),
  KEY category (category),
  KEY dt (dt)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;



CREATE TABLE subscribes (
  sub_id int(11) NOT NULL AUTO_INCREMENT,
  sub_type int(11) DEFAULT NULL,
  reciever_type int(11) DEFAULT NULL,
  msg_type int(11) DEFAULT NULL,
  msgtext text,
  detail longtext,
  disabled int(1) DEFAULT 0,
  PRIMARY KEY (sub_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;

CREATE TABLE timesheet (
  time_id int(11) NOT NULL AUTO_INCREMENT,
  emp_id int(11) NOT NULL,
  description varchar(255) DEFAULT NULL,
  t_start datetime DEFAULT NULL,
  t_end datetime DEFAULT NULL,
  t_type int(11) DEFAULT 0,
  t_break smallint(6) DEFAULT 0,
  branch_id int(11) DEFAULT NULL,
  PRIMARY KEY (time_id),
  KEY emp_id (emp_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;


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
  COALESCE(employees.employee_id, 0) AS employee_id,
  (CASE WHEN ISNULL(employees.emp_name) THEN users.userlogin ELSE employees.emp_name END) AS username
FROM ((users
  LEFT JOIN employees
    ON (((users.userlogin = employees.login)
    AND (employees.disabled <> 1))))
  LEFT JOIN roles
    ON ((users.role_id = roles.role_id))) ;


CREATE
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
    when d.state in(7,11,20) then 45  
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
   
    
CREATE
VIEW contracts_view
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

 
CREATE VIEW custitems_view
AS
SELECT
  `s`.`custitem_id` AS `custitem_id`,
  `s`.`cust_name` AS `cust_name`,
  COALESCE(`s`.`item_id`, 0) AS `item_id`,
  `s`.`customer_id` AS `customer_id`,
  `s`.`quantity` AS `quantity`,
  `s`.`price` AS `price`,
  `s`.`cust_code` AS `cust_code`,
  `s`.`brand` AS `brand`,
  `s`.`store` AS `store`,
  `s`.`bar_code` AS `bar_code`,
  `s`.`details` AS `details`,
  `s`.`updatedon` AS `updatedon`,
  `c`.`customer_name` AS `customer_name`,
   i.item_code 
FROM `custitems` `s`
  JOIN `customers` `c`
    ON `s`.`customer_id` = `c`.`customer_id`
  LEFT JOIN items i ON  s.item_id = i.item_id 
  
  WHERE c.status <> 1   ;

CREATE
VIEW customers_view
AS
SELECT
  customers.customer_id AS customer_id,
  customers.customer_name AS customer_name,
  customers.detail AS detail,
  customers.email AS email,
  customers.createdon AS createdon,
  customers.phone AS phone,
  customers.status AS status,
  customers.city AS city,
  customers.leadsource AS leadsource,
  customers.leadstatus AS leadstatus,
  customers.country AS country,
  customers.passw AS passw,
  (SELECT
      COUNT(0)
    FROM messages m
    WHERE ((m.item_id = customers.customer_id)
    AND (m.item_type = 2))) AS mcnt,
  (SELECT
      COUNT(0)
    FROM files f
    WHERE ((f.item_id = customers.customer_id)
    AND (f.item_type = 2))) AS fcnt,
  (SELECT
      COUNT(0)
    FROM eventlist e
    WHERE ((e.customer_id = customers.customer_id)
    AND (e.eventdate >= NOW()))) AS ecnt
FROM customers ;

CREATE
VIEW docstatelog_view
AS
SELECT
  dl.log_id AS log_id,
  dl.user_id AS user_id,
  dl.document_id AS document_id,
  dl.docstate AS docstate,
  dl.createdon AS createdon,
  dl.hostname AS hostname,
  u.username AS username,
  d.document_number AS document_number,
  d.meta_desc AS meta_desc,
  d.meta_name AS meta_name
FROM ((docstatelog dl
  JOIN users_view u
    ON ((dl.user_id = u.user_id)))
  JOIN documents_view d
    ON ((d.document_id = dl.document_id))) ;



CREATE
VIEW empacc_view
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

    
CREATE VIEW entrylist_view 
AS
SELECT
  entrylist.entry_id AS entry_id,
  entrylist.document_id AS document_id,
  entrylist.quantity AS quantity,
  documents.customer_id AS customer_id,
  entrylist.stock_id AS stock_id,
  entrylist.service_id AS service_id,
  entrylist.tag AS tag,
  entrylist.createdon AS createdon,
  store_stock.item_id AS item_id,
  store_stock.partion AS partion,
  case when entrylist.createdon  is NULL  then documents.document_date else entrylist.createdon  end      AS document_date,
  entrylist.cost AS cost,
  entrylist.outprice AS outprice
FROM ((entrylist
  LEFT JOIN store_stock
    ON ((entrylist.stock_id = store_stock.stock_id)))
  JOIN documents
    ON ((entrylist.document_id = documents.document_id)));    
    
    

CREATE VIEW eventlist_view
AS
SELECT
  e.user_id AS user_id,
  e.eventdate AS eventdate,
  e.title AS title,
  e.description AS description,
  e.event_id AS event_id,
  e.customer_id AS customer_id,
  e.isdone AS isdone,
  e.createdby AS createdby,
  e.event_type AS event_type,
  e.details AS details,
  c.customer_name AS customer_name,
  uv.username AS username,
  uv2.username AS createdname
FROM ((eventlist e
  LEFT JOIN customers c
    ON (e.customer_id = c.customer_id))
  LEFT JOIN users_view uv
    ON ((uv.user_id = e.user_id))      
  LEFT JOIN users_view uv2
    ON ((uv2.user_id = e.createdby))) ;    
    
   

CREATE
VIEW iostate_view
AS
SELECT
  s.id AS id,
  s.document_id AS document_id,
  s.iotype AS iotype,
  s.amount AS amount,
  d.document_date AS document_date,
  d.branch_id AS branch_id
FROM (iostate s
  JOIN documents d
    ON ((s.document_id = d.document_id))) ;

CREATE
VIEW issue_issuelist_view
AS
SELECT
  i.issue_id AS issue_id,
  i.issue_name AS issue_name,
  i.details AS details,
  i.status AS status,
  i.priority AS priority,
  i.user_id AS user_id,
  i.lastupdate AS lastupdate,
  i.project_id AS project_id,
  u.username AS username,
  p.project_name AS project_name
FROM ((issue_issuelist i
  LEFT JOIN users_view u
    ON ((i.user_id = u.user_id)))
  JOIN issue_projectlist p
    ON ((i.project_id = p.project_id))) ;

CREATE
VIEW issue_projectlist_view
AS
SELECT
  p.project_id AS project_id,
  p.project_name AS project_name,
  p.details AS details,
  p.customer_id AS customer_id,
  p.status AS status,
  c.customer_name AS customer_name,
  (SELECT
      COALESCE(SUM((CASE WHEN (i.status = 0) THEN 1 ELSE 0 END)), 0)
    FROM issue_issuelist i
    WHERE (i.project_id = p.project_id)) AS inew,
  (SELECT
      COALESCE(SUM((CASE WHEN (i.status > 1) THEN 1 ELSE 0 END)), 0)
    FROM issue_issuelist i
    WHERE (i.project_id = p.project_id)) AS iproc,
  (SELECT
      COALESCE(SUM((CASE WHEN (i.status = 1) THEN 1 ELSE 0 END)), 0)
    FROM issue_issuelist i
    WHERE (i.project_id = p.project_id)) AS iclose
FROM (issue_projectlist p
  LEFT JOIN customers c
    ON ((p.customer_id = c.customer_id))) ;

CREATE
VIEW issue_time_view
AS
SELECT
  t.id AS id,
  t.issue_id AS issue_id,
  t.createdon AS createdon,
  t.user_id AS user_id,
  t.duration AS duration,
  t.notes AS notes,
  u.username AS username,
  i.issue_name AS issue_name,
  i.project_id AS project_id,
  i.project_name AS project_name
FROM ((issue_time t
  JOIN users_view u
    ON ((t.user_id = u.user_id)))
  JOIN issue_issuelist_view i
    ON ((t.issue_id = i.issue_id))) ;

    
CREATE
VIEW items_view
AS
SELECT
  items.item_id AS item_id,
  items.itemname AS itemname,
  items.description AS description,
  items.detail AS detail,
  items.item_code AS item_code,
  items.bar_code AS bar_code,
  items.cat_id AS cat_id,
  items.msr AS msr,
  items.disabled AS disabled,
  items.minqty AS minqty,
  items.item_type AS item_type,
  items.manufacturer AS manufacturer,
  item_cat.cat_name AS cat_name
FROM (items
  LEFT JOIN item_cat
    ON ((items.cat_id = item_cat.cat_id))) ;    
    
CREATE
VIEW item_set_view
AS
SELECT
  item_set.set_id AS set_id,
  item_set.item_id AS item_id,
  item_set.pitem_id AS pitem_id,
  item_set.qty AS qty,
  item_set.service_id AS service_id,
  item_set.cost AS cost,
  items.itemname AS itemname,
  items.item_code AS item_code,
  services.service_name AS service_name
FROM ((item_set
  LEFT JOIN items
    ON ((item_set.item_id = items.item_id)))
  LEFT JOIN services
    ON ((item_set.service_id = services.service_id))) ;



CREATE
VIEW messages_view
AS
SELECT
  messages.message_id AS message_id,
  messages.user_id AS user_id,
  messages.created AS created,
  messages.message AS message,
  messages.item_id AS item_id,
  messages.item_type AS item_type,
  messages.checked AS checked,
  users_view.username AS username
FROM (messages
  LEFT JOIN users_view
    ON ((messages.user_id = users_view.user_id))) ;

CREATE
VIEW note_nodesview
AS
SELECT
  note_nodes.node_id AS node_id,
  note_nodes.pid AS pid,
  note_nodes.title AS title,
  note_nodes.mpath AS mpath,
  note_nodes.user_id AS user_id,
  note_nodes.ispublic AS ispublic,
  (SELECT
      COUNT(note_topicnode.topic_id) AS cnt
    FROM note_topicnode
    WHERE (note_topicnode.node_id = note_nodes.node_id)) AS tcnt
FROM note_nodes ;

CREATE
VIEW note_topicnodeview
AS
SELECT
  note_topicnode.topic_id AS topic_id,
  note_topicnode.node_id AS node_id,
  note_topicnode.tn_id AS tn_id,
  note_topicnode.islink AS islink,
  note_topics.title AS title,
  note_topics.content AS content 
   
FROM note_topics
  JOIN note_topicnode
    ON note_topics.topic_id = note_topicnode.topic_id
  JOIN note_nodes
    ON note_topicnode.node_id = note_nodes.node_id    ;

CREATE
VIEW note_topicsview
AS
SELECT
  t.topic_id AS topic_id,
  t.title AS title,
  t.content AS content,
  t.acctype AS acctype,
  t.user_id AS user_id
FROM note_topics t ;

CREATE
VIEW paylist_view
AS
SELECT
  pl.pl_id AS pl_id,
  pl.document_id AS document_id,
  pl.amount AS amount,
  pl.mf_id AS mf_id,
  pl.notes AS notes,
  pl.user_id AS user_id,
  pl.paydate AS paydate,
  pl.paytype AS paytype,
  pl.bonus AS bonus,
  d.document_number AS document_number,
  u.username AS username,
  m.mf_name AS mf_name,
  d.customer_id AS customer_id,
  d.customer_name AS customer_name
FROM (((paylist pl
  JOIN documents_view d
    ON ((pl.document_id = d.document_id)))
  LEFT JOIN users_view u
    ON ((pl.user_id = u.user_id)))
  LEFT JOIN mfund m
    ON ((pl.mf_id = m.mf_id))) ;


   
    
    
CREATE VIEW prodstage_view
AS
SELECT
  ps.st_id AS st_id,
  ps.pp_id AS pp_id,
  ps.pa_id AS pa_id,
  ps.state AS state,
  ps.stagename AS stagename,
 
  ps.detail AS detail,
  pr.procname AS procname,
 
  pr.state AS procstate,
  pa.pa_name AS pa_name
FROM ((prodstage ps
  JOIN prodproc pr
    ON ((pr.pp_id = ps.pp_id)))
  JOIN parealist pa
    ON ((pa.pa_id = ps.pa_id))) ;    
    
CREATE VIEW prodproc_view
AS
SELECT
  p.pp_id AS pp_id,
  p.procname AS procname,
  p.basedoc AS basedoc,
 
  p.state AS state,

  COALESCE((SELECT
      COUNT(0)
    FROM prodstage ps
    WHERE (ps.pp_id = p.pp_id)), NULL) AS stagecnt,
  p.detail AS detail
FROM prodproc p ;




CREATE
VIEW roles_view
AS
SELECT
  roles.role_id AS role_id,
  roles.rolename AS rolename,
  roles.disabled AS disabled,
  roles.acl AS acl,
  (SELECT
      COALESCE(COUNT(0), 0)
    FROM users
    WHERE (users.role_id = roles.role_id)) AS cnt
FROM roles ;

CREATE
VIEW shop_attributes_view
AS
SELECT
  shop_attributes.attribute_id AS attribute_id,
  shop_attributes.attributename AS attributename,
  shop_attributes.cat_id AS cat_id,
  shop_attributes.attributetype AS attributetype,
  shop_attributes.valueslist AS valueslist,
  shop_attributes_order.ordern AS ordern
FROM (shop_attributes
  JOIN shop_attributes_order
    ON (((shop_attributes.attribute_id = shop_attributes_order.attr_id)
    AND (shop_attributes.cat_id = shop_attributes_order.pg_id))))
ORDER BY shop_attributes_order.ordern ;

CREATE
VIEW shop_products_view
AS
SELECT
  i.item_id AS item_id,
  i.itemname AS itemname,
  i.description AS description,
  i.detail AS detail,
  i.item_code AS item_code,
  i.bar_code AS bar_code,
  i.cat_id AS cat_id,
  i.msr AS msr,
  i.disabled AS disabled,
  i.minqty AS minqty,
  i.item_type AS item_type,
  i.manufacturer AS manufacturer,
  i.cat_name AS cat_name,
  COALESCE((SELECT
      SUM(store_stock.qty)
    FROM store_stock
    WHERE (store_stock.item_id = i.item_id)), 0) AS qty,
  COALESCE((SELECT
      COUNT(0)
    FROM shop_prod_comments c
    WHERE (c.item_id = i.item_id)), 0) AS comments,
  COALESCE((SELECT
      SUM(c.rating)
    FROM shop_prod_comments c
    WHERE (c.item_id = i.item_id)), 0) AS ratings
FROM items_view i ;

CREATE
VIEW shop_varitems_view
AS
SELECT
  shop_varitems.varitem_id AS varitem_id,
  shop_varitems.var_id AS var_id,
  shop_varitems.item_id AS item_id,
  sv.attr_id AS attr_id,
  sa.attributevalue AS attributevalue,
  it.itemname AS itemname,
  it.item_code AS item_code
FROM (((shop_varitems
  JOIN shop_vars sv
    ON ((shop_varitems.var_id = sv.var_id)))
  JOIN shop_attributevalues sa
    ON (((sa.item_id = shop_varitems.item_id)
    AND (sv.attr_id = sa.attribute_id))))
  JOIN items it
    ON ((shop_varitems.item_id = it.item_id))) ;

CREATE
VIEW shop_vars_view
AS
SELECT
  shop_vars.var_id AS var_id,
  shop_vars.attr_id AS attr_id,
  shop_vars.varname AS varname,
  shop_attributes.attributename AS attributename,
  shop_attributes.cat_id AS cat_id,
  (SELECT
      COUNT(0)
    FROM shop_varitems
    WHERE (shop_varitems.var_id = shop_vars.var_id)) AS cnt
FROM ((shop_vars
  JOIN shop_attributes
    ON ((shop_vars.attr_id = shop_attributes.attribute_id)))
  JOIN item_cat
    ON ((shop_attributes.cat_id = item_cat.cat_id))) ;

CREATE VIEW store_stock_view
AS
SELECT
  st.stock_id AS stock_id,
  st.item_id AS item_id,
  st.partion AS partion,
  st.store_id AS store_id,
  st.customer_id AS customer_id,
  st.emp_id AS emp_id,
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
    
    

CREATE
VIEW timesheet_view
AS
SELECT
  t.time_id AS time_id,
  t.emp_id AS emp_id,
  t.description AS description,
  t.t_start AS t_start,
  t.t_end AS t_end,
  t.t_type AS t_type,
  t.t_break AS t_break,
  e.emp_name AS emp_name,
  b.branch_name AS branch_name,
  e.disabled AS disabled,
  t.branch_id AS branch_id
FROM ((timesheet t
  JOIN employees e
    ON ((t.emp_id = e.employee_id)))
  LEFT JOIN branches b
    ON ((t.branch_id = b.branch_id))) ;


CREATE TABLE  taglist (
  id int(11) NOT NULL AUTO_INCREMENT,
  tag_type smallint(6) NOT NULL,
  item_id int(11) NOT NULL,
  tag_name varchar(255) NOT NULL,
  PRIMARY KEY (id)
)
ENGINE = InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE promocodes (
  id int(11) NOT NULL AUTO_INCREMENT,
  code varchar(16) NOT NULL,
  type tinyint(4) NOT NULL,
  disabled tinyint(1) NOT NULL default 0,
  enddate  DATE DEFAULT null,  
  details text DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE = INNODB DEFAULT CHARSET = utf8
;

ALTER TABLE promocodes
ADD UNIQUE INDEX code (code)   ; 

CREATE TABLE custacc (
  ca_id bigint(20) NOT NULL AUTO_INCREMENT,
  customer_id int(11) NOT NULL,
  document_id int(11) DEFAULT NULL,
  optype tinyint(4) DEFAULT NULL,
  amount decimal(10, 2) NOT NULL,
  createdon date DEFAULT NULL,
  PRIMARY KEY (ca_id),
  KEY customer_id (customer_id),
  KEY document_id (document_id)
) ENGINE = INNODB  DEFAULT CHARSET = utf8;    
    
CREATE
VIEW custacc_view
AS
SELECT
  e.ca_id AS ca_id,
  e.customer_id AS customer_id,
  e.document_id AS document_id,
  e.optype AS optype,
  d.notes AS notes,
  e.amount AS amount,
  COALESCE(e.createdon, d.document_date) AS createdon,
  d.document_number AS document_number,
  c.customer_name AS customer_name
FROM ((custacc e
  LEFT JOIN documents d
    ON ((d.document_id = e.document_id)))
  JOIN customers c
    ON ((c.customer_id = e.customer_id))) ;
    
  
 

CREATE VIEW item_cat_view
AS
SELECT
  ic.cat_id AS cat_id,
  ic.cat_name AS cat_name,
  ic.detail AS detail,
  ic.parent_id AS parent_id,
  COALESCE((SELECT
      COUNT(*)
    FROM items i
    WHERE i.cat_id = ic.cat_id), 0) AS itemscnt  ,
    COALESCE((SELECT
      COUNT(*)
    FROM item_cat ic2
    WHERE ic.cat_id = ic2.parent_id), 0) AS childcnt
FROM item_cat ic   ;  


CREATE TABLE  eqentry (
  id int NOT NULL AUTO_INCREMENT,
  eq_id int NOT NULL,
  optype smallint NOT NULL,
  amount decimal(10, 2) DEFAULT NULL,
  document_id int DEFAULT NULL,
  KEY (eq_id) ,
  KEY (document_id) ,
  PRIMARY KEY (id)
) ENGINE = INNODB DEFAULT CHARSET = utf8 ;  

 

CREATE
VIEW eqentry_view
AS
SELECT
  e.id AS id,
  e.eq_id AS eq_id,
  d.document_date AS document_date,
  e.optype AS optype,
  e.amount AS amount,
  e.document_id AS document_id,
  d.document_number AS document_number,
  d.notes AS notes
FROM (eqentry e
  JOIN documents d
    ON ((e.document_id = d.document_id)))  ;
    

     
CREATE TABLE shop_articles (
  id int NOT NULL AUTO_INCREMENT,
  title varchar(255) NOT NULL,
  shortdata text DEFAULT NULL,
  longdata longtext NOT NULL,
  createdon date NOT NULL,
  isactive tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE = INNODB DEFAULT CHARSET = utf8 ;   

CREATE TABLE  substitems (
  id bigint NOT NULL AUTO_INCREMENT,
  itemname varchar(255) NOT NULL ,
  origcode varchar(255) NOT NULL ,
  origbrand varchar(255) DEFAULT NULL ,
  substcode varchar(255) NOT NULL ,
  substbrand varchar(255) DEFAULT NULL ,
  customer_id int DEFAULT NULL,
   
  KEY (origcode) ,
  PRIMARY KEY (id)
) ENGINE = INNODB DEFAULT CHARSET = utf8 ;    


CREATE TABLE acc_entry (
  id bigint NOT NULL AUTO_INCREMENT,
  createdon DATE DEFAULT NULL,
  accdt varchar(4) DEFAULT NULL,
  accct varchar(4) DEFAULT NULL,
  amount decimal(11, 2) NOT NULL,
  document_id int NOT NULL,
  
  tagdt int  DEFAULT  NULL   ,
  tagct int  DEFAULT  NULL   ,
 
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
    
  d.notes AS notes,
  e.document_id AS document_id,
  d.branch_id AS branch_id,
  e.tagdt AS tagdt,
  e.tagct AS tagct,
   
  d.document_number AS document_number
FROM  acc_entry e
  JOIN documents d
    ON  d.document_id = e.document_id ;

  
  
INSERT INTO users (userlogin, userpass, createdon, email, acl, disabled, options, role_id ) VALUES( 'admin', 'admin', '2017-01-01', 'admin@admin.admin', 'a:3:{s:9:\"aclbranch\";N;s:6:\"onlymy\";N;s:8:\"hidemenu\";N;}', 0, 'a:23:{s:8:\"defstore\";s:1:\"0\";s:7:\"deffirm\";s:1:\"0\";s:5:\"defmf\";s:1:\"0\";s:13:\"defsalesource\";s:1:\"0\";s:8:\"pagesize\";s:2:\"25\";s:11:\"hidesidebar\";i:0;s:8:\"darkmode\";i:1;s:11:\"emailnotify\";i:0;s:16:\"usemobileprinter\";i:0;s:7:\"pserver\";s:0:\"\";s:6:\"prtype\";i:0;s:5:\"pwsym\";i:0;s:12:\"pserverlabel\";s:0:\"\";s:11:\"prtypelabel\";i:0;s:10:\"pwsymlabel\";i:0;s:6:\"prturn\";i:0;s:8:\"pcplabel\";i:0;s:3:\"pcp\";i:0;s:8:\"mainpage\";s:15:\"\\App\\Pages\\Main\";s:5:\"phone\";s:0:\"\";s:5:\"viber\";s:0:\"\";s:4:\"favs\";s:0:\"\";s:7:\"chat_id\";s:0:\"\";}', 1);
INSERT INTO roles (rolename, acl) VALUES( 'admins', 'a:11:{s:13:\"noshowpartion\";N;s:15:\"showotherstores\";N;s:7:\"aclview\";N;s:7:\"acledit\";N;s:6:\"aclexe\";N;s:9:\"aclcancel\";N;s:8:\"aclstate\";N;s:9:\"acldelete\";N;s:7:\"widgets\";N;s:7:\"modules\";N;s:9:\"smartmenu\";s:3:\"8,2\";}');
UPDATE users set  role_id=(select role_id  from roles  where  rolename='admins' limit 0,1 )  where  userlogin='admin' ;

 
INSERT INTO stores (  storename, description) VALUES(  'Основний склад', '');
INSERT INTO mfund (  mf_name, description, branch_id, detail) VALUES( 'Каса', '', NULL, '<detail><beznal>0</beznal><btran></btran><bank><![CDATA[]]></bank><bankacc><![CDATA[]]></bankacc></detail>');

INSERT INTO customers ( customer_name, detail, email, phone, status, city, leadstatus, leadsource, createdon) VALUES( 'Фiз. особа', '<detail><code></code><discount></discount><bonus></bonus><type>0</type><fromlead>0</fromlead><jurid></jurid><shopcust_id></shopcust_id><isholding>0</isholding><holding>0</holding><viber></viber><nosubs>1</nosubs><user_id>4</user_id><holding_name><![CDATA[]]></holding_name><address><![CDATA[]]></address><comment><![CDATA[Умовний контрагент (якщо треба когось зазначити)]]></comment></detail>', '', '', 0, '', NULL, NULL, '2021-04-28');
  
  
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 4, 'Склади', 'StoreList', 'Товари', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 4, 'Номенклатура', 'ItemList', 'Товари', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 4, 'Співробітники', 'EmployeeList', '', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 4, 'Категорії товарів', 'CategoryList', 'Товари', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 4, 'Контрагенти', 'CustomerList', '', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Прибуткова накладна', 'GoodsReceipt', 'Закупівлі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Видаткова накладна', 'GoodsIssue', 'Продажі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'Загальний журнал', 'DocList', '', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Гарантійний талон', 'Warranty', 'Продажі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Рух по складу', 'ItemActivity', 'Склад', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'ABC аналіз', 'ABC', 'Аналітика', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 4, 'Послуги, роботи', 'ServiceList', '', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Акт виконаних робіт', 'ServiceAct', 'Послуги', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Повернення від покупця', 'ReturnIssue', 'Продажі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'Наряди', 'TaskList', 'Виробництво', 1);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Наряд', 'Task', 'Виробництво', 1);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Оплата по виробництву', 'EmpTask', 'Виробництво', 1);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Закупівлі', 'Income', 'Закупівлі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Продажі', 'Outcome', 'Продажі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'Замовлення клієнтів', 'OrderList', 'Продажі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Замовлення', 'Order', 'Продажі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Оприбуткування з виробництва', 'ProdReceipt', 'Виробництво', 1);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Списання на виробництво', 'ProdIssue', 'Виробництво', 1);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Звіт по виробництву', 'Prod', 'Виробництво', 1);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 4, 'Виробничі дільниці', 'ProdAreaList', '', 1);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'Продажі', 'GIList', 'Продажі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 4, 'Обладнання та ОЗ', 'EqList', '', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'Закупівлі', 'GRList', 'Закупівлі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Заявка постачальнику', 'OrderCust', 'Закупівлі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'Заявки постачальникам', 'OrderCustList', 'Закупівлі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Прайс', 'Price', 'Склад', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Повернення постачальнику', 'RetCustIssue', 'Закупівлі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Перекомплектація (розфасовка)', 'TransItem', 'Склад', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 4, 'Каси, рахунки', 'MFList', '', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'Журнал платежів', 'PayList', 'Каса та платежі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Рух по грошовим рахункам', 'PayActivity', 'Каса та платежі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Прибутковий ордер', 'IncomeMoney', 'Каса та платежі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Видатковий ордер', 'OutcomeMoney', 'Каса та платежі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Прибутки та видатки', 'PayBalance', '', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Інвентаризація', 'Inventory', 'Склад', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Рахунок, вхідний', 'InvoiceCust', 'Закупівлі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Рахунок-фактура', 'Invoice', 'Продажі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 5, 'Імпорт', 'Import', '', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'Рух ТМЦ', 'StockList', 'Склад', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Касовий чек', 'POSCheck', 'Продажі', 1);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'ТМЦ в дорозі', 'CustOrder', 'Закупівлі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Списання ТМЦ', 'OutcomeItem', 'Склад', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Оприбуткування ТМЦ', 'IncomeItem', 'Склад', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 5, 'АРМ касира', 'ARMPos', '', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'Роботи, послуги', 'SerList', '', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'ТМЦ на складі', 'ItemList', 'Склад', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 5, 'Експорт', 'Export', '', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Виплата зарплати', 'OutSalary', 'Зарплата', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Звіт по зарплаті', 'SalaryRep', 'Каса та платежі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 4, 'Договори', 'ContractList', '', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Перемiщення ТМЦ', 'MoveItem', 'Склад', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Робочий час', 'Timestat', '', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Товарно-транспортна накладна', 'TTN', 'Продажі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Нелiквiднi товари', 'NoLiq', 'Склад', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'Розрахунки з постачальниками', 'PaySelList', 'Каса та платежі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'Розрахунки з покупцями', 'PayBayList', 'Каса та платежі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Перемiщення грошей', 'MoveMoney', 'Каса та платежі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Замовленя кафе', 'OrderFood', 'Кафе', 1);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 5, 'АРМ касира (кафе)', 'ARMFood', 'Кафе', 1);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'Журнал доставок', 'DeliveryList', 'Кафе', 1);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 5, 'АРМ кухнi (бару)', 'ArmProdFood', 'Кафе', 1);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Кафе', 'OutFood', 'Кафе', 1);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'Прибутки та видатки', 'IOState', '', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Замовленi товари', 'ItemOrder', 'Продажі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 5, 'Програма лояльності', 'Discounts', '', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Нарахування зарплати', 'CalcSalary', 'Каса та платежі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 4, 'Нарахування та утримання', 'SalaryTypeList', '', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'Вир. процеси', 'ProdProcList', 'Виробництво', 1);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'Вир. етапи', 'ProdStageList', 'Виробництво', 1);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Перемiщення партiй ТМЦ', 'MovePart', 'Склад', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Повернення покупцiв', 'Returnselled', 'Продажі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Повернення постачальникам', 'Returnbayed', 'Закупівлі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Отримані послуги', 'IncomeService', 'Послуги', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Стан складiв', 'StoreItems', 'Склад', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'Товари у постачальників', 'CustItems', 'Закупівлі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Акт звірки', 'CompareAct', 'Контрагенти', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Зарезервовані товари', 'Reserved', 'Склад', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'OLAP аналіз', 'OLAP', 'Аналітика', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Управлiнський баланс', 'Balance', '', 0);  
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Офiсний документ', 'OfficeDoc', '', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'Офiс', 'OfficeList', '', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Прогноз продаж', 'PredSell', 'Аналітика', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Повернення з виробництва', 'ProdReturn', 'Виробництво', 1);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Товари на комісії', 'ItemComission', 'Закупівлі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'Зарплата', 'SalaryList', 'Каса та платежі', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 1, 'Операції з ОЗ та  НМА', 'EQ', '', 0);
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 3, 'Платіжний календар', 'PayTable', 'Каса та платежі', 0);
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 1, 'Авансовий звiт', 'AdvanceRep', 'Каса та платежі',   0);
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  2, 'Закриття дня', 'EndDay', 'Каса та платежі',     0);
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  3, 'Замiни ТМЦ', 'Substitution', 'Склад',     0);

INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 4, 'План рахункiв', 'AccountList', 'Бухоблiк',   1 );
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 3, 'Журнал проводок', 'AccountEntryList', 'Бухоблiк',   1 );
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 2, 'Рух по рахунку', 'AccountActivity', 'Бухоблiк',     1);
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 1, 'Ручна проводка', 'ManualEntry', 'Бухоблiк',   1);
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 2, 'Оборотно-сальдова вiдомiсть', 'ObSaldo', 'Бухоблiк',     1);
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 2, 'Шахматна вiдомiсть', 'Shahmatka', 'Бухоблiк',   1 );
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 2, 'Фiн. звiт малого  пiдприємства', 'FinReportSmall', 'Бухоблiк',  1 );
INSERT INTO metadata (  meta_type, description,   meta_name, menugroup,   disabled) VALUES( 1, 'Закриття перiоду', 'FinResult', 'Бухоблiк',   1);
   
   
INSERT INTO saltypes (st_id, salcode, salname, salshortname, disabled) VALUES(2, 105, 'Основна зарплата', 'осн', 0);
INSERT INTO saltypes (st_id, salcode, salname, salshortname, disabled) VALUES(3, 200, 'Всього нараховано', 'вс. нар', 0);
INSERT INTO saltypes (st_id, salcode, salname, salshortname, disabled) VALUES(4, 600, 'Всього утримано', 'вс. утр', 0);
INSERT INTO saltypes (st_id, salcode, salname, salshortname, disabled) VALUES(5, 900, 'До видачi', 'До видачi', 0);
INSERT INTO saltypes (st_id, salcode, salname, salshortname, disabled) VALUES(6, 850, 'Аванс', 'Аванс', 0);
INSERT INTO saltypes (st_id, salcode, salname, salshortname, disabled) VALUES(7, 220, 'НДФО', 'НДФО', 0);
INSERT INTO saltypes (st_id, salcode, salname, salshortname, disabled) VALUES(8, 300, 'ЕСВ', 'ЕСВ', 0);


INSERT INTO options (optname, optvalue) VALUES('api', 'YTozOntzOjM6ImV4cCI7czowOiIiO3M6Mzoia2V5IjtzOjQ6InRlc3QiO3M6NToiYXR5cGUiO3M6MToiMSI7fQ==');
INSERT INTO options (optname, optvalue) VALUES('common', 'YTo0Mzp7czo5OiJxdHlkaWdpdHMiO3M6MToiMCI7czo4OiJhbWRpZ2l0cyI7czoxOiIwIjtzOjEwOiJkYXRlZm9ybWF0IjtzOjU6ImQubS5ZIjtzOjExOiJwYXJ0aW9udHlwZSI7czoxOiIxIjtzOjY6InBob25lbCI7czoyOiIxMCI7czo2OiJwcmljZTEiO3M6MTg6ItCg0L7Qt9C00YDRltCx0L3QsCI7czo2OiJwcmljZTIiO3M6MTI6ItCe0L/RgtC+0LLQsCI7czo2OiJwcmljZTMiO3M6MDoiIjtzOjY6InByaWNlNCI7czowOiIiO3M6NjoicHJpY2U1IjtzOjA6IiI7czo4OiJkZWZwcmljZSI7czowOiIiO3M6ODoic2hvcG5hbWUiO3M6MDoiIjtzOjg6InRzX2JyZWFrIjtzOjI6IjYwIjtzOjg6InRzX3N0YXJ0IjtzOjU6IjA5OjAwIjtzOjY6InRzX2VuZCI7czo1OiIxODowMCI7czoxMToiY2hlY2tzbG9nYW4iO3M6MDoiIjtzOjExOiJhdXRvYXJ0aWNsZSI7aToxO3M6MTA6InVzZXNudW1iZXIiO3M6MToiMCI7czoxMDoidXNlc2Nhbm5lciI7aTowO3M6MTY6InVzZW1vYmlsZXNjYW5uZXIiO2k6MDtzOjk6InVzZWltYWdlcyI7aTowO3M6MTQ6InByaW50b3V0cXJjb2RlIjtpOjA7czoxNDoibm9jaGVja2FydGljbGUiO2k6MDtzOjE1OiJzaG93YWN0aXZldXNlcnMiO2k6MDtzOjg6InNob3djaGF0IjtpOjA7czoxMDoidXNlY2F0dHJlZSI7aTowO3M6OToidXNlYnJhbmNoIjtpOjA7czoxMDoibm9hbGxvd2ZpeiI7aTowO3M6MTA6ImFsbG93bWludXMiO2k6MDtzOjY6InVzZXZhbCI7aTowO3M6NjoiY2FwY2hhIjtpOjA7czo5OiJudW1iZXJ0dG4iO2k6MDtzOjk6InBheXR5cGVpbiI7czoxOiIwIjtzOjEwOiJwYXl0eXBlb3V0IjtzOjE6IjAiO3M6MTI6ImFsbG93bWludXNtZiI7aTowO3M6NzoiY2FzaGllciI7czowOiIiO3M6MTA6ImFjdHVhbGRhdGUiO2k6MTcwNDA2MDAwMDtzOjE0OiJzcHJlYWRkZWxpdmVyeSI7aTowO3M6MTE6ImJheWRlbGl2ZXJ5IjtpOjA7czo4OiJub3VwZGF0ZSI7aTowO3M6NzoiY2hlY2tpcCI7aTowO3M6NjoiaXBsaXN0IjtzOjA6IiI7czo4OiJzdG9yZWVtcCI7aTowO30=');
INSERT INTO options (optname, optvalue) VALUES('discount', 'YToxODp7czo4OiJmaXJzdGJheSI7czoyOiIxMSI7czo2OiJib251czEiO3M6MzoiMS4xIjtzOjY6ImxldmVsMiI7czowOiIiO3M6NjoiYm9udXMyIjtzOjM6IjEuNCI7czo2OiJzdW1tYTEiO3M6MzoiMTAwIjtzOjY6InN1bW1hMiI7czo0OiIxMDAwIjtzOjY6ImJvbnVzMyI7czoxOiIzIjtzOjY6InN1bW1hMyI7czo0OiIzMDAwIjtzOjY6ImJvbnVzNCI7czoxOiI0IjtzOjY6InN1bW1hNCI7czo0OiI0MDAwIjtzOjU6ImRpc2MxIjtzOjE6IjEiO3M6MTA6ImRpc2NzdW1tYTEiO3M6MToiMCI7czo1OiJkaXNjMiI7czoxOiIzIjtzOjEwOiJkaXNjc3VtbWEyIjtzOjE6IjAiO3M6NToiZGlzYzMiO3M6MToiNyI7czoxMDoiZGlzY3N1bW1hMyI7czoxOiIwIjtzOjU6ImRpc2M0IjtzOjA6IiI7czoxMDoiZGlzY3N1bW1hNCI7czowOiIiO30=');
INSERT INTO options (optname, optvalue) VALUES('food', 'YToxNTp7czo4OiJ3b3JrdHlwZSI7czoxOiIyIjtzOjk6InByaWNldHlwZSI7czo2OiJwcmljZTEiO3M6ODoiZGVsaXZlcnkiO2k6MTtzOjY6InRhYmxlcyI7aToxO3M6NDoicGFjayI7aToxO3M6NDoibWVudSI7aToxO3M6NDoibmFtZSI7czo2OiJkZGRkZGQiO3M6NToicGhvbmUiO3M6ODoiNTU1NTU1NTUiO3M6NjoidGltZXBuIjtzOjI6IjExIjtzOjY6InRpbWVzYSI7czowOiIiO3M6NjoidGltZXN1IjtzOjA6IiI7czoxMjoiZm9vZGJhc2VtZW51IjtzOjE6IjAiO3M6MTY6ImZvb2RiYXNlbWVudW5hbWUiO3M6MDoiIjtzOjk6ImZvb2RtZW51MiI7czoxOiIwIjtzOjEyOiJmb29kbWVudW5hbWUiO3M6MDoiIjt9');
INSERT INTO options (optname, optvalue) VALUES('printer', 'YTo3OntzOjg6InBtYXhuYW1lIjtzOjE6IjciO3M6OToicHJpY2V0eXBlIjtzOjY6InByaWNlMSI7czoxMToiYmFyY29kZXR5cGUiO3M6NDoiQzEyOCI7czo2OiJwcHJpY2UiO2k6MTtzOjU6InBjb2RlIjtpOjE7czo4OiJwYmFyY29kZSI7aToxO3M6NzoicHFyY29kZSI7aTowO30=');
INSERT INTO options (optname, optvalue) VALUES('shop', 'YToyMDp7czo3OiJkZWZjdXN0IjtzOjE6IjEiO3M6MTE6ImRlZmN1c3RuYW1lIjtzOjI5OiLQm9C10L7QvdC40LQg0JzQsNGA0YLRi9C90Y7QuiI7czo5OiJkZWZicmFuY2giO047czo5OiJvcmRlcnR5cGUiO3M6MToiMCI7czoxMjoiZGVmcHJpY2V0eXBlIjtzOjY6InByaWNlMSI7czo1OiJlbWFpbCI7czowOiIiO3M6ODoic2hvcG5hbWUiO3M6MTc6ItCd0LDRiCDQvNCw0LPQsNC3IjtzOjEyOiJjdXJyZW5jeW5hbWUiO3M6Njoi0YDRg9CxIjtzOjg6InVzZWxvZ2luIjtpOjA7czo5OiJ1c2VmaWx0ZXIiO2k6MDtzOjEzOiJjcmVhdGVuZXdjdXN0IjtpOjA7czoxMToidXNlZmVlZGJhY2siO2k6MDtzOjExOiJ1c2VtYWlucGFnZSI7aTowO3M6NzoiYWJvdXR1cyI7czoxNjoiUEhBK1BHSnlQand2Y0Q0PSI7czo3OiJjb250YWN0IjtzOjA6IiI7czo4OiJkZWxpdmVyeSI7czowOiIiO3M6NDoibmV3cyI7czowOiIiO3M6NToicGFnZXMiO2E6Mjp7czo0OiJuZXdzIjtPOjEyOiJBcHBcRGF0YUl0ZW0iOjI6e3M6MjoiaWQiO047czo5OiIAKgBmaWVsZHMiO2E6NDp7czo0OiJsaW5rIjtzOjQ6Im5ld3MiO3M6NToidGl0bGUiO3M6MTE6Imtra3JycnJycnJyIjtzOjU6Im9yZGVyIjtzOjE6IjIiO3M6NDoidGV4dCI7czoyNDoiUEhBK1pXVmxaV1ZsWldWbFBDOXdQZz09Ijt9fXM6ODoiYWJvdXRfdXMiO086MTI6IkFwcFxEYXRhSXRlbSI6Mjp7czoyOiJpZCI7TjtzOjk6IgAqAGZpZWxkcyI7YTo0OntzOjQ6ImxpbmsiO3M6ODoiYWJvdXRfdXMiO3M6NToidGl0bGUiO3M6OToi0J4g0L3QsNGBIjtzOjU6Im9yZGVyIjtzOjE6IjMiO3M6NDoidGV4dCI7czozMjoiUEhBK1BHSSswSjRnMEwzUXNOR0JQQzlpUGp3dmNEND0iO319fXM6NToicGhvbmUiO3M6MDoiIjtzOjEwOiJzYWxlc291cmNlIjtzOjE6IjAiO30=');
INSERT INTO options (optname, optvalue) VALUES('sms', 'YToxMTp7czoxMjoic21zY2x1YnRva2VuIjtzOjA6IiI7czoxMjoic21zY2x1YmxvZ2luIjtzOjA6IiI7czoxMToic21zY2x1YnBhc3MiO3M6MDoiIjtzOjk6InNtc2NsdWJhbiI7czowOiIiO3M6MTA6InNtc2NsdWJ2YW4iO3M6MDoiIjtzOjEyOiJzbXNzZW15dG9rZW4iO3M6MDoiIjtzOjEyOiJzbXNzZW15ZGV2aWQiO3M6MDoiIjtzOjExOiJmbHlzbXNsb2dpbiI7czowOiIiO3M6MTA6ImZseXNtc3Bhc3MiO3M6MDoiIjtzOjg6ImZseXNtc2FuIjtzOjA6IiI7czo3OiJzbXN0eXBlIjtzOjE6IjAiO30=');
INSERT INTO options (optname, optvalue) VALUES('val', 'YToyOntzOjc6InZhbGxpc3QiO2E6MTp7aToxNjQyNjc1OTU1O086MTI6IkFwcFxEYXRhSXRlbSI6Mjp7czoyOiJpZCI7aToxNjQyNjc1OTU1O3M6OToiACoAZmllbGRzIjthOjM6e3M6NDoiY29kZSI7czozOiJVU0QiO3M6NDoibmFtZSI7czoxMDoi0JTQvtC70LDRgCI7czo0OiJyYXRlIjtzOjI6IjYwIjt9fX1zOjg6InZhbHByaWNlIjtpOjE7fQ==');
INSERT INTO options (optname, optvalue) VALUES('salary', 'YTo3OntzOjQ6ImNhbGMiO3M6MjE2OiIgLy/QstGB0YzQvtCz0L4g0L3QsNGA0LDRhdC+0LLQsNC90L4NCiAgdjIwMCA9ICB2MTA1DQoNCiAvL9C/0L7QtNCw0YLQutC4DQp2MjIwID0gIHYyMDAgKiAwLjE4DQp2MzAwID0gIHYyMDAgKiAwLjIyDQovL9Cy0YHRjNC+0LPQviDRg9GC0YDQuNC80LDQvdC+DQp2NjAwID12MjAwICAtIHYyMjAtIHYzMDANCi8v0L3QsCDRgNGD0LrQuA0KdjkwMCA9djIwMCAgLSB2NjAwLXY4NTAiO3M6ODoiY2FsY2Jhc2UiO3M6NjE6Ii8v0L7RgdC90L7QstC90LAgINC30LDRgNC/0LvQsNGC0LANCiB2MTA1PXRhc2tzdW0rc2VsbHZhbHVlDQoiO3M6MTM6ImNvZGViYXNlaW5jb20iO3M6MzoiMTA1IjtzOjEwOiJjb2RlcmVzdWx0IjtzOjM6IjkwMCI7czoxMToiY29kZWFkdmFuY2UiO3M6MToiMCI7czo4OiJjb2RlZmluZSI7czoxOiIwIjtzOjk6ImNvZGVib251cyI7czoxOiIwIjt9');
INSERT INTO options (optname, optvalue) VALUES('version', '6.18.0');





INSERT INTO keyval  (  keyd,vald)  VALUES ('cron','true');
INSERT INTO keyval  (  keyd,vald)  VALUES ('migrationbalans','done');
INSERT INTO keyval  (  keyd,vald)  VALUES ('migrationbonus','done');
INSERT INTO keyval  (  keyd,vald)  VALUES ('migration6118','done');
INSERT INTO keyval  (  keyd,vald)  VALUES ('migration12','done');
INSERT INTO keyval  (  keyd,vald)  VALUES ('migration180','done');
