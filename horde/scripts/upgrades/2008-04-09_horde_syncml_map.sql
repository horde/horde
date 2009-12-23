CREATE TABLE horde_syncml_anchors(
    syncml_syncpartner  VARCHAR(255),
    syncml_db           VARCHAR(255),
    syncml_uid          VARCHAR(255),
    syncml_clientanchor VARCHAR(255),
    syncml_serveranchor VARCHAR(255)
);

CREATE INDEX syncml_anchors_syncpartner_idx ON horde_syncml_anchors (syncml_syncpartner);
CREATE INDEX syncml_anchors_db_idx ON horde_syncml_anchors (syncml_db);
CREATE INDEX syncml_anchors_uid_idx ON horde_syncml_anchors (syncml_uid);
