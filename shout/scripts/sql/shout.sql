# Sequel Pro dump
# Version 1630
# http://code.google.com/p/sequel-pro
#
# Host: localhost (MySQL 5.1.45)
# Database: asterisk
# Generation Time: 2010-04-02 19:09:52 -0400
# ************************************************************

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table accounts
# ------------------------------------------------------------

DROP TABLE IF EXISTS `accounts`;

CREATE TABLE `accounts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `adminpin` varchar(12) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` (`id`,`name`,`code`,`adminpin`)
VALUES
	(1,'NONE','NONE','NONE');

/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table actions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `actions`;

CREATE TABLE `actions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

LOCK TABLES `actions` WRITE;
/*!40000 ALTER TABLE `actions` DISABLE KEYS */;
INSERT INTO `actions` (`id`,`name`)
VALUES
	(1,'jump'),
	(2,'ringexten'),
	(3,'leave_message'),
	(4,'conference'),
	(5,'directory'),
	(6,'dial'),
	(7,'rewind'),
	(8,'admin_login');

/*!40000 ALTER TABLE `actions` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table conferences
# ------------------------------------------------------------

DROP TABLE IF EXISTS `conferences`;

CREATE TABLE `conferences` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `room_number` mediumint(9) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `pin` mediumint(9) DEFAULT NULL,
  `options` varchar(50) DEFAULT NULL,
  `account_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `room_number` (`room_number`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



# Dump of table menu_entries
# ------------------------------------------------------------

DROP TABLE IF EXISTS `menu_entries`;

CREATE TABLE `menu_entries` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` int(11) unsigned NOT NULL,
  `digit` varchar(8) NOT NULL,
  `action_id` int(11) unsigned NOT NULL,
  `args` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



# Dump of table menus
# ------------------------------------------------------------

DROP TABLE IF EXISTS `menus`;

CREATE TABLE `menus` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int(11) unsigned NOT NULL,
  `name` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `recording_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

LOCK TABLES `menus` WRITE;
/*!40000 ALTER TABLE `menus` DISABLE KEYS */;
INSERT INTO `menus` (`id`,`account_id`,`name`,`description`,`recording_id`)
VALUES
	(1,1,'INACTIVE','Inactive Menu (default)',1);

/*!40000 ALTER TABLE `menus` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table numbers
# ------------------------------------------------------------

DROP TABLE IF EXISTS `numbers`;

CREATE TABLE `numbers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `did` varchar(25) NOT NULL,
  `account_id` int(11) unsigned NOT NULL,
  `menu_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



# Dump of table recordings
# ------------------------------------------------------------

DROP TABLE IF EXISTS `recordings`;

CREATE TABLE `recordings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(64) NOT NULL,
  `account_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

LOCK TABLES `recordings` WRITE;
/*!40000 ALTER TABLE `recordings` DISABLE KEYS */;
INSERT INTO `recordings` (`id`,`filename`,`account_id`)
VALUES
	(1,'NONE',1);

/*!40000 ALTER TABLE `recordings` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table sip_conf
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sip_conf`;

CREATE TABLE `sip_conf` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL DEFAULT '',
  `accountcode` varchar(20) DEFAULT NULL,
  `amaflags` varchar(7) DEFAULT NULL,
  `callgroup` varchar(10) DEFAULT NULL,
  `callerid` varchar(80) DEFAULT NULL,
  `canreinvite` varchar(3) DEFAULT 'yes',
  `context` varchar(80) DEFAULT NULL,
  `defaultip` varchar(15) DEFAULT NULL,
  `dtmfmode` varchar(7) DEFAULT 'rfc2833',
  `fromuser` varchar(80) DEFAULT NULL,
  `fromdomain` varchar(80) DEFAULT NULL,
  `host` varchar(31) NOT NULL DEFAULT '',
  `insecure` varchar(4) DEFAULT NULL,
  `language` varchar(2) DEFAULT NULL,
  `mailbox` varchar(50) DEFAULT NULL,
  `md5secret` varchar(80) DEFAULT NULL,
  `nat` varchar(5) NOT NULL DEFAULT 'no',
  `permit` varchar(95) DEFAULT NULL,
  `deny` varchar(95) DEFAULT NULL,
  `mask` varchar(95) DEFAULT NULL,
  `pickupgroup` varchar(10) DEFAULT NULL,
  `port` varchar(5) NOT NULL DEFAULT '',
  `qualify` varchar(3) DEFAULT NULL,
  `restrictcid` varchar(1) DEFAULT NULL,
  `rtptimeout` varchar(3) DEFAULT NULL,
  `rtpholdtimeout` varchar(3) DEFAULT NULL,
  `secret` varchar(80) DEFAULT NULL,
  `type` varchar(8) NOT NULL DEFAULT 'friend',
  `username` varchar(80) NOT NULL DEFAULT '',
  `disallow` varchar(100) DEFAULT 'all',
  `allow` varchar(100) DEFAULT 'ulaw;gsm',
  `musiconhold` varchar(100) DEFAULT NULL,
  `regseconds` bigint(20) NOT NULL DEFAULT '0',
  `ipaddr` varchar(15) NOT NULL DEFAULT '',
  `regexten` varchar(80) NOT NULL DEFAULT '',
  `cancallforward` varchar(3) DEFAULT 'yes',
  `lastms` int(11) NOT NULL DEFAULT '-1',
  `defaultuser` varchar(80) NOT NULL DEFAULT '',
  `useragent` varchar(100) DEFAULT NULL,
  `fullcontact` varchar(150) DEFAULT NULL,
  `regserver` varchar(80) DEFAULT NULL,
  `alias` varchar(80) DEFAULT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;






/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
