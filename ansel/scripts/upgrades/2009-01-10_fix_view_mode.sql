ALTER TABLE ansel_shares CHANGE attribute_view_mode attribute_view_mode VARCHAR(255) DEFAULT 'Normal' NOT NULL;

UPDATE ansel_shares SET attribute_view_mode = 'Normal' WHERE attribute_view_mode = '0';
UPDATE ansel_shares SET attribute_view_mode = 'Date' WHERE attribute_view_mode = '1';

