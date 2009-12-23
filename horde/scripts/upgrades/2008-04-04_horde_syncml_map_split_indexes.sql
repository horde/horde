ALTER TABLE horde_syncml_map DROP INDEX syncml_cuid_idx;
ALTER TABLE horde_syncml_map DROP INDEX syncml_suid_idx;

ALTER TABLE horde_syncml_map CHANGE COLUMN syncml_syncpartner syncml_syncpartner VARCHAR(255) NOT NULL;
ALTER TABLE horde_syncml_map CHANGE COLUMN syncml_db syncml_db VARCHAR(255) NOT NULL;
ALTER TABLE horde_syncml_map CHANGE COLUMN syncml_uid syncml_uid VARCHAR(255) NOT NULL;
ALTER TABLE horde_syncml_map CHANGE COLUMN syncml_cuid syncml_cuid VARCHAR(255);
ALTER TABLE horde_syncml_map CHANGE COLUMN syncml_suid syncml_suid VARCHAR(255);

CREATE INDEX syncml_syncpartner_idx ON horde_syncml_map (syncml_syncpartner);
CREATE INDEX syncml_db_idx ON horde_syncml_map (syncml_db);
CREATE INDEX syncml_uid_idx ON horde_syncml_map (syncml_uid);
CREATE INDEX syncml_cuid_idx ON horde_syncml_map (syncml_cuid);
CREATE INDEX syncml_suid_idx ON horde_syncml_map (syncml_suid);
