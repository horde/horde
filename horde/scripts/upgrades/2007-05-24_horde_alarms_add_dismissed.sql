ALTER TABLE horde_alarms ADD alarm_dismissed SMALLINT DEFAULT 0 NOT NULL;
CREATE INDEX alarm_dismissed_idx ON horde_alarms (alarm_dismissed);
