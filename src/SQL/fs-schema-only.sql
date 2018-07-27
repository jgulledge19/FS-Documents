# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.7.18-0ubuntu0.16.04.1)
# Database: test
# Generation Time: 2018-07-27 03:56:18 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table document_dates
# ------------------------------------------------------------

DROP TABLE IF EXISTS `document_dates`;

CREATE TABLE `document_dates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `key` varchar(32) NOT NULL DEFAULT '',
  `value` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Document` (`document_id`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table document_ints
# ------------------------------------------------------------

DROP TABLE IF EXISTS `document_ints`;

CREATE TABLE `document_ints` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `key` varchar(32) NOT NULL DEFAULT '',
  `value` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Document` (`document_id`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table document_strings
# ------------------------------------------------------------

DROP TABLE IF EXISTS `document_strings`;

CREATE TABLE `document_strings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `key` varchar(32) NOT NULL DEFAULT '',
  `value` mediumtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Document` (`document_id`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table documents
# ------------------------------------------------------------

DROP TABLE IF EXISTS `documents`;

CREATE TABLE `documents` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT NULL,
  `last_exported` datetime DEFAULT NULL,
  `owner` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
