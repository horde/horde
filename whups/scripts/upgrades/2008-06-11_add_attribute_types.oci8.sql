ALTER TABLE whups_attributes_desc ADD COLUMN attribute_description_new VARCHAR2(255);
UPDATE whups_attributes_desc SET attribute_description_new = attribute_description;
ALTER TABLE whups_attributes_desc DROP COLUMN attribute_description;
ALTER TABLE whups_attributes_desc RENAME COLUMN attribute_description_new TO attribute_description;
ALTER TABLE whups_attributes_desc ADD COLUMN attribute_type VARCHAR2(255) DEFAULT 'text';
ALTER TABLE whups_attributes_desc ADD COLUMN attribute_params CLOB;
ALTER TABLE whups_attributes_desc ADD COLUMN attribute_required NUMBER(8);
