CREATE SEQUENCE horde_group_uid_seq;
CREATE TRIGGER horde_group_uid_trigger
BEFORE INSERT ON horde_groups
FOR EACH ROW
BEGIN
SELECT horde_group_uid_seq.nextval INTO :new.group_uid FROM dual;
END;
