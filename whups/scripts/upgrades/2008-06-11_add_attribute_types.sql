ALTER TABLE whups_attributes_desc CHANGE COLUMN attribute_description attribute_description VARCHAR(255);
ALTER TABLE whups_attributes_desc ADD COLUMN attribute_type VARCHAR(255) DEFAULT 'text';
ALTER TABLE whups_attributes_desc ADD COLUMN attribute_params TEXT;
ALTER TABLE whups_attributes_desc ADD COLUMN attribute_required SMALLINT;
