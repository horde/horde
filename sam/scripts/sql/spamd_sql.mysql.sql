-- $Horde: sam/scripts/sql/spamd_sql.mysql.sql,v 1.2 2006/03/22 18:41:32 jan Exp $

CREATE TABLE userpref (
    prefid int(11) NOT NULL auto_increment,
    username VARCHAR(255) NOT NULL,
    preference VARCHAR(30) NOT NULL,
    value VARCHAR(100) NOT NULL,

    PRIMARY KEY  (prefid),
    KEY username (username)
);
