
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  3, 'Реєстр ПН', 'TaxInvoiceList', '',    0);
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  1, 'Податкова накладна', 'TaxInvoice', 'Продажі',    0);
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  1, 'Додаток2 до ПН', 'TaxInvoice2', 'Продажі',    0);
INSERT INTO metadata (meta_type, description, meta_name,  menugroup,   disabled) VALUES(  1, 'Вхідна ПН', 'TaxInvoiceIncome', 'Закупівлі',     0);

delete from options where  optname='version' ;
insert into options (optname,optvalue) values('version','6.16.0'); 

