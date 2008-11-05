
CREATE TABLE IF NOT EXISTS folks_online (
  user_uid varchar(32) NOT NULL default '',
  ip_address char(16) NOT NULL,
  time_last_click int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (user_uid,ip_address)
) TYPE=HEAP;

UPDATE folks_users SET last_online_on = (
    SELECT time_last_click FROM folks_online WHERE
        folks_users.user_uid = folks_online.user_uid
        GROUP BY folks_online.user_uid
);

DELETE FROM folks_online WHERE time_last_click < UNIX_TIMESTAMP() - 480;
