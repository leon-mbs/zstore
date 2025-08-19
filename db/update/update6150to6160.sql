
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  3, 'Реєстр ПН', 'TaxInvoiceList', '',    0);
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  1, 'Податкова накладна', 'TaxInvoice', 'Продажі',    0);
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  1, 'Додаток2 до ПН', 'TaxInvoice2', 'Продажі',    0);
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  1, 'Вхідна ПН', 'TaxInvoiceIncome', 'Закупівлі',     0);

delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','6.16.0'); 


Нужно настроить виндовый хостинг. 
поставить апач mysql php8.1 phpmyadmin 
настроить  сайт наружу. на сайт (на  апач)  поставить питон и джанго.
точные версии по  ходу дела

ну  и доступ  по  RDP  (продублировать анидеском на  всякий  случай)

Самого хостинга  пока  нет. поэтому нужен исполнитель  который порекомендует хостинг (в украине желательно) 
с которым  с  одной  стороны работал  с другой стороны хостинг не сильно дорогой и не  геморный в настройке (привязке доменного имени например)- 
это нужно  для  демонстрационных  проектов  - то есть минимальный  план  по ресурсам 


