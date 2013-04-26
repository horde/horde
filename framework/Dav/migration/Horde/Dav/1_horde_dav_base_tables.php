<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */

/**
 * Create Horde_Dav base tables.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */
class HordeDavBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade
     */
    public function up()
    {
        $t = $this->createTable('horde_dav_ids', array('autoincrementKey' => false));
        $t->column('id_collection', 'string', array('null' => false));
        $t->column('id_internal', 'string', array('limit' => 255, 'null' => false));
        $t->column('id_external', 'string', array('limit' => 255, 'null' => false));
        $t->end();

        $this->addIndex('horde_dav_ids', 'id_collection');
        $this->addIndex('horde_dav_ids', 'id_internal', array('unique' => true));
        $this->addIndex('horde_dav_ids', 'id_external', array('unique' => true));
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->dropTable('horde_dav_ids');
    }
}
