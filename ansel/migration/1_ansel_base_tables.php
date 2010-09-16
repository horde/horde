<?php
/**
 * Create Ansel base tables (as of Ansel 1.1.1).
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Ansel
 */
class AnselBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        // Create: ansel_images
        $t = $this->createTable('ansel_images', array('primaryKey' => 'image_id'));
        $t->column('image_id', 'bigint', array('null' => false));
        $t->column('gallery_id', 'bigint', array('null' => false));
        $t->column('image_filename', 'string', array('limit' => 255, 'null' => false));
        $t->column('image_type', 'string', array('limit' => 100, 'null' => false));
        $t->column('image_caption', 'text');
        $t->column('image_uploaded_date', 'bigint', array('null' => false));
        $t->column('image_original_date', 'bigint', array('null' => false));
        $t->column('image_sort', 'integer', array('null' => false));
        $t->column('image_faces', 'integer', array('null' => false, 'default' => 0));
        $t->column('image_latitude', 'string', array('limit' => 32));
        $t->column('image_longitude', 'string', array('limit' => 32));
        $t->column('image_location', 'string', array('limit' => 255));
        $t->column('image_geotag_date', 'bigint');
        $t->end();

        $this->addIndex('ansel_images', array('gallery_id'));
        $this->addIndex('ansel_images', array('image_id', 'gallery_id'));
        $this->addIndex('ansel_images', array('image_uploaded_date'));
        $this->addIndex('ansel_images', array('image_original_date'));

        // Create: ansel_image_attributes
        //$t = $this->createTable('ansel_image_attributes', array('primaryKey' => 'image_id, attr_name'));
        $t = $this->createTable('ansel_image_attributes');
        $t->column('image_id', 'bigint', array('null' => false));
        $t->column('attr_name', 'string', array('null' => false, 'limit' => 50));
        $t->column('attr_value', 'string', array('limit' => 255));
        $t->end();

        $this->addIndex('ansel_image_attributes', array('image_id'));

        // Create: ansel_faces
        $t = $this->createTable('ansel_faces', array('primaryKey' => 'face_id'));
        $t->column('face_id', 'bigint', array('null' => false));
        $t->column('image_id', 'bigint', array('null' => false));
        $t->column('gallery_id', 'bigint', array('null' => false));
        $t->column('face_name', 'string', array('limit' => 255));
        $t->column('face_x1', 'integer', array('null' => false));
        $t->column('face_y1', 'integer', array('null' => false));
        $t->column('face_x2', 'integer', array('null' => false));
        $t->column('face_y2', 'integer', array('null' => false));
        $t->column('face_signature', 'binary');
        $t->end();

        $this->addIndex('ansel_faces', array('image_id'));
        $this->addIndex('ansel_faces', array('gallery_id'));

        // Create: ansel_faces_index
        $t = $this->createTable('ansel_faces_index');
        $t->column('face_id', 'bigint', array('null' => false));
        $t->column('index_position', 'integer', array('null' => false));
        $t->column('index_part', 'binary');
        $t->end();

        $this->addIndex('ansel_faces_index', array('face_id'));
        // Doesn't look like we can specify the length of the field to index..
        // at least in mysql
        //$this->addIndex('ansel_faces_index', array('index_part (30)'));
        $this->addIndex('ansel_faces_index', array('index_position'));

        // Create: ansel_shares
        $t = $this->createTable('ansel_shares', array('primaryKey' => 'share_id'));
        $t->column('share_id', 'bigint', array('null' => false));
        $t->column('share_owner', 'string', array('limit' => 255, 'null' => false));
        $t->column('share_parents', 'string', array('limit' => 255));
        $t->column('perm_creator', 'integer', array('null' => false));
        $t->column('perm_default', 'integer', array('null' => false));
        $t->column('perm_guest', 'integer', array('null' => false));
        $t->column('share_flags', 'integer', array('null' => false, 'default' => 0));
        $t->column('attribute_name', 'string', array('limit' => 255, 'null' => false));
        $t->column('attribute_desc', 'text');
        $t->column('attribute_default', 'integer');
        $t->column('attribute_default_type', 'string', array('limit' => 6));
        $t->column('attribute_default_prettythumb', 'text');
        $t->column('attribute_style', 'string', array('limit' => 255));
        $t->column('attribute_category', 'string', array('limit' => 255, 'null' => false, 'default' => ''));
        $t->column('attribute_last_modified', 'bigint');
        $t->column('attribute_date_created', 'bigint');
        $t->column('attribute_images', 'integer', array('null' => false, 'default' => 0));
        $t->column('attribute_has_subgalleries', 'integer', array('null' => false, 'default' => 0));
        $t->column('attribute_slug', 'string', array('limit' => 255));
        $t->column('attribute_age', 'integer', array('null' => false, 'default' => 0));
        $t->column('attribute_download', 'string', array('limit' => 255));
        $t->column('attribute_passwd', 'string', array('limit' => 255));
        $t->column('attribute_faces', 'integer', array('null' => false, 'default' => 0));
        $t->column('attribute_view_mode', 'string', array('limit' => 255, 'default' => 'Normal', 'null' => false));
        $t->end();

        $this->addIndex('ansel_shares', array('share_owner'));
        $this->addIndex('ansel_shares', array('perm_creator'));
        $this->addIndex('ansel_shares', array('perm_default'));
        $this->addIndex('ansel_shares', array('perm_guest'));
        $this->addIndex('ansel_shares', array('attribute_category'));
        $this->addIndex('ansel_shares', array('share_parents'));

        // Create: ansel_shares_groups
        $t = $this->createTable('ansel_shares_groups');
        $t->column('share_id', 'bigint', array('null' => false));
        $t->column('group_uid', 'bigint', array('null' => false));
        $t->column('perm', 'integer', array('null' => false));
        $t->end();

        $this->addIndex('ansel_shares_groups', array('share_id'));
        $this->addIndex('ansel_shares_groups', array('group_uid'));
        $this->addIndex('ansel_shares_groups', array('perm'));

        // Create: ansel_shares_users
        $t = $this->createTable('ansel_shares_users');
        $t->column('share_id', 'bigint', array('null' => false));
        $t->column('user_uid', 'bigint', array('null' => false));
        $t->column('perm', 'integer', array('null' => false));
        $t->end();

        $this->addIndex('ansel_shares_users', array('share_id'));
        $this->addIndex('ansel_shares_users', array('user_uid'));
        $this->addIndex('ansel_shares_users', array('perm'));

        // Create: ansel_images_geolocation
        $t = $this->createTable('ansel_images_geolocation', array('primaryKey' => 'image_id'));
        $t->column('image_id', 'bigint', array('null' => false));
        $t->column('image_latitude', 'string', array('limit' => 32));
        $t->column('image_longitude', 'string', array('limit' => 32));
        $t->column('image_location', 'string', array('limit' => 255));
        $t->end();

        // Create: ansel_tags (Deprecated in 2.0)
        $t = $this->createTable('ansel_tags', array('primaryKey' => 'tag_id'));
        $t->column('tag_id', 'integer', array('null' => false));
        $t->column('tag_name', 'string', array('limit' => 255, 'null' => false));
        $t->end();

        // Create: ansel_galleries_tags (Deprecated in 2.0)
        $t = $this->createTable('ansel_galleries_tags');
        $t->column('gallery_id', 'integer', array('null' => false));
        $t->column('tag_id', 'integer', array('null' => false));
        $t->end();

        // Create: ansel_images_tags (Deprecated in 2.0)
        $t = $this->createTable('ansel_images_tags');
        $t->column('image_id', 'integer', array('null' => false));
        $t->column('tag_id', 'integer', array('null' => false));
        $t->end();
    }

    /**
     * Downgrade
     *
     */
    public function down()
    {
        $this->dropTable('ansel_images');
        $this->dropTable('ansel_image_attributes');
        $this->dropTable('ansel_faces');
        $this->dropTable('ansel_faces_index');
        $this->dropTable('ansel_shares');
        $this->dropTable('ansel_shares_groups');
        $this->dropTable('ansel_shares_users');
        $this->dropTable('ansel_images_geolocation');
        $this->dropTable('ansel_tags');
        $this->dropTable('ansel_galleries_tags');
        $this->dropTable('ansel_images_tags');
    }

}
