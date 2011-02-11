CREATE TABLE IF NOT EXISTS folks_attributes (
  user_uid VARCHAR(32) NOT NULL,
  attributes_group VARCHAR(32) NOT NULL,
  attributes_key VARCHAR(20) NOT NULL,
  attributes_value VARCHAR(255) NOT NULL,
  KEY user_uid (user_uid),
  KEY attributes_group (attributes_group)
);

-- friends SQL
CREATE TABLE IF NOT EXISTS `folks_friends` (
  `user_uid` VARCHAR(32) NOT NULL,
  `friend_uid` VARCHAR(32) NOT NULL,
  `friend_ask` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY  (`user_uid`, `friend_uid`),
  KEY `friend_ask` (`friend_ask`)
);
-- BLACKLIST
CREATE TABLE IF NOT EXISTS `folks_blacklist` (
  `user_uid` VARCHAR(32) NOT NULL,
  `friend_uid` VARCHAR(32) NOT NULL,
  PRIMARY KEY  (`user_uid`, `friend_uid`)
);

CREATE TABLE IF NOT EXISTS folks_notify_counts (
  user_uid VARCHAR(32) NOT NULL,
  count_news SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
  count_galleries SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
  count_classifieds SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
  count_videos SMALLINT(6) UNSIGNED NOT NULL DEFAULT '0',
  count_attendances SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
  count_wishes SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
  count_blog SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY  (user_uid),
  KEY count_news (count_news),
  KEY count_galleries (count_galleries),
  KEY count_classifieds (count_classifieds),
  KEY count_videos (count_videos),
  KEY count_attendances (count_attendances),
  KEY count_wishes (count_wishes),
  KEY count_blog (count_blog)
);

CREATE TABLE IF NOT EXISTS folks_online (
  user_uid VARCHAR(32) NOT NULL DEFAULT '',
  ip_address CHAR(16) NOT NULL,
  time_last_click INT(11) UNSIGNED NOT NULL DEFAULT '0',
  KEY user_uid (user_uid),
  KEY ip_address (ip_address)
);

 CREATE TABLE folks_out (
  user_uid VARCHAR(32) NOT NULL ,
  out_from INT UNSIGNED NOT NULL ,
  out_to INT UNSIGNED NOT NULL ,
  out_desc VARCHAR(255) NOT NULL ,
  INDEX (user_uid , out_from , out_to)
);

CREATE TABLE IF NOT EXISTS folks_users (
  user_id MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_uid VARCHAR(32) NOT NULL,
  user_status VARCHAR(32) NOT NULL,
  user_comments VARCHAR(32) NOT NULL DEFAULT 'authenticated',
  user_url VARCHAR(100) NOT NULL,
  user_description text NOT NULL,
  user_password CHAR(32) NOT NULL,
  user_relationship TINYINT(1) NOT NULL DEFAULT '0',
  user_video SMALLINT(5) UNSIGNED NOT NULL,
  signup_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  signup_by VARCHAR(19) NOT NULL,
  user_email VARCHAR(100) NOT NULL,
  user_city VARCHAR(80) NOT NULL,
  user_country CHAR(2) NOT NULL,
  user_gender TINYINT(1) DEFAULT '0',
  user_birthday DATE NOT NULL DEFAULT '0000-00-00',
  user_picture TINYINT(1) DEFAULT '0',
  user_bookmarks SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
  user_vacation INT(10) UNSIGNED NOT NULL DEFAULT '0',
  last_login_on DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  last_login_by VARCHAR(19) NOT NULL,
  last_online VARCHAR(16) NOT NULL DEFAULT 'all',
  last_online_on INT(10) UNSIGNED NOT NULL DEFAULT '0',
  popularity TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  activity TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  activity_log VARCHAR(32) NOT NULL DEFAULT 'all',
  count_news SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
  count_galleries SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
  count_classifieds SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
  count_videos SMALLINT(6) UNSIGNED NOT NULL DEFAULT '0',
  count_attendances SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
  count_wishes SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
  count_blogs SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
  count_comments SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY  (user_id),
  UNIQUE KEY user_email (user_email),
  UNIQUE KEY user_uid (user_uid),
  KEY user_birthday (user_birthday),
  KEY user_city (user_city),
  KEY user_gender (user_gender),
  KEY user_video (user_video)
);

CREATE TABLE IF NOT EXISTS folks_views (
  user_uid VARCHAR(32) NOT NULL,
  view_uid VARCHAR(32) NOT NULL,
  view_time INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY  (view_uid, user_uid)
);

CREATE TABLE IF NOT EXISTS folks_testimonials (
  profile_uid VARCHAR(32) NOT NULL,
  user_uid VARCHAR(32) NOT NULL,
  testimonial text NOT NULL,
  PRIMARY KEY (profile_uid,user_uid)
);

CREATE TABLE folks_vacation (
  user_uid VARCHAR(32) NOT NULL ,
  start INT UNSIGNED NOT NULL ,
  end INT UNSIGNED NOT NULL ,
  subject VARCHAR(100) NOT NULL ,
  reason VARCHAR(255) NOT NULL ,
  PRIMARY KEY (user_uid)
);

CREATE TABLE folks_networks (
  user_uid VARCHAR(32) NOT NULL,
  network_link_name VARCHAR(255) NOT NULL,
  network_link VARCHAR(255) NOT NULL,
  network_name VARCHAR(255) NOT NULL,
  KEY user_uid (user_uid)
);

CREATE TABLE folks_search (
  user_uid VARCHAR(32) NOT NULL,
  search_name VARCHAR(32) NOT NULL,
  search_criteria text NOT NULL,
  KEY user_uid (user_uid)
);

CREATE TABLE folks_activity (
  user_uid varchar(32) NOT NULL,
  activity_message varchar(255) NOT NULL,
  activity_scope varchar(255) NOT NULL,
  activity_date int(10) unsigned NOT NULL,
  KEY user_uid (user_uid),
  KEY activity_date (activity_date)
);
