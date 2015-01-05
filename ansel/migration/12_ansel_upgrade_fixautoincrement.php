<?php
/**
 * Create Ansel base tables (as of Ansel 1.1.1).
 *
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class AnselUpgradeFixautoincrement extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('ansel_images', 'image_id', 'autoincrementKey');
        $this->changeColumn('ansel_faces', 'face_id', 'autoincrementKey');
        $this->changeColumn('ansel_shares', 'share_id', 'autoincrementKey');
    }

    /**
     * Downgrade
     *
     */
    public function down()
    {
        $this->changeColumn('ansel_images', 'image_id', 'integer', array('null' => false, 'autoincrement' => true, 'unsigned' => true));
        $this->changeColumn('ansel_faces', 'face_id', 'integer', array('null' => false, 'autoincrement' => true, 'unsigned' => true));
        $this->changeColumn('ansel_shares', 'share_id', 'bigint', array('null' => false, 'autoincrement' => true));
    }

}
