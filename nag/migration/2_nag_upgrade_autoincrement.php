<?php
/**
 * Adds autoincrement flags
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Nag
 */
class NagUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('nag_shares', 'share_id', 'integer', array('null' => false, 'autoincrement' => true));
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('nag_shares', 'share_id', 'integer', array('null' => false, 'autoincrement' => false));
    }

}