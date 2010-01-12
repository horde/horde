CREATE TABLE IF NOT EXISTS `folks_blacklist` (
  `user_uid` VARCHAR(32) NOT NULL,
  `friend_uid` VARCHAR(32) NOT NULL,
  PRIMARY KEY  (`user_uid`, `friend_uid`)
);

INSERT folks_blacklist (`user_uid`, `friend_uid`)
    SELECT `user_uid`, `friend_uid` FROM `folks_friends` WHERE `group_id` = 1;

DELETE FROM folks_friends  WHERE `group_id` = 1;