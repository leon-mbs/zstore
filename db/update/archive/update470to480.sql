CREATE TABLE `ppo_zformstat` (
  `zf_id` int(11) NOT NULL AUTO_INCREMENT,
  `pos_id` int(11) NOT NULL,
  `checktype` int(11) NOT NULL,
  `createdon` datetime NOT NULL,
  `document_number` varchar(255) NOT NULL,
  `amount0` decimal(10,2) NOT NULL,
  `amount1` decimal(10,2) NOT NULL,
  `amount2` decimal(10,2) NOT NULL,
  `amount3` decimal(10,2) NOT NULL,
  PRIMARY KEY (`zf_id`)
)   DEFAULT CHARSET=utf8;

