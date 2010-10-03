<?php
/**
 * Upgrade for autoincrement
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
class AnselUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('ansel_images', 'image_id', 'bigint', array('null' => false, 'autoincrement' => true));
        $this->changeColumn('ansel_faces', 'face_id', 'bigint', array('null' => false, 'autoincrement' => true));
        $this->changeColumn('ansel_shares', 'share_id', 'bigint', array('null' => false, 'autoincrement' => true));
        $this->changeColumn('ansel_tags', 'tag_id', 'integer', array('null' => false, 'autoincrement' => true));
    }

    public function down()
    {
        $this->changeColumn('ansel_images', 'image_id', 'bigint', array('null' => false, 'autoincrement' => false));
        $this->changeColumn('ansel_faces', 'face_id', 'bigint', array('null' => false, 'autoincrement' => false));
        $this->changeColumn('ansel_shares', 'share_id', 'bigint', array('null' => false, 'autoincrement' => false));
        $this->changeColumn('ansel_tags', 'tag_id', 'integer', array('null' => false, 'autoincrement' => false));
    }

}