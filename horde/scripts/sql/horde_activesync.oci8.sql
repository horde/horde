CREATE TABLE horde_activesync_state (
    sync_time          NUMBER(16),
    sync_key           VARCHAR2(255) NOT NULL,
    sync_data          CLOB,
    sync_devid         VARCHAR2(255),
    sync_folderid      VARCHAR2(255),
    sync_user          VARCHAR2(255) NOT NULL,
--
    PRIMARY KEY (sync_key)
);

CREATE INDEX activesync_state_folder_idx ON horde_activesync_state (sync_folderid);
CREATE INDEX activesync_state_devid_idx ON horde_activesync_state (sync_devid);

CREATE TABLE horde_activesync_map (
    message_uid        VARCHAR2(255) NOT NULL,
    sync_modtime       NUMBER(16),
    sync_key           VARCHAR2(255) NOT NULL,
    sync_devid         VARCHAR2(255) NOT NULL,
    sync_folderid      VARCHAR2(255) NOT NULL,
    sync_user          VARCHAR2(255) NOT NULL
);

CREATE INDEX activesync_map_devid_idx ON horde_activesync_map (sync_devid);
CREATE INDEX activesync_map_message_idx ON horde_activesync_map (message_uid);
CREATE INDEX activesync_map_user_idx ON horde_activesync_map (sync_user);


CREATE TABLE horde_activesync_device (
    device_id         VARCHAR2(255) NOT NULL,
    device_type       VARCHAR2(255) NOT NULL,
    device_agent      VARCHAR2(255) NOT NULL,
    device_supported  CLOB,
    device_policykey  NUMBER(16) DEFAULT 0,
    device_rwstatus   NUMBER(8),
--
    PRIMARY KEY (device_id)
);

CREATE TABLE horde_activesync_device_users (
    device_id         VARCHAR2(255) NOT NULL,
    device_user       VARCHAR2(255) NOT NULL,
    device_ping       CLOB,
    device_folders    CLOB
);
CREATE INDEX activesync_device_users_idx ON horde_activesync_device_users (device_user);
CREATE INDEX activesync_device_users_id_idx on horde_activesync_device_users (device_id);

exit
