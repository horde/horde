-- Simple upgrade script to remove the NOT NULL constraint on the
-- default schema's object_lastname field.

ALTER TABLE turba_objects MODIFY object_lastname VARCHAR(255);

-- For posgresql:
-- ALTER TABLE turba_objects ALTER object_lastname DROP NOT NULL;
