CREATE TABLE horde_activesync_state (
    sync_time          INTEGER,
    sync_key           VARCHAR(255) NOT NULL,
    sync_data          TEXT,
    sync_devid         VARCHAR(255),
    sync_folderid      VARCHAR(255),
--
    PRIMARY KEY (sync_key)
);

CREATE INDEX activesync_state_folder_idx ON horde_activesync_state (sync_folderid);
CREATE INDEX activesync_state_devid_idx ON horde_activesync_state (sync_devid);

CREATE TABLE horde_activesync_map (
    message_uid        VARCHAR(255) NOT NULL,
    sync_modtime       INTEGER,
    sync_key           VARCHAR(255) NOT NULL,
    sync_devid         VARCHAR(255) NOT NULL,
    sync_folderid      VARCHAR(255) NOT NULL
);

CREATE INDEX activesync_map_devid_idx ON horde_activesync_map (sync_devid);
CREATE INDEX activesync_map_message_idx ON horde_activesync_map(message_uid);

CREATE TABLE horde_activesync_device (
    device_id         VARCHAR(255) NOT NULL,
    device_type       VARCHAR(255) NOT NULL,
    device_agent      VARCHAR(255) NOT NULL,
    device_ping       TEXT,
    device_policykey  BIGINT DEFAULT 0,
    device_rwstatus   INTEGER,
    device_folders    TEXT,

--
    PRIMARY KEY (device_id)
);
