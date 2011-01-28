<?php
/**
 * Change page_id column to autoincrement.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Wicked
 */
class WickedAutoIncrementPageId extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('wicked_pages', 'page_id', 'integer', array('autoincrement' => true, 'default' => null));
        try {
            $this->dropTable('wicked_pages_seq');
        } catch (Horde_Db_Exception $e) {
        }
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        $this->changeColumn('wicked_pages', 'page_id', 'integer', array('autoincrement' => false));
    }
}
