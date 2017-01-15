<?php
/**
 * Fix column type of ansel_shares_users.user_uid.
 *
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class AnselUpgradeFixshareusers extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('ansel_shares_users', 'user_uid', 'string', array('limit' => 255, 'null' => false));

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
