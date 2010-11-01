CREATE SEQUENCE horde_group_uid_seq;
ALTER TABLE horde_groups ALTER COLUMN group_uid SET DEFAULT NEXTVAL('horde_group_uid_seq');
