--
-- $Horde$
--
CREATE TABLE crumb_clients (
    client_id         INT NOT NULL,
    turba_uid         VARCHAR(255),
    whups_queue       INT,
    group_id          VARCHAR(255),

    PRIMARY KEY       (client_id)
);

