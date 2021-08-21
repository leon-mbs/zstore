 
 
 
 
CREATE TABLE `prodproc` (
  `pp_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `state` smallint(4) DEFAULT 0,
  `detail` LONGTEXT DEFAULT NULL,
   PRIMARY KEY (`pp_id`)
  
) engine=InnoDB DEFAULT CHARSET=utf8;
     
  
 CREATE TABLE `prodstage` (
  `st_id` int(11) NOT NULL AUTO_INCREMENT,
  `pp_id` int(11) NOT NULL ,
  `pa_id` int(11) NOT NULL ,
  `startdate` DateTime  NOT NULL,
  `enddate` DateTime  NOT NULL,
  `name` varchar(255) NOT NULL,
  `detail` LONGTEXT DEFAULT NULL,
   KEY (`pp_id`) ,
   PRIMARY KEY (`st_id`)  ,
   CONSTRAINT `st_ibfk_1` FOREIGN KEY (`pp_id`) REFERENCES `prodproc` (`pp_id`) 
) engine=InnoDB DEFAULT CHARSET=utf8;


CREATE VIEW  prodstage_view
AS
    SELECT
      `ps`.`st_id` AS `st_id`,
      `ps`.`pp_id` AS `pp_id`,
      `ps`.`pa_id` AS `pa_id`,
      `ps`.`startdate` AS `startdate`,
      `ps`.`enddate` AS `enddate`,
      `ps`.`name` AS `name`,
      `ps`.`detail` AS `detail`,
      `pr`.`name` AS `procname`,
      `pa`.`pa_name` AS `pa_name`
    FROM ((`prodstage` `ps`
      JOIN `prodproc` `pr`
        ON ((`pr`.`pp_id` = `ps`.`pp_id`)))
      JOIN `parealist` `pa`
        ON ((`pa`.`pa_id` = `ps`.`pa_id`))); 
 
  
 INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, '��. ��������', 'ProdProcList', '������������', 0);
 INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 3, '��. �����', 'ProdProcList', '������������', 0);
 
 
  
������ ��������, ��������������  ������
�����  ����  ���� ��� �������
�������  ������� � ����  ���  ��  ���������  �����
�����  ��������. ������  ���� ��� ����������

����� �� �������� �������  �������  ������������  ��������� ���  ��������
  

���������������� ���� - ������ ���� ���� ��������
����  ������������  ������ 
� �������  �������������  ������  ������


������  ������ ���������  ��    ��������  � ��������  ������

��� ��������� ����� �� ������ ������� �������  ������������
������� ���� ������� �� ������������ �� ������� ����� 


��������  ��������  � ������������� - ��������  ����������  �  �����
���� �� �����  �  ������
������� ��  ����� �������  �������  ������������ � ������� ����
�����������  �  ������������� (�  ����� �����?)
 

 
    