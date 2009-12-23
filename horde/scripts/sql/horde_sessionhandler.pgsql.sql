CREATE TABLE horde_sessionhandler (
    session_id             VARCHAR(32) NOT NULL,
    session_lastmodified   INT NOT NULL,
    session_data           TEXT,
    PRIMARY KEY (session_id)
);

CREATE INDEX session_lastmodified_idx ON horde_sessionhandler (session_lastmodified);
