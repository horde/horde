CREATE TABLE horde_sessionhandler (
    session_id             VARCHAR(32) NOT NULL,
    session_lastmodified   INT NOT NULL,
    session_data           LONGBLOB,

    PRIMARY KEY (session_id)
) ENGINE = InnoDB;

CREATE INDEX session_lastmodified_idx ON horde_sessionhandler (session_lastmodified);
