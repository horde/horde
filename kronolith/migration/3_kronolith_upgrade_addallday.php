<?php
/**
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
class KronolithUpgradeAddAllDay extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $t = $this->table('kronolith_events');
        $cols = $t->getColumns();
        if (!in_array('event_allday', array_keys($cols))) {
            $this->addColumn('kronolith_events', 'event_allday', 'integer', array('default' => 0));
            $this->execute('UPDATE kronolith_events SET event_allday = 1 WHERE ' . $this->modifyDate('event_start', '+', 1, 'DAY') . ' = event_end');
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->removeColumn('kronolith_events', 'event_allday');
    }
}