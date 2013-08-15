CREATE TABLE IF NOT EXISTS `places` (
  `id` int(9) NOT NULL AUTO_INCREMENT,
  `approved` int(1) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `type` varchar(60) NOT NULL,
  `lat` float NOT NULL,
  `lng` float NOT NULL,
  `address` varchar(400) NOT NULL,
  `uri` varchar(200) NOT NULL,
  `description` varchar(500) NOT NULL,
  `sector` varchar(50) NOT NULL,
  `owner_name` varchar(100) NOT NULL,
  `owner_email` varchar(100) NOT NULL,
  `hiring` int(1) DEFAULT NULL,
  `hirelink` varchar(200) DEFAULT NULL,
  `hiredate` varchar(14) DEFAULT NULL,
  `employeenum` int(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

