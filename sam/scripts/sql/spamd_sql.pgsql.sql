-- $Horde: sam/scripts/sql/spamd_sql.pgsql.sql,v 1.2 2006/12/13 04:58:20 chuck Exp $

CREATE TABLE userpref (
    prefid      SERIAL,
    username    VARCHAR(255) NOT NULL,
    preference  VARCHAR(30) NOT NULL,
    value       VARCHAR(100) NOT NULL,

    PRIMARY KEY (prefid)
);
