-- MySQL dump 10.11
--
-- Host: localhost    Database: eemaster
-- ------------------------------------------------------
-- Server version	5.0.27-standard

--
-- Table structure for table `exp_actions`
--

DROP TABLE IF EXISTS `exp_actions`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `exp_actions` (
  `action_id` int(4) unsigned NOT NULL auto_increment,
  `class` varchar(50) NOT NULL,
  `method` varchar(50) NOT NULL,
  PRIMARY KEY  (`action_id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
