ALTER TABLE horde_alarms DROP PRIMARY KEY;
ALTER TABLE horde_alarms ADD PRIMARY KEY (alarm_id, alarm_uid);
