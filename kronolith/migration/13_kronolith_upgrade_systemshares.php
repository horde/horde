<?php
/**
 * Adds url field
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class KronolithUpgradeSystemShares extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('kronolith_shares', 'share_owner', 'string', array('limit' => 255));
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('kronolith_shares', 'share_owner', 'string', array('limit' => 255, 'null' => false));
    }

}