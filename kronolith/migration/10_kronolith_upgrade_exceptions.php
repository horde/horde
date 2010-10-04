<?php
/**
 * Adds url field
 *
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
class KronolithUpgradeExceptions extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $t = $this->_connection->table('kronolith_events');
        $cols = $t->getColumns();
        if (!in_array('event_baseid', array_keys($cols))) {
            $this->addColumn('kronolith_events', 'event_baseid', 'string', array('limit' => 255, 'default' => ''));
            $this->addColumn('kronolith_events', 'event_exceptionoriginaldate', 'datetime');
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->removeColumn('kronolith_events', 'event_baseid');
        $this->removeColumn('kronolith_events', 'event_exceptionoriginaldate');
    }

}