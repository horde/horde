<?php
/**
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */

require_once __DIR__ . '/../lib/Turba.php';

/**
 * Add hierarchcal related columns to the legacy sql share driver
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */
class TurbaUpgradeActiveSyncSchema extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $t = $this->_connection->table('turba_objects');
        $cols = array_keys($t->getColumns());

        if (!in_array('object_homephone2', $cols)) {
            $this->addColumn('turba_objects', 'object_homephone2', 'string', array('limit' => 25));
        }
        if (!in_array('object_carphone', $cols)) {
            $this->addColumn('turba_objects', 'object_carphone', 'string', array('limit' => 25));
        }
        if (!in_array('object_workphone2', $cols)) {
            $this->addColumn('turba_objects', 'object_workphone2', 'string', array('limit' => 25));
        }
        if (!in_array('object_radiophone', $cols)) {
            $this->addColumn('turba_objects', 'object_radiophone', 'string', array('limit' => 25));
        }
        if (!in_array('object_companyphone', $cols)) {
            $this->addColumn('turba_objects', 'object_companyphone', 'string', array('limit' => 25));
        }
        if (!in_array('object_otherstreet', $cols)) {
            $this->addColumn('turba_objects', 'object_otherstreet', 'string', array('limit' => 255));
        }
        if (!in_array('object_otherpob', $cols)) {
            $this->addColumn('turba_objects', 'object_otherpob', 'string', array('limit' => 10));
        }
        if (!in_array('object_othercity', $cols)) {
            $this->addColumn('turba_objects', 'object_othercity', 'string', array('limit' => 255));
        }
        if (!in_array('object_otherprovince', $cols)) {
            $this->addColumn('turba_objects', 'object_otherprovince', 'string', array('limit' => 255));
        }
        if (!in_array('object_otherpostalcode', $cols)) {
            $this->addColumn('turba_objects', 'object_otherpostalcode', 'string', array('limit' => 10));
        }
        if (!in_array('object_othercountry', $cols)) {
            $this->addColumn('turba_objects', 'object_othercountry', 'string', array('limit' => 255));
        }
        if (!in_array('object_yomifirstname', $cols)) {
            $this->addColumn('turba_objects', 'object_yomifirstname', 'string', array('limit' => 255));
        }
        if (!in_array('object_yomilastname', $cols)) {
            $this->addColumn('turba_objects', 'object_yomilastname', 'string', array('limit' => 255));
        }
        if (!in_array('object_manager', $cols)) {
            $this->addColumn('turba_objects', 'object_manager', 'string', array('limit' => 255));
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->removeColumn('turba_objects', 'object_homephone2');
        $this->removeColumn('turba_objects', 'object_carphone');
        $this->removeColumn('turba_objects', 'object_workphone2');
        $this->removeColumn('turba_objects', 'object_radiophone');
        $this->removeColumn('turba_objects', 'object_companyphone');
        $this->removeColumn('turba_objects', 'object_otherstreet');
        $this->removeColumn('turba_objects', 'object_otherpob');
        $this->removeColumn('turba_objects', 'object_othercity');
        $this->removeColumn('turba_objects', 'object_otherprovince');
        $this->removeColumn('turba_objects', 'object_otherpostalcode');
        $this->removeColumn('turba_objects', 'object_othercountry');
        $this->removeColumn('turba_objects', 'object_yomifirstname');
        $this->removeColumn('turba_objects', 'object_yomilastname');
        $this->removeColumn('turba_objects', 'object_manager');
    }

}
