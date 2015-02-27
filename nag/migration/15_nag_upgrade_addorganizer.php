<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */

/**
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
class NagUpgradeAddOrganizer extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->addColumn('nag_tasks', 'task_organizer', 'string', array('limit' => 250));
        $this->addColumn('nag_tasks', 'task_status', 'integer', array('default' => 0));
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->removeColumn('nag_tasks', 'task_organizer');
        $this->removeColumn('nag_tasks', 'task_status');
    }
}
