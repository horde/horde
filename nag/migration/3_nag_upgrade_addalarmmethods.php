<?php
/**
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
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