<?php
/**
 * Add individual alarm methods for tasks.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
class NagUpgradeAddAlarmMethods extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $t = $this->_connection->table('nag_tasks');
        $cols = $t->getColumns();
        if (!in_array('task_alarm_methods', array_keys($cols))) {
            $this->addColumn('nag_tasks', 'task_alarm_methods', 'text');
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->removeColumn('nag_tasks', 'task_alarm_methods');
    }

}