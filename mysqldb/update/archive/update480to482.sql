
update    documents set  payed = payamount  where meta_id in(select meta_id from metadata where  meta_name='Order') ;

INSERT INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 1, 'Товарно-транспортная накладная', 'TTN', 'Продажи', 0);

CREATE TABLE `shop_images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `content` longblob NOT NULL,
  `mime` varchar(16) DEFAULT NULL,
  `thumb` longblob,
  PRIMARY KEY (`image_id`)
)   DEFAULT CHARSET=utf8; 



