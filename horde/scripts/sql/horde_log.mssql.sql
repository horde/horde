CREATE TABLE horde_log (
    id          INT NOT NULL,
    logtime     TIMESTAMP NOT NULL,
    ident       CHAR(16) NOT NULL,
    priority    INT NOT NULL,
    -- For DBs that don't support the VARCHAR(MAX) field type:
    -- message  VARCHAR(2048),
    message     VARCHAR(MAX),
    PRIMARY KEY (id)
);
