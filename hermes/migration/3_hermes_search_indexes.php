<?php
/**
 * Adds indexes to the columns that are used for searching.
 *
 * Copyright 2015-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Hermes
 */
class HermesSearchIndexes extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->addIndex('hermes_timeslices', 'clientjob_id');
        $this->addIndex('hermes_timeslices', 'employee_id');
        $this->addIndex('hermes_timeslices', 'jobtype_id');
        $this->addIndex('hermes_timeslices', 'timeslice_isbillable');
        $this->addIndex('hermes_timeslices', 'timeslice_date');
        $this->addIndex('hermes_timeslices', 'timeslice_submitted');
        $this->addIndex('hermes_timeslices', 'timeslice_exported');
        $this->addIndex('hermes_timeslices', 'costobject_id');
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        $this->removeIndex('hermes_timeslices', 'clientjob_id');
        $this->removeIndex('hermes_timeslices', 'employee_id');
        $this->removeIndex('hermes_timeslices', 'jobtype_id');
        $this->removeIndex('hermes_timeslices', 'timeslice_isbillable');
        $this->removeIndex('hermes_timeslices', 'timeslice_date');
        $this->removeIndex('hermes_timeslices', 'timeslice_submitted');
        $this->removeIndex('hermes_timeslices', 'timeslice_exported');
        $this->removeIndex('hermes_timeslices', 'costobject_id');
    }
}
