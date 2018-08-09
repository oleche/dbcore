USE `demo`;

CREATE TABLE IF NOT EXISTS `demo_user` (
  `username` varchar(15) NOT NULL,
  `name` varchar(45) NOT NULL,
  `lastname` varchar(45) DEFAULT NULL,
  `email` varchar(70) NOT NULL,
  `password` varchar(40) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY (`username`),
  KEY `user_ibfk_1` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `user_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

ALTER TABLE `demo_user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`type`) REFERENCES `user_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

INSERT INTO `user_type` (`id`, `name`) VALUES
  (1, 'User A'),
  (2, 'User B'),
  (3, 'User C');
