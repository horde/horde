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
class HermesBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('hermes_timeslices', $tableList)) {
            // Create: hermes_timeslice
            $t = $this->createTable('hermes_timeslices', array('primaryKey' => false));
            $t->column('timeslice_id', 'integer', array('null' => false));
            $t->column('clientjob_id', 'string', array('limit' => 255, 'null' => false));
            $t->column('employee_id', 'string', array('limit' => 255, 'null' => false));
            $t->column('jobtype_id', 'integer', array('null' => false));
            $t->column('timeslice_hours', 'decimal', array('precision' => 10, 'scale' => 2, 'null' => false));
            $t->column('timeslice_rate', 'decimal', array('precision' => 10, 'scale' => 2));
            $t->column('timeslice_isbillable', 'integer', array('default' => 0, 'null' => false));
            $t->column('timeslice_date', 'integer', array('null' => false));
            $t->column('timeslice_description', 'text', array('null' => false));
            $t->column('timeslice_note', 'text');
            $t->column('timeslice_submitted', 'integer', array('default' => 0, 'null' => false));
            $t->column('timeslice_exported', 'integer', array('default' => 0, 'null' => false));
            $t->column('costobject_id', 'string', array('limit' => 255));
            $t->primaryKey(array('timeslice_id'));
            $t->end();
        }

        if (!in_array('hermes_jobtypes', $tableList)) {
            // Create: hermes_jobtypes
            $t = $this->createTable('hermes_jobtypes', array('primaryKey' => false));
            $t->column('jobtype_id', 'integer', array('null' => false));
            $t->column('jobtype_name', 'string', array('limit' => 255));
            $t->column('jobtype_enabled', 'integer', array('default' => 1, 'null' => false));
            $t->column('jobtype_rate', 'decimal', array('precision' => 10, 'scale' => 2));
            $t->column('jobtype_billable', 'integer', array('default' => 0, 'null' => false));
            $t->primaryKey(array('jobtype_id'));
            $t->end();
        }

        if (!in_array('hermes_clientjobs', $tableList)) {
            // Create: hermes_clientjobs
            $t = $this->createTable('hermes_clientjobs', array('primaryKey' => false));
            $t->column('clientjob_id', 'string', array('limit' => 255, 'null' => false));
            $t->column('clientjob_enterdescription', 'integer', array('null' => false, 'default' => 1));
            $t->column('clientjob_exportid', 'string', array('limit' => 255));
            $t->primaryKey(array('clientjob_id'));
            $t->end();
        }

        if (!in_array('hermes_deliverables', $tableList)) {
            // Create: hermes_deliverables
            $t = $this->createTable('hermes_deliverables', array('primaryKey' => false));
            $t->column('deliverable_id', 'integer', array('null' => false));
            $t->column('client_id', 'string', array('limit' => 255, 'null' => false));
            $t->column('deliverable_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('deliverable_parent', 'integer');
            $t->column('deliverable_estimate', 'decimal', array('precision' => 10, 'scale' => 2));
            $t->column('deliverable_active', 'integer', array('default' => 1, 'null' => false));
            $t->column('deliverable_description', 'text');
            $t->primaryKey(array('deliverable_id'));
            $t->end();

            $this->addIndex('hermes_deliverables', array('client_id'));
            $this->addIndex('hermes_deliverables', array('deliverable_active'));
        }
    }

    /**
     * Downgrade
     *
     */
    public function down()
    {
        $tableList = $this->tables();

        $this->dropTable('hermes_timeslices');
        $this->dropTable('hermes_jobtypes');
        $this->dropTable('hermes_clientjobs');
        $this->dropTable('hermes_deliverables');
    }

}
