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
class TurbaUpgradeSchema extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $t = $this->_connection->table('turba_objects');
        $cols = array_keys($t->getColumns());

        if (!in_array('object_assistant', $cols)) {
            $this->addColumn('turba_objects', 'object_assistant', 'string', array('limit' => 255));
        }
        if (!in_array('object_workemail', $cols)) {
            $this->addColumn('turba_objects', 'object_workemail', 'string', array('limit' => 255));
        }
        if (!in_array('object_homeemail', $cols)) {
            $this->addColumn('turba_objects', 'object_homeemail', 'string', array('limit' => 255));
        }

    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->removeColumn('turba_objects', 'object_assistant');
        $this->removeColumn('turba_objects', 'object_workemail');
        $this->removeColumn('turba_objects', 'object_workhomeemail');
    }

}
