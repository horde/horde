CREATE TABLE skoli_classes_students (
    class_id VARCHAR(255) NOT NULL,
    student_id VARCHAR(255) NOT NULL
);

CREATE INDEX skoli_classlist_idx ON skoli_classes_students (class_id);
CREATE INDEX skoli_studentlist_idx ON skoli_classes_students (student_id);

CREATE TABLE skoli_objects (
    object_id VARCHAR(32) NOT NULL,
    object_owner VARCHAR(255) NOT NULL,
    object_uid VARCHAR(255) NOT NULL,
    class_id VARCHAR(255) NOT NULL,
    student_id VARCHAR(255) NOT NULL,
    object_time INT NOT NULL,
    object_type VARCHAR(255) NOT NULL,
    PRIMARY KEY (object_id)
);

CREATE INDEX skoli_objectlist_idx ON skoli_objects (object_owner);
CREATE INDEX skoli_uid_idx ON skoli_objects (object_uid);
CREATE INDEX skoli_classlist_idx ON skoli_objects (class_id);
CREATE INDEX skoli_studentlist_idx ON skoli_objects (student_id);

CREATE TABLE skoli_object_attributes (
    object_id VARCHAR(32) NOT NULL,
    attr_name VARCHAR(50) NOT NULL,
    attr_value VARCHAR(255),
    PRIMARY KEY (object_id, attr_name)
);
CREATE INDEX skoli_object_attributes_object_idx ON skoli_object_attributes (object_id);

CREATE TABLE skoli_shares (
    share_id INT NOT NULL,
    share_name VARCHAR(255) NOT NULL,
    share_owner VARCHAR(32) NOT NULL,
    share_flags SMALLINT NOT NULL DEFAULT 0,
    perm_creator SMALLINT NOT NULL DEFAULT 0,
    perm_default SMALLINT NOT NULL DEFAULT 0,
    perm_guest SMALLINT NOT NULL DEFAULT 0,
    attribute_name VARCHAR(255) NOT NULL,
    attribute_desc VARCHAR(255),
    attribute_school VARCHAR(255) NOT NULL,
    attribute_grade VARCHAR(255),
    attribute_semester VARCHAR(255),
    attribute_start INT NOT NULL,
    attribute_end INT NOT NULL,
    attribute_category VARCHAR(255) NULL,
    attribute_location VARCHAR(255),
    attribute_marks VARCHAR(255),
    attribute_address_book VARCHAR(255) NOT NULL,
    PRIMARY KEY (share_id)
);

CREATE INDEX skoli_shares_share_name_idx ON skoli_shares (share_name);
CREATE INDEX skoli_shares_share_owner_idx ON skoli_shares (share_owner);
CREATE INDEX skoli_shares_perm_creator_idx ON skoli_shares (perm_creator);
CREATE INDEX skoli_shares_perm_default_idx ON skoli_shares (perm_default);
CREATE INDEX skoli_shares_perm_guest_idx ON skoli_shares (perm_guest);
CREATE INDEX skoli_shares_attribute_category_idx ON skoli_shares (attribute_category);
CREATE INDEX skoli_shares_attribute_address_book_idx ON skoli_shares (attribute_address_book);

CREATE TABLE skoli_shares_groups (
    share_id INT NOT NULL,
    group_uid VARCHAR(255) NOT NULL,
    perm SMALLINT NOT NULL
);

CREATE INDEX skoli_shares_groups_share_id_idx ON skoli_shares_groups (share_id);
CREATE INDEX skoli_shares_groups_group_uid_idx ON skoli_shares_groups (group_uid);
CREATE INDEX skoli_shares_groups_perm_idx ON skoli_shares_groups (perm);

CREATE TABLE skoli_shares_users (
    share_id INT NOT NULL,
    user_uid VARCHAR(255) NOT NULL,
    perm SMALLINT NOT NULL
);

CREATE INDEX skoli_shares_users_share_id_idx ON skoli_shares_users (share_id);
CREATE INDEX skoli_shares_users_user_uid_idx ON skoli_shares_users (user_uid);
CREATE INDEX skoli_shares_users_perm_idx ON skoli_shares_users (perm);
