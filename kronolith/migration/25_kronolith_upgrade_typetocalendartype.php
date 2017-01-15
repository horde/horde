<?php
/**
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */

require_once __DIR__ . '/../lib/Kronolith.php';

/**
 * Add hierarchcal related columns to the legacy sql share driver
 *
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class KronolithUpgradeTypeToCalendarType extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->renameColumn('kronolith_sharesng', 'attribute_type', 'attribute_calendar_type');
        $this->renameColumn('kronolith_shares', 'attribute_type',  'attribute_calendar_type');
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->renameColumn('kronolith_sharesng', 'attribute_calendar_type', 'attribute_type');
        $this->renameColumn('kronolith_shares', 'attribute_calendar_type', 'attribute_type');
    }

}
