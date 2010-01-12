ALTER TABLE whups_versions ADD COLUMN version_active INT;
UPDATE whups_versions SET version_active = 1;
ALTER TABLE whups_versions ALTER COLUMN version_active SET DEFAULT 1;
CREATE INDEX whups_versions_active_idx ON whups_versions (version_active);
