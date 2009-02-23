CREATE TABLE ingo_rules (
    rule_id INT NOT NULL,
    rule_owner VARCHAR2(255) NOT NULL,
    rule_name VARCHAR2(255) NOT NULL,
    rule_action INT NOT NULL,
    rule_value VARCHAR2(255),
    rule_flags INT,
    rule_conditions CLOB,
    rule_combine INT,
    rule_stop INT,
    rule_active INT DEFAULT 1 NOT NULL,
    rule_order INT DEFAULT 0 NOT NULL,
--
    PRIMARY KEY (rule_id)
);

CREATE INDEX rule_owner_idx ON ingo_rules (rule_owner);


CREATE TABLE ingo_lists (
    list_owner VARCHAR2(255) NOT NULL,
    list_blacklist INT DEFAULT 0,
    list_address VARCHAR2(255) NOT NULL
);

CREATE INDEX list_idx ON ingo_lists (list_owner, list_blacklist);


CREATE TABLE ingo_forwards (
    forward_owner VARCHAR2(255) NOT NULL,
    forward_addresses CLOB,
    forward_keep INT DEFAULT 0 NOT NULL,
--
    PRIMARY KEY (forward_owner)
);


CREATE TABLE ingo_vacations (
    vacation_owner VARCHAR2(255) NOT NULL,
    vacation_addresses CLOB,
    vacation_subject VARCHAR2(255),
    vacation_reason CLOB,
    vacation_days INT DEFAULT 7,
    vacation_start INT,
    vacation_end INT,
    vacation_excludes CLOB,
    vacation_ignorelists INT DEFAULT 1,
--
    PRIMARY KEY (vacation_owner)
);


CREATE TABLE ingo_spam (
    spam_owner VARCHAR2(255) NOT NULL,
    spam_level INT DEFAULT 5,
    spam_folder VARCHAR2(255),
--
    PRIMARY KEY (spam_owner)
);


CREATE TABLE ingo_shares (
    share_id INT NOT NULL,
    share_name VARCHAR2(255) NOT NULL,
    share_owner VARCHAR2(255) NOT NULL,
    share_flags SMALLINT NOT NULL DEFAULT 0,
    perm_creator SMALLINT NOT NULL DEFAULT 0,
    perm_default SMALLINT NOT NULL DEFAULT 0,
    perm_guest SMALLINT NOT NULL DEFAULT 0,
    attribute_name VARCHAR2(255) NOT NULL,
    attribute_desc VARCHAR2(255),
    PRIMARY KEY (share_id)
);

CREATE INDEX ingo_shares_share_name_idx ON ingo_shares (share_name);
CREATE INDEX ingo_shares_share_owner_idx ON ingo_shares (share_owner);
CREATE INDEX ingo_shares_perm_creator_idx ON ingo_shares (perm_creator);
CREATE INDEX ingo_shares_perm_default_idx ON ingo_shares (perm_default);
CREATE INDEX ingo_shares_perm_guest_idx ON ingo_shares (perm_guest);

CREATE TABLE ingo_shares_groups (
    share_id INT NOT NULL,
    group_uid VARCHAR(255) NOT NULL,
    perm SMALLINT NOT NULL
);

CREATE INDEX ingo_shares_groups_share_id_idx ON ingo_shares_groups (share_id);
CREATE INDEX ingo_shares_groups_group_uid_idx ON ingo_shares_groups (group_uid);
CREATE INDEX ingo_shares_groups_perm_idx ON ingo_shares_groups (perm);

CREATE TABLE ingo_shares_users (
    share_id INT NOT NULL,
    user_uid VARCHAR2(255) NOT NULL,
    perm SMALLINT NOT NULL
);

CREATE INDEX ingo_shares_users_share_id_idx ON ingo_shares_users (share_id);
CREATE INDEX ingo_shares_users_user_uid_idx ON ingo_shares_users (user_uid);
CREATE INDEX ingo_shares_users_perm_idx ON ingo_shares_users (perm);
