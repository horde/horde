CREATE TABLE ansel_faces (
    face_id              INT NOT NULL,
    image_id             INT NOT NULL,
    gallery_id           INT NOT NULL,
    face_name            VARCHAR(255) NOT NULL,
    face_x1              INT NOT NULL,
    face_y1              INT NOT NULL,
    face_x2              INT NOT NULL,
    face_y2              INT NOT NULL,
    face_signature       BLOB,
--
    PRIMARY KEY  (face_id)
);

CREATE TABLE ansel_faces_index (
    face_id INT NOT NULL,
    index_position INT NOT NULL,
    index_part BLOB
);
CREATE INDEX ansel_faces_index_face_id_idx ON ansel_faces_index (face_id);
CREATE INDEX ansel_faces_index_index_part_idx ON ansel_faces_index (index_part (30));
CREATE INDEX ansel_faces_index_index_position_idx ON ansel_faces_index (index_position);

ALTER TABLE ansel_shares ADD COLUMN attribute_faces INT NOT NULL;
ALTER TABLE ansel_images ADD COLUMN image_faces INT NOT NULL;