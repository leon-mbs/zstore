SET NAMES 'utf8'; 

убрать  amount c меток

 
delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','8.2.0'); 
