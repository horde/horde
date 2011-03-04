<?php
/**
 * Create Hermes base tables
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
        $this->changeColumn('hermes_timeslices', 'timeslice_id', 'primaryKey');
        try {
            $this->dropTable('hermes_timeslices_seq');
        } catch (Horde_Db_Exception $e) {
        }
        $this->changeColumn('hermes_jobtypes', 'jobtype_id', 'primaryKey');
        try {
            $this->dropTable('hermes_jobtypes_seq');
        } catch (Horde_Db_Exception $e) {
        }
        $this->changeColumn('hermes_deliverables', 'deliverable_id', 'primaryKey');
        try {
            $this->dropTable('hermes_deliverables_seq');
        } catch (Horde_Db_Exception $e) {
        }
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
