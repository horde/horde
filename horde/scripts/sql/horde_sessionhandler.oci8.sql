CREATE TABLE horde_sessionhandler (
    session_id             VARCHAR2(32) NOT NULL,
    session_lastmodified   NUMBER(16) NOT NULL,
    session_data           BLOB,
--
    PRIMARY KEY (session_id)
);

CREATE INDEX session_lastmodified_idx ON horde_sessionhandler (session_lastmodified);
