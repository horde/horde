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
class AnselUpgradeUnsignedints extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('ansel_images', 'image_id', 'integer', array('null' => false, 'autoincrement' => true, 'unsigned' => true));
        $this->changeColumn('ansel_images', 'gallery_id', 'bigint', array('null' => false));
        $this->changeColumn('ansel_images', 'image_uploaded_date', 'integer', array('null' => false, 'unsigned' => true));
        $this->changeColumn('ansel_images', 'image_original_date', 'integer', array('null' => false, 'unsigned' => true));
        $this->changeColumn('ansel_images', 'image_sort', 'integer', array('null' => false, 'unsigned' => true));
        $this->changeColumn('ansel_images', 'image_faces', 'integer', array('null' => false, 'default' => 0, 'unsigned' => true));
        $this->changeColumn('ansel_images', 'image_geotag_date', 'integer', array('unsigned' => true));
        
        $this->changeColumn('ansel_image_attributes', 'image_id', 'integer', array('null' => false, 'unsigned' => true));

        $this->changeColumn('ansel_faces', 'face_id', 'integer', array('null' => false, 'autoincrement' => true, 'unsigned' => true));
        $this->changeColumn('ansel_faces', 'image_id', 'integer', array('null' => false, 'unsigned' => true));
        $this->changeColumn('ansel_faces', 'gallery_id', 'bigint', array('null' => false));

        $this->changeColumn('ansel_faces_index', 'face_id', 'integer', array('null' => false, 'unsigned' => true));
        $this->changeColumn('ansel_faces_index', 'index_position', 'integer', array('null' => false, 'unsigned' => true));

        $this->changeColumn('ansel_shares', 'share_id', 'bigint', array('null' => false, 'autoincrement' => true));
        $this->changeColumn('ansel_shares', 'attribute_last_modified', 'integer', array('unsigned' => true));
        $this->changeColumn('ansel_shares', 'attribute_date_created', 'integer', array('unsigned' => true));
        $this->changeColumn('ansel_shares', 'attribute_images', 'integer', array('null' => false, 'default' => 0, 'unsigned' => true));
        $this->changeColumn('ansel_shares', 'attribute_faces', 'integer', array('null' => false, 'default' => 0, 'unsigned' => true));
       
        $this->changeColumn('ansel_shares_groups', 'group_uid', 'integer', array('null' => false, 'unsigned' => true));
       
        $this->changeColumn('ansel_shares_users', 'user_uid', 'integer', array('null' => false, 'unsigned' => true));
       
        $this->changeColumn('ansel_images_geolocation', 'image_id', 'integer', array('null' => false, 'unsigned' => true));
    }

    /**
     * Downgrade
     *
     */
    public function down()
    {
        // No need.
    }

}
