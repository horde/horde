<?php
/**
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */

require_once __DIR__ . '/../lib/Turba.php';

/**
 * Fixes the type of the parents column.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */
class TurbaUpgradeParents extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('turba_shares', 'share_parents', 'string', array('limit' => 4000));
        $this->changeColumn('turba_sharesng', 'share_parents', 'string', array('limit' => 4000));
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('turba_shares', 'share_parents', 'text');
        $this->changeColumn('turba_sharesng', 'share_parents', 'text');
    }
}
