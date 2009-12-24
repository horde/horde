-- $Horde: whups/scripts/upgrades/2008-04-29_add_sql_share_tables.sql,v 1.3 2009/10/20 21:28:28 jan Exp $

CREATE TABLE whups_shares (
    share_id INT NOT NULL,
    share_name VARCHAR(255) NOT NULL,
    share_owner VARCHAR(255) NOT NULL,
    share_flags SMALLINT DEFAULT 0 NOT NULL,
    perm_creator SMALLINT DEFAULT 0 NOT NULL,
    perm_default SMALLINT DEFAULT 0 NOT NULL,
    perm_guest SMALLINT DEFAULT 0 NOT NULL,
    attribute_name VARCHAR(255) NOT NULL,
    PRIMARY KEY (share_id)
);

CREATE INDEX whups_shares_share_name_idx ON whups_shares (share_name);
CREATE INDEX whups_shares_share_owner_idx ON whups_shares (share_owner);
CREATE INDEX whups_shares_perm_creator_idx ON whups_shares (perm_creator);
CREATE INDEX whups_shares_perm_default_idx ON whups_shares (perm_default);
CREATE INDEX whups_shares_perm_guest_idx ON whups_shares (perm_guest);

CREATE TABLE whups_shares_groups (
    share_id INT NOT NULL,
    group_uid INT NOT NULL,
    perm SMALLINT NOT NULL
);

CREATE INDEX whups_shares_groups_share_id_idx ON whups_shares_groups (share_id);
CREATE INDEX whups_shares_groups_group_uid_idx ON whups_shares_groups (group_uid);
CREATE INDEX whups_shares_groups_perm_idx ON whups_shares_groups (perm);

CREATE TABLE whups_shares_users (
    share_id INT NOT NULL,
    user_uid VARCHAR(32) NOT NULL,
    perm SMALLINT NOT NULL
);

CREATE INDEX whups_shares_users_share_id_idx ON whups_shares_users (share_id);
CREATE INDEX whups_shares_users_user_uid_idx ON whups_shares_users (user_uid);
CREATE INDEX whups_shares_users_perm_idx ON whups_shares_users (perm);
