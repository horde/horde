<?php
/**
 * Adds recurrency columns to the tasks table.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
class NagUpgradeRecurrence extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->addColumn('nag_tasks', 'task_recurtype', 'integer', array('default' => 0));
        $this->addColumn('nag_tasks', 'task_recurinterval', 'integer');
        $this->addColumn('nag_tasks', 'task_recurdays', 'integer');
        $this->addColumn('nag_tasks', 'task_recurenddate', 'datetime');
        $this->addColumn('nag_tasks', 'task_recurcount', 'integer');
        $this->addColumn('nag_tasks', 'task_exceptions', 'text');
        $this->addColumn('nag_tasks', 'task_completions', 'text');
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        $this->removeColumn('nag_tasks', 'task_recurtype');
        $this->removeColumn('nag_tasks', 'task_recurinterval');
        $this->removeColumn('nag_tasks', 'task_recurdays');
        $this->removeColumn('nag_tasks', 'task_recurenddate');
        $this->removeColumn('nag_tasks', 'task_recurcount');
        $this->removeColumn('nag_tasks', 'task_exceptions');
        $this->removeColumn('nag_tasks', 'task_completions');
    }
}
