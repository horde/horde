ALTER TABLE kronolith_events ADD event_private INT DEFAULT 0 NOT NULL;
ALTER TABLE kronolith_events ADD event_recurcount INT;

CREATE TABLE kronolith_shares (
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

CREATE INDEX kronolith_shares_share_name_idx ON kronolith_shares (share_name);
CREATE INDEX kronolith_shares_share_owner_idx ON kronolith_shares (share_owner);
CREATE INDEX kronolith_shares_perm_creator_idx ON kronolith_shares (perm_creator);
CREATE INDEX kronolith_shares_perm_default_idx ON kronolith_shares (perm_default);
CREATE INDEX kronolith_shares_perm_guest_idx ON kronolith_shares (perm_guest);

CREATE TABLE kronolith_shares_groups (
    share_id INT NOT NULL,
    group_uid INT NOT NULL,
    perm SMALLINT NOT NULL
);

CREATE INDEX kronolith_shares_groups_share_id_idx ON kronolith_shares_groups (share_id);
CREATE INDEX kronolith_shares_groups_group_uid_idx ON kronolith_shares_groups (group_uid);
CREATE INDEX kronolith_shares_groups_perm_idx ON kronolith_shares_groups (perm);

CREATE TABLE kronolith_shares_users (
    share_id INT NOT NULL,
    user_uid VARCHAR2(32) NOT NULL,
    perm SMALLINT NOT NULL
);

CREATE INDEX kronolith_shares_users_share_id_idx ON kronolith_shares_users (share_id);
CREATE INDEX kronolith_shares_users_user_uid_idx ON kronolith_shares_users (user_uid);
CREATE INDEX kronolith_shares_users_perm_idx ON kronolith_shares_users (perm);
