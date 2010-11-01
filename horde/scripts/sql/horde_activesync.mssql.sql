CREATE TABLE horde_activesync_state (
    sync_time          INT,
    sync_key           VARCHAR(255) NOT NULL,
    sync_data          TEXT,
    sync_devid         VARCHAR(255),
    sync_folderid      VARCHAR(255),
    sync_user          VARCHAR(255) NOT NULL,
--
    PRIMARY KEY (sync_key)
);
GO

CREATE INDEX activesync_state_folder_idx ON horde_activesync_state (sync_folderid);
CREATE INDEX activesync_state_devid_idx ON horde_activesync_state (sync_devid);
GO

CREATE TABLE horde_activesync_map (
    message_uid        VARCHAR(255) NOT NULL,
    sync_modtime       INT,
    sync_key           VARCHAR(255) NOT NULL,
    sync_devid         VARCHAR(255) NOT NULL,
    sync_folderid      VARCHAR(255) NOT NULL,
    sync_user          VARCHAR(255) NOT NULL
);
GO

CREATE INDEX activesync_map_user_idx ON horde_activesync_map (sync_user);
CREATE INDEX activesync_map_devid_idx ON horde_activesync_map (sync_devid);
CREATE INDEX activesync_map_message_idx ON horde_activesync_map (message_uid);
GO

CREATE TABLE horde_activesync_device (
    device_id         VARCHAR(255) NOT NULL,
    device_type       VARCHAR(255) NOT NULL,
    device_agent      VARCHAR(255) NOT NULL,
    device_supported  TEXT,
    device_policykey  BIGINT DEFAULT 0,
    device_rwstatus   INT,
--
    PRIMARY KEY (device_id)
);
GO

CREATE TABLE horde_activesync_device_users (
    device_id         VARCHAR(255) NOT NULL,
    device_user       VARCHAR(255) NOT NULL,
    device_ping       TEXT,
    device_folders    TEXT
);
GO

CREATE INDEX activesync_device_users_idx ON horde_activesync_device_users (device_user);
CREATE INDEX activesync_device_users_id_idx on horde_activesync_device_users (device_id);
GO
