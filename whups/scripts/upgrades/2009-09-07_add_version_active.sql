ALTER TABLE whups_versions ADD COLUMN version_active INT DEFAULT 1;
CREATE INDEX whups_versions_active_idx ON whups_versions (version_active);
