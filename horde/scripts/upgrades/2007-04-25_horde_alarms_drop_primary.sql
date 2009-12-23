ALTER TABLE horde_alarms DROP PRIMARY KEY;
CREATE INDEX alarm_id_idx ON horde_alarms (alarm_id);
