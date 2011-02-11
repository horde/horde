CREATE TABLE IF NOT EXISTS `folks_friends` (
  `user_uid` VARCHAR(32) NOT NULL,
  `group_id` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `friend_uid` VARCHAR(32) NOT NULL,
  `friend_ask` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY  (`user_uid`,`group_id`,`friend_uid`),
  KEY `user_uid` (`user_uid`),
  KEY `group_name` (`group_id`),
  KEY `friend_uid` (`friend_uid`),
  KEY `friend_ask` (`friend_ask`)
);


CREATE TABLE folks_shares (
  share_id INT(10) UNSIGNED NOT NULL,
  share_name VARCHAR(255) NOT NULL,
  share_owner VARCHAR(32) NOT NULL,
  share_flags TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  perm_creator TINYINT(2) UNSIGNED NOT NULL DEFAULT '0',
  perm_DEFAULT TINYINT(2) UNSIGNED NOT NULL DEFAULT '0',
  perm_guest TINYINT(2) UNSIGNED NOT NULL DEFAULT '0',
  attribute_name VARCHAR(255) NOT NULL,
  attribute_desc VARCHAR(255) NOT NULL,
  attribute_type TINYINT(1) NOT NULL,
  PRIMARY KEY  (share_id),
  KEY share_name (share_name),
  KEY share_owner (share_owner),
  KEY perm_creator (perm_creator),
  KEY perm_DEFAULT (perm_DEFAULT),
  KEY perm_guest (perm_guest),
  KEY attribute_type (attribute_type)
);

CREATE TABLE folks_shares_groups (
  share_id INT(10) UNSIGNED NOT NULL,
  group_uid INT(10) UNSIGNED NOT NULL,
  perm TINYINT(3) UNSIGNED NOT NULL,
  KEY share_id (share_id),
  KEY group_uid (group_uid),
  KEY perm (perm)
);

CREATE TABLE folks_shares_users (
  share_id INT(10) UNSIGNED NOT NULL,
  user_uid VARCHAR(32) NOT NULL,
  perm SMALLINT(5) UNSIGNED NOT NULL,
  KEY share_id (share_id),
  KEY user_uid (user_uid),
  KEY perm (perm)
);

