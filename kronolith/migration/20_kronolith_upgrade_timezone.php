<?php
/**
 * Adds timezone fields to events.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class KronolithUpgradeTimezone extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->addColumn('kronolith_events', 'event_timezone', 'string', array('limit' => 50));
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->removeColumn('kronolith_events', 'event_timezone');
    }
}
