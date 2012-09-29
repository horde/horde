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
class TurbaUpgradeUpdateSchema extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $t = $this->_connection->table('turba_objects');
        $cols = array_keys($t->getColumns());

        if (!in_array('object_anniversary', $cols)) {
            $this->addColumn('turba_objects', 'object_anniversary', 'string', array('limit' => 10));
        }
        if (!in_array('object_department', $cols)) {
            $this->addColumn('turba_objects', 'object_department', 'string', array('limit' => 255));
        }
        if (!in_array('object_spouse', $cols)) {
            $this->addColumn('turba_objects', 'object_spouse', 'string', array('limit' => 255));
        }
        if (!in_array('object_homefax', $cols)) {
            $this->addColumn('turba_objects', 'object_homefax', 'string', array('limit' => 25));
        }
        if (!in_array('object_nickname', $cols)) {
            $this->addColumn('turba_objects', 'object_nickname', 'string', array('limit' => 255));
        }
        if (!in_array('object_assistantphone', $cols)) {
            $this->addColumn('turba_objects', 'object_assistantphone', 'string', array('limit' => 25));
        }
        if (!in_array('object_imaddress', $cols)) {
            $this->addColumn('turba_objects', 'object_imaddress', 'string', array('limit' => 255));
        }
        if (!in_array('object_imaddress2', $cols)) {
            $this->addColumn('turba_objects', 'object_imaddress2', 'string', array('limit' => 255));
        }
        if (!in_array('object_imaddress3', $cols)) {
            $this->addColumn('turba_objects', 'object_imaddress3', 'string', array('limit' => 255));
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->removeColumn('turba_objects', 'object_anniversary');
        $this->removeColumn('turba_objects', 'object_department');
        $this->removeColumn('turba_objects', 'object_spouse');
        $this->removeColumn('turba_objects', 'object_homefax');
        $this->removeColumn('turba_objects', 'object_nickname');
        $this->removeColumn('turba_objects', 'object_assistantphone');
        $this->removeColumn('turba_objects', 'object_imaddress');
        $this->removeColumn('turba_objects', 'object_imaddress2');
        $this->removeColumn('turba_objects', 'object_imaddress3');
    }

}
