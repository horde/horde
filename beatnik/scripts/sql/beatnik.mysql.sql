--
-- Table structure for table `beatnik_a`
--

CREATE TABLE `beatnik_a` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `zonename` varchar(255) NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `ipaddr` varchar(255) NOT NULL,
  `ttl` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `beatnik_cname`
--

CREATE TABLE `beatnik_cname` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `zonename` varchar(255) NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `pointer` varchar(255) NOT NULL,
  `ttl` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `beatnik_mx`
--

CREATE TABLE `beatnik_mx` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `zonename` varchar(255) NOT NULL,
  `pointer` varchar(255) NOT NULL,
  `pref` varchar(255) NOT NULL,
  `ttl` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `beatnik_ns`
--

CREATE TABLE `beatnik_ns` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `zonename` varchar(255) NOT NULL,
  `hostname` varchar(255) default NULL,
  `pointer` varchar(255) default '',
  `ttl` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `beatnik_ptr`
--

CREATE TABLE `beatnik_ptr` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `zonename` varchar(255) NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `pointer` varchar(255) NOT NULL,
  `ttl` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `beatnik_soa`
--

CREATE TABLE `beatnik_soa` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `zonename` varchar(255) NOT NULL,
  `zonens` varchar(255) NOT NULL,
  `zonecontact` varchar(255) default NULL,
  `serial` varchar(255) default NULL,
  `refresh` int(10) unsigned default NULL,
  `retry` int(10) unsigned default NULL,
  `expire` varchar(255) default NULL,
  `minimum` varchar(255) default NULL,
  `ttl` int(11) NOT NULL default '3600',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `zonename` (`zonename`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=1;

-- --------------------------------------------------------

--
-- Table structure for table `beatnik_srv`
--

CREATE TABLE `beatnik_srv` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `zonename` varchar(255) NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `pointer` varchar(255) NOT NULL,
  `priority` varchar(255) NOT NULL,
  `weight` varchar(255) NOT NULL,
  `port` varchar(255) NOT NULL,
  `ttl` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `beatnik_txt`
--

CREATE TABLE `beatnik_txt` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `zonename` varchar(255) NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `text` varchar(255) NOT NULL,
  `ttl` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
