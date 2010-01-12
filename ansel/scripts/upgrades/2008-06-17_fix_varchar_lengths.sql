ALTER TABLE ansel_shares CHANGE share_owner share_owner VARCHAR(255);
ALTER TABLE ansel_shares CHANGE attribute_style attribute_style VARCHAR(255);
ALTER TABLE ansel_shares_users CHANGE user_uid user_uid VARCHAR(255);
ALTER TABLE ansel_faces CHANGE face_name face_name VARCHAR(255) NOT NULL;