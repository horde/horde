<?php
/**
 * Adds an index for the event_baseid column.
 *
 * Copyright 2015-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class KronolithUpgradeBaseidindex extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->addIndex('kronolith_events', 'event_baseid');
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        $this->removeIndex('kronolith_events', 'event_baseid');
    }
}
