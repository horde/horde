DROP INDEX syncml_cuid_idx;
DROP INDEX syncml_suid_idx;

ALTER TABLE horde_syncml_map RENAME syncml_syncpartner TO syncml_syncpartner_copy;
ALTER TABLE horde_syncml_map ADD COLUMN syncml_syncpartner VARCHAR(255);
ALTER TABLE horde_syncml_map ALTER syncml_syncpartner SET NOT NULL;
UPDATE horde_syncml_map SET syncml_syncpartner = syncml_syncpartner_copy;
ALTER TABLE horde_syncml_map DROP COLUMN syncml_syncpartner_copy;

ALTER TABLE horde_syncml_map RENAME syncml_db TO syncml_db_copy;
ALTER TABLE horde_syncml_map ADD COLUMN syncml_db VARCHAR(255);
ALTER TABLE horde_syncml_map ALTER syncml_db SET NOT NULL;
UPDATE horde_syncml_map SET syncml_db = syncml_db_copy;
ALTER TABLE horde_syncml_map DROP COLUMN syncml_db_copy;

ALTER TABLE horde_syncml_map RENAME syncml_uid TO syncml_uid_copy;
ALTER TABLE horde_syncml_map ADD COLUMN syncml_uid VARCHAR(255);
ALTER TABLE horde_syncml_map ALTER syncml_uid SET NOT NULL;
UPDATE horde_syncml_map SET syncml_uid = syncml_uid_copy;
ALTER TABLE horde_syncml_map DROP COLUMN syncml_uid_copy;

ALTER TABLE horde_syncml_map RENAME syncml_cuid TO syncml_cuid_copy;
ALTER TABLE horde_syncml_map ADD COLUMN syncml_cuid VARCHAR(255);
UPDATE horde_syncml_map SET syncml_cuid = syncml_cuid_copy;
ALTER TABLE horde_syncml_map DROP COLUMN syncml_cuid_copy;

ALTER TABLE horde_syncml_map RENAME syncml_suid TO syncml_suid_copy;
ALTER TABLE horde_syncml_map ADD COLUMN syncml_suid VARCHAR(255);
UPDATE horde_syncml_map SET syncml_suid = syncml_suid_copy;
ALTER TABLE horde_syncml_map DROP COLUMN syncml_suid_copy;

CREATE INDEX syncml_syncpartner_idx ON horde_syncml_map (syncml_syncpartner);
CREATE INDEX syncml_db_idx ON horde_syncml_map (syncml_db);
CREATE INDEX syncml_uid_idx ON horde_syncml_map (syncml_uid);
CREATE INDEX syncml_cuid_idx ON horde_syncml_map (syncml_cuid);
CREATE INDEX syncml_suid_idx ON horde_syncml_map (syncml_suid);
