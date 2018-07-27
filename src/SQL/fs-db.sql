# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.7.18-0ubuntu0.16.04.1)
# Database: test
# Generation Time: 2018-07-27 03:55:16 +0000
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

LOCK TABLES `document_dates` WRITE;
/*!40000 ALTER TABLE `document_dates` DISABLE KEYS */;

INSERT INTO `document_dates` (`id`, `document_id`, `key`, `value`)
VALUES
	(3,5,'myDate','1981-01-01 18:22:11'),
	(5,7,'myDate','1901-01-01 18:22:11'),
	(7,9,'myDate','1901-01-01 18:22:11'),
	(9,11,'myDate','1901-01-01 18:22:11'),
	(10,12,'myDate','1981-01-01 18:22:11'),
	(11,1,'myDate','1981-01-01 18:22:11'),
	(14,13,'myDate','1901-01-01 18:22:11'),
	(15,14,'myDate','1901-01-01 18:22:11');

/*!40000 ALTER TABLE `document_dates` ENABLE KEYS */;
UNLOCK TABLES;


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

LOCK TABLES `document_ints` WRITE;
/*!40000 ALTER TABLE `document_ints` DISABLE KEYS */;

INSERT INTO `document_ints` (`id`, `document_id`, `key`, `value`)
VALUES
	(5,5,'myKey',12349),
	(9,7,'myKey',1234),
	(10,7,'myKey2',5678),
	(13,9,'myKey',1234),
	(14,9,'myKey2',5678),
	(17,11,'myKey',1234),
	(18,11,'myKey2',5678),
	(19,12,'myKey',12349),
	(21,1,'myKey',12349),
	(22,1,'myKey3',9012),
	(23,12,'myKey3',9012),
	(24,5,'myKey3',9012),
	(25,13,'myKey',1234),
	(26,13,'myKey2',5678),
	(27,14,'myKey',1234),
	(28,14,'myKey2',5678);

/*!40000 ALTER TABLE `document_ints` ENABLE KEYS */;
UNLOCK TABLES;


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

LOCK TABLES `document_strings` WRITE;
/*!40000 ALTER TABLE `document_strings` DISABLE KEYS */;

INSERT INTO `document_strings` (`id`, `document_id`, `key`, `value`)
VALUES
	(2,5,'myString','This is only a test Update 3...'),
	(4,7,'myString','This is only a test...'),
	(6,9,'myString','This is only a test...'),
	(8,11,'myString','This is only a test...'),
	(9,12,'myString','This is only a test Update 3...'),
	(10,1,'myString','This is only a test Update...'),
	(11,13,'myString','This is only a test...'),
	(12,14,'myString','This is only a test...');

/*!40000 ALTER TABLE `document_strings` ENABLE KEYS */;
UNLOCK TABLES;


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

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;

INSERT INTO `documents` (`id`, `name`, `created`, `modified`, `last_exported`, `owner`)
VALUES
	(5,'Test 1, Update ','2018-07-26 02:49:31','2018-07-27 03:18:32',NULL,'user1'),
	(7,'Test 1','2018-07-26 02:49:34',NULL,NULL,'user2'),
	(9,'Test 1','2018-07-26 02:49:35',NULL,NULL,'user1'),
	(11,'Test 1','2018-07-26 02:49:37',NULL,NULL,'user1'),
	(12,'Test 1, Update ','2018-07-26 03:03:23','2018-07-27 00:01:33','2018-07-27 03:17:09','user2'),
	(13,'Test 1','2018-07-27 03:18:58',NULL,'2018-07-27 03:20:55','user1'),
	(14,'Test 1','2018-07-27 03:19:41',NULL,'2018-07-27 03:20:27','user2');

/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
