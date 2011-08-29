-- $Horde: sam/scripts/sql/amavisd_sql.mysql.sql,v 1.4 2006/08/21 05:59:04 selsky Exp $

-- local users
CREATE TABLE users (
  id         INT UNSIGNED NOT NULL auto_increment,
  policy_id  INT UNSIGNED DEFAULT 1 NOT NULL,
  email      VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  KEY email (email)
);
CREATE UNIQUE INDEX users_idx_email ON users(email);

-- any e-mail address, external or local, used as senders in wblist
CREATE TABLE mailaddr (
  id         INT UNSIGNED NOT NULL auto_increment,
  email      VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  KEY email (email)
);
CREATE UNIQUE INDEX mailaddr_idx_email ON mailaddr(email);

-- per-recipient whitelist and/or blacklist,
-- puts sender and recipient in relation wb  (white or blacklisted sender)
CREATE TABLE wblist (
  rid        INT UNSIGNED NOT NULL,     -- recipient: users.id
  sid        INT UNSIGNED NOT NULL,     -- sender:    mailaddr.id
  wb         CHAR(1)      NOT NULL,     -- W or Y / B or N
  PRIMARY KEY (rid,sid)
);

CREATE TABLE policy (
  id                   INT UNSIGNED NOT NULL auto_increment,
  policy_name          VARCHAR(255), -- not used by amavisd-new

  virus_lover          CHAR(1),      -- Y/N
  spam_lover           CHAR(1),      -- Y/N  (optional field)
  banned_files_lover   CHAR(1),      -- Y/N  (optional field)
  bad_header_lover     CHAR(1),      -- Y/N  (optional field)

  bypass_virus_checks  CHAR(1),      -- Y/N
  bypass_spam_checks   CHAR(1),      -- Y/N
  bypass_banned_checks CHAR(1),      -- Y/N  (optional field)
  bypass_header_checks CHAR(1),      -- Y/N  (optional field)
 
  spam_modifies_subj   CHAR(1),      -- Y/N  (optional field)
  spam_quarantine_to   VARCHAR(64) DEFAULT NULL,   -- (optional field)
  spam_tag_level       FLOAT,                      -- higher score inserts spam info headers
  spam_tag2_level      FLOAT       DEFAULT NULL,   -- higher score inserts
                                                   -- 'declared spam' info header fields
  spam_kill_level      FLOAT,                      -- higher score activates evasive actions, e.g.
                                                   -- reject/drop, quarantine, ...
                                                   -- (subject to final_spam_destiny setting)
  addr_extension_spam   VARCHAR(32),               -- extension to add to the localpart of an
                                                   -- address for detected spam
  addr_extension_virus  VARCHAR(32),               -- extension to add to the localpart of an
                                                   -- address for detected viruses
  addr_extension_banned VARCHAR(32),               -- extension to add to the localpart of an
                                                   -- address for detected banned files
  PRIMARY KEY (id)
);
CREATE UNIQUE INDEX policy_idx_policy_name ON policy(policy_name);
