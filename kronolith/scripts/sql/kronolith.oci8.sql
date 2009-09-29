CREATE TABLE kronolith_events (
    event_id VARCHAR2(32) NOT NULL,
    event_uid VARCHAR2(255) NOT NULL,
    calendar_id VARCHAR2(255) NOT NULL,
    event_creator_id VARCHAR2(255) NOT NULL,
    event_description VARCHAR2(4000),
    event_location VARCHAR2(4000),
    event_status INT DEFAULT 0,
    event_attendees VARCHAR2(4000),
    event_resources VARCHAR2(4000),
    event_exceptions VARCHAR2(4000),
    event_title VARCHAR2(255),
    event_recurtype INT DEFAULT 0,
    event_recurinterval INT,
    event_recurdays INT,
    event_recurenddate DATE,
    event_recurcount INT,
    event_start DATE,
    event_end DATE,
    event_allday INT DEFAULT 0,
    event_alarm INT DEFAULT 0,
    event_alarm_methods VARCHAR2(4000),
    event_modified INT NOT NULL,
    event_private INT DEFAULT 0 NOT NULL,
--
    PRIMARY KEY (event_id)
);

CREATE INDEX kronolith_calendar_idx ON kronolith_events (calendar_id);
CREATE INDEX kronolith_uid_idx ON kronolith_events (event_uid);


CREATE TABLE kronolith_storage (
    vfb_owner      VARCHAR2(255) DEFAULT NULL,
    vfb_email      VARCHAR2(255) DEFAULT '' NOT NULL,
    vfb_serialized VARCHAR2(4000) NOT NULL
);

CREATE INDEX kronolith_vfb_owner_idx ON kronolith_storage (vfb_owner);
CREATE INDEX kronolith_vfb_email_idx ON kronolith_storage (vfb_email);


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
    attribute_color VARCHAR2(7),
    PRIMARY KEY (share_id)
);

CREATE INDEX kronolith_shares_share_name_idx ON kronolith_shares (share_name);
CREATE INDEX kronolith_shares_share_owner_idx ON kronolith_shares (share_owner);
CREATE INDEX kronolith_shares_perm_creator_idx ON kronolith_shares (perm_creator);
CREATE INDEX kronolith_shares_perm_default_idx ON kronolith_shares (perm_default);
CREATE INDEX kronolith_shares_perm_guest_idx ON kronolith_shares (perm_guest);

CREATE TABLE kronolith_shares_groups (
    share_id INT NOT NULL,
    group_uid VARCHAR(255) NOT NULL,
    perm SMALLINT NOT NULL
);

CREATE INDEX kronolith_shares_groups_share_id_idx ON kronolith_shares_groups (share_id);
CREATE INDEX kronolith_shares_groups_group_uid_idx ON kronolith_shares_groups (group_uid);
CREATE INDEX kronolith_shares_groups_perm_idx ON kronolith_shares_groups (perm);

CREATE TABLE kronolith_shares_users (
    share_id INT NOT NULL,
    user_uid VARCHAR2(255) NOT NULL,
    perm SMALLINT NOT NULL
);

CREATE INDEX kronolith_shares_users_share_id_idx ON kronolith_shares_users (share_id);
CREATE INDEX kronolith_shares_users_user_uid_idx ON kronolith_shares_users (user_uid);
CREATE INDEX kronolith_shares_users_perm_idx ON kronolith_shares_users (perm);

CREATE TABLE kronolith_resources (
    resource_id INT NOT NULL,
    resource_name VARCHAR(255),
    resource_calendar VARCHAR(255),
    resource_description CLOB,
    resource_category VARCHAR(255) DEFAULT '',
    resource_response_type INT DEFAULT 0,
    resource_type VARCHAR(255) NOT NULL,
    resource_members CLOB,
    
    PRIMARY KEY (resource_id)
);
