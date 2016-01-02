<?php
/**
 * Copyright 2014-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */

/**
 * Fixes the type of the parents column.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class AnselUpgradeParents extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('ansel_shares', 'share_parents', 'string', array('limit' => 4000));
        $this->changeColumn('ansel_sharesng', 'share_parents', 'string', array('limit' => 4000));
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('ansel_shares', 'share_parents', 'text');
        $this->changeColumn('ansel_sharesng', 'share_parents', 'text');
    }
}
