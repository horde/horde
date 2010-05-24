CREATE TABLE horde_perms (
    perm_id NUMBER(16) NOT NULL,
    perm_name VARCHAR2(255) NOT NULL UNIQUE,
    perm_parents VARCHAR2(255) NOT NULL,
    perm_data CLOB,
    PRIMARY KEY (perm_id)
);

CREATE SEQUENCE horde_perms_id_seq;
CREATE TRIGGER horde_perms_id_trigger
BEFORE INSERT ON horde_perms
FOR EACH ROW
BEGIN
SELECT horde_perms_id_seq.nextval INTO :new.permid FROM dual;
END;