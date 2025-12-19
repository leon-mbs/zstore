SET NAMES 'utf8'; 

 товары при заказе  которых не  было на  складе
 товары с задерэкой после  заказа
 задержка с заявки до полчения
 задерэеки по  поставщиком  после  оплат
 
INSERT INTO metadata (meta_type, description, meta_name, menugroup, disabled) VALUES( 2, 'Обмеження системи', 'Toc', 'Аналітика', 0);


UPDATE metadata set  disabled=1 where meta_name='PredSell';
 

delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','8.1.0'); 
