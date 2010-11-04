<?php
/**
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Kronolith
 */
class KronolithUpgradeAddAllDay extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $t = $this->_connection->table('kronolith_events');
        $cols = $t->getColumns();
        if (!in_array('event_allday', array_keys($cols))) {
            $this->addColumn('kronolith_events', 'event_allday', 'integer', array('default' => 0));
            $this->_connection->execute('UPDATE kronolith_events SET event_allday = 1 WHERE event_start + ' . Horde_SQL::buildIntervalClause($this->_connection, '1 DAY') . ' = event_end');
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