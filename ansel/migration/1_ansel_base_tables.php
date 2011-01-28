<?php
/**
 * Create Ansel base tables (as of Ansel 1.1.1).
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
        $tableList = $this->tables();

        if (!in_array('ansel_images', $tableList)) {
            // Create: ansel_images
            $t = $this->createTable('ansel_images', array('primaryKey' => false));
            $t->column('image_id', 'integer', array('null' => false));
            $t->column('gallery_id', 'integer', array('null' => false));
            $t->column('image_filename', 'string', array('limit' => 255, 'null' => false));
            $t->column('image_type', 'string', array('limit' => 100, 'null' => false));
            $t->column('image_caption', 'text');
            $t->column('image_uploaded_date', 'integer', array('null' => false));
            $t->column('image_original_date', 'integer', array('null' => false));
            $t->column('image_sort', 'integer', array('null' => false));
            $t->column('image_faces', 'integer', array('null' => false, 'default' => 0));
            $t->column('image_latitude', 'string', array('limit' => 32));
            $t->column('image_longitude', 'string', array('limit' => 32));
            $t->column('image_location', 'string', array('limit' => 255));
            $t->column('image_geotag_date', 'integer');
            $t->primaryKey(array('image_id'));
            $t->end();

            $this->addIndex('ansel_images', array('gallery_id'));
            $this->addIndex('ansel_images', array('image_id', 'gallery_id'));
            $this->addIndex('ansel_images', array('image_uploaded_date'));
            $this->addIndex('ansel_images', array('image_original_date'));
        }

        if (!in_array('ansel_image_attributes', $tableList)) {
            // Create: ansel_image_attributes
            //$t = $this->createTable('ansel_image_attributes', array('primaryKey' => 'image_id, attr_name'));
            $t = $this->createTable('ansel_image_attributes', array('primaryKey' => false));
            $t->column('image_id', 'integer', array('null' => false));
            $t->column('attr_name', 'string', array('null' => false, 'limit' => 50));
            $t->column('attr_value', 'string', array('limit' => 255));
            $t->primaryKey(array('image_id', 'attr_name'));
            $t->end();
            $this->addIndex('ansel_image_attributes', array('image_id'));
        }

        if (!in_array('ansel_faces', $tableList)) {
            // Create: ansel_faces
            $t = $this->createTable('ansel_faces', array('primaryKey' => false));
            $t->column('face_id', 'integer', array('null' => false));
            $t->column('image_id', 'integer', array('null' => false));
            $t->column('gallery_id', 'integer', array('null' => false));
            $t->column('face_name', 'string', array('limit' => 255));
            $t->column('face_x1', 'integer', array('null' => false));
            $t->column('face_y1', 'integer', array('null' => false));
            $t->column('face_x2', 'integer', array('null' => false));
            $t->column('face_y2', 'integer', array('null' => false));
            $t->column('face_signature', 'binary');
            $t->primaryKey(array('face_id'));
            $t->end();
            $this->addIndex('ansel_faces', array('image_id'));
            $this->addIndex('ansel_faces', array('gallery_id'));
        }

        if (!in_array('ansel_faces_index', $tableList)) {
            // Create: ansel_faces_index
            $t = $this->createTable('ansel_faces_index');
            $t->column('face_id', 'integer', array('null' => false));
            $t->column('index_position', 'integer', array('null' => false));
            $t->column('index_part', 'binary');
            $t->end();

            $this->addIndex('ansel_faces_index', array('face_id'));
            // Doesn't look like we can specify the length of the field to index..
            // at least in mysql
            //$this->addIndex('ansel_faces_index', array('index_part (30)'));
            $this->addIndex('ansel_faces_index', array('index_position'));
        }

        if (!in_array('ansel_shares', $tableList)) {
            // Create: ansel_shares
            $t = $this->createTable('ansel_shares', array('primaryKey' => false));
            $t->column('share_id', 'integer', array('null' => false));
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
            $t->column('attribute_last_modified', 'integer');
            $t->column('attribute_date_created', 'integer');
            $t->column('attribute_images', 'integer', array('null' => false, 'default' => 0));
            $t->column('attribute_has_subgalleries', 'integer', array('null' => false, 'default' => 0));
            $t->column('attribute_slug', 'string', array('limit' => 255));
            $t->column('attribute_age', 'integer', array('null' => false, 'default' => 0));
            $t->column('attribute_download', 'string', array('limit' => 255));
            $t->column('attribute_passwd', 'string', array('limit' => 255));
            $t->column('attribute_faces', 'integer', array('null' => false, 'default' => 0));
            $t->column('attribute_view_mode', 'string', array('limit' => 255, 'default' => 'Normal', 'null' => false));
            $t->primaryKey(array('share_id'));
            $t->end();

            $this->addIndex('ansel_shares', array('share_owner'));
            $this->addIndex('ansel_shares', array('perm_creator'));
            $this->addIndex('ansel_shares', array('perm_default'));
            $this->addIndex('ansel_shares', array('perm_guest'));
            $this->addIndex('ansel_shares', array('attribute_category'));
            $this->addIndex('ansel_shares', array('share_parents'));
        }

        if (!in_array('ansel_shares_groups', $tableList)) {
            // Create: ansel_shares_groups
            $t = $this->createTable('ansel_shares_groups');
            $t->column('share_id', 'integer', array('null' => false));
            $t->column('group_uid', 'integer', array('null' => false));
            $t->column('perm', 'integer', array('null' => false));
            $t->end();

            $this->addIndex('ansel_shares_groups', array('share_id'));
            $this->addIndex('ansel_shares_groups', array('group_uid'));
            $this->addIndex('ansel_shares_groups', array('perm'));
        }

        if (!in_array('ansel_shares_users', $tableList)) {
            // Create: ansel_shares_users
            $t = $this->createTable('ansel_shares_users');
            $t->column('share_id', 'integer', array('null' => false));
            $t->column('user_uid', 'integer', array('limit' => 255, 'null' => false));
            $t->column('perm', 'integer', array('null' => false));
            $t->end();

            $this->addIndex('ansel_shares_users', array('share_id'));
            $this->addIndex('ansel_shares_users', array('user_uid'));
            $this->addIndex('ansel_shares_users', array('perm'));
        }

        if (!in_array('ansel_images_geolocation', $tableList)) {
            // Create: ansel_images_geolocation
            $t = $this->createTable('ansel_images_geolocation', array('primaryKey' => false));
            $t->column('image_id', 'integer', array('null' => false));
            $t->column('image_latitude', 'string', array('limit' => 32));
            $t->column('image_longitude', 'string', array('limit' => 32));
            $t->column('image_location', 'string', array('limit' => 255));
            $t->primaryKey(array('image_id'));
            $t->end();
        }

        if (!in_array('ansel_tags', $tableList)) {
            // Create: ansel_tags (Deprecated in 2.0)
            $t = $this->createTable('ansel_tags', array('primaryKey' => false));
            $t->column('tag_id', 'integer', array('null' => false));
            $t->column('tag_name', 'string', array('limit' => 255, 'null' => false));
            $t->primaryKey(array('tag_id'));
            $t->end();
        }

        if (!in_array('ansel_galleries_tags', $tableList)) {
            // Create: ansel_galleries_tags (Deprecated in 2.0)
            $t = $this->createTable('ansel_galleries_tags');
            $t->column('gallery_id', 'integer', array('null' => false));
            $t->column('tag_id', 'integer', array('null' => false));
            $t->end();
        }

        if (!in_array('ansel_images_tags', $tableList)) {
            // Create: ansel_images_tags (Deprecated in 2.0)
            $t = $this->createTable('ansel_images_tags');
            $t->column('image_id', 'integer', array('null' => false));
            $t->column('tag_id', 'integer', array('null' => false));
            $t->end();
        }
    }

    /**
     * Downgrade
     *
     */
    public function down()
    {
        $tableList = $this->tables();

        $this->dropTable('ansel_images');
        $this->dropTable('ansel_image_attributes');
        $this->dropTable('ansel_faces');
        $this->dropTable('ansel_faces_index');
        $this->dropTable('ansel_shares');
        $this->dropTable('ansel_shares_groups');
        $this->dropTable('ansel_shares_users');
        $this->dropTable('ansel_images_geolocation');
        if (in_array('ansel_tags', $tableList)) {
            $this->dropTable('ansel_tags');
        }
        if (in_array('ansel_galleries_tags', $tableList)) {
            $this->dropTable('ansel_galleries_tags');
        }
        if (in_array('ansel_images_tags', $tableList)) {
            $this->dropTable('ansel_images_tags');
        }
    }

}
