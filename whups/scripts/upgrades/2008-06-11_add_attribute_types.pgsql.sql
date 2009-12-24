BEGIN;
ALTER TABLE whups_attributes_desc ADD COLUMN attribute_description_new VARCHAR(255);
UPDATE whups_attributes_desc SET attribute_description_new = attribute_description;
ALTER TABLE whups_attributes_desc DROP attribute_description;
ALTER TABLE whups_attributes_desc RENAME attribute_description_new TO attribute_description;
COMMIT;

BEGIN;
ALTER TABLE whups_attributes_desc ADD COLUMN attribute_type VARCHAR(255);
ALTER TABLE whups_attributes_desc ALTER COLUMN attribute_type SET DEFAULT 'text';
COMMIT;

ALTER TABLE whups_attributes_desc ADD COLUMN attribute_params TEXT;
ALTER TABLE whups_attributes_desc ADD COLUMN attribute_required SMALLINT;
