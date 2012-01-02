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
class KronolithUpgradeAddZoom extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $t = $this->_connection->table('kronolith_events_geo');
        $cols = $t->getColumns();
        if (!in_array('event_zoom', array_keys($cols))) {
            $this->addColumn('kronolith_events_geo', 'event_zoom', 'integer', array('default' => 0, 'null' => false));
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->removeColumn('kronolith_events_geo', 'event_zoom');
    }

}