ALTER TABLE ansel_shares ALTER attribute_view_mode TYPE VARCHAR(255);
ALTER TABLE ansel_shares ALTER attribute_view_mode SET DEFAULT 'Normal';
UPDATE ansel_shares SET attribute_view_mode = 'Normal' WHERE attribute_view_mode = '0';
UPDATE ansel_shares SET attribute_view_mode = 'Date' WHERE attribute_view_mode = '1';
ALTER TABLE ansel_shares ALTER attribute_view_mode SET NOT NULL;
