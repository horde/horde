ALTER TABLE turba_objects ADD object_middlenames VARCHAR(255);
ALTER TABLE turba_objects ADD object_namesuffix VARCHAR(32);
ALTER TABLE turba_objects ADD object_homepob VARCHAR(10);
ALTER TABLE turba_objects ADD object_workpob VARCHAR(10);
ALTER TABLE turba_objects ADD object_tz VARCHAR(32);
ALTER TABLE turba_objects ADD object_geo VARCHAR(255);
ALTER TABLE turba_objects ADD object_logo BLOB;
ALTER TABLE turba_objects ADD object_logotype VARCHAR(10);

ALTER TABLE turba_objects CHANGE object_blobtype object_phototype VARCHAR(10);

CREATE INDEX turba_email_idx ON turba_objects (object_email);
CREATE INDEX turba_firstname_idx ON turba_objects (object_firstname);
CREATE INDEX turba_lastname_idx ON turba_objects (object_lastname);
