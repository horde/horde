CREATE SEQUENCE horde_perms_id_seq;
ALTER TABLE horde_perms ALTER COLUMN perm_id SET DEFAULT NEXTVAL('horde_perms_id_seq');
