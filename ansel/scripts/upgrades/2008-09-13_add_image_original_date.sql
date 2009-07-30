ALTER TABLE ansel_images ADD COLUMN image_original_date INT NOT NULL;
ALTER TABLE ansel_images CHANGE COLUMN image_uploaded image_uploaded_date  INT NOT NULL;

CREATE INDEX ansel_images_original_idx ON ansel_images (image_original_date);
ALTER TABLE ansel_images DROP index ansel_images_uploaded_idx;
CREATE INDEX ansel_images_uploaded_date_idx ON ansel_images (image_uploaded_date);

ALTER TABLE ansel_shares ADD COLUMN attribute_view_mode INT NOT NULL;