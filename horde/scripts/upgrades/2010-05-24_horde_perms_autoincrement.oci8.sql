CREATE SEQUENCE horde_perms_id_seq;
CREATE TRIGGER horde_perms_id_trigger
BEFORE INSERT ON horde_perms
FOR EACH ROW
BEGIN
SELECT horde_perms_id_seq.nextval INTO :new.permid FROM dual;
END;
