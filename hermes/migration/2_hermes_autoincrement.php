<?php
/**
 * Create Hermes base tables
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Hermes
 */
class HermesAutoincrement extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('hermes_timeslices', 'timeslice_id', 'integer', array('autoincrement' => true, 'null' => false, 'default' => null));
        $this->changeColumn('hermes_jobtypes', 'jobtype_id', 'integer', array('autoincrement' => true, 'null' => false, 'default' => null));
        $this->changeColumn('hermes_deliverables', 'deliverable_id', 'integer', array('autoincrement' => true, 'null' => false, 'default' => null));

    }

    /**
     * Downgrade
     *
     */
    public function down()
    {
        $this->changeColumn('hermes_timeslices', 'timeslice_id', 'integer', array('autoincrement' => false, 'null' => false, 'default' => null));
        $this->changeColumn('hermes_jobtypes', 'jobtype_id', 'integer', array('autoincrement' => false, 'null' => false, 'default' => null));
        $this->changeColumn('hermes_deliverables', 'deliverable_id', 'integer', array('autoincrement' => false, 'null' => false, 'default' => null));

    }

}
