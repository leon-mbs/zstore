SET NAMES 'utf8'; 

убрать  amount c меток

ALTER TABLE documents MODIFY COLUMN user_id int   default NULL;
 
delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','8.2.0'); 
