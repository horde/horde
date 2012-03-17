<?php
/**
 * Create Skeleton base tables.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Your Name <you@example.com>
 * @category Horde
 * @package  Skeleton
 */
class SkeletonBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade
     */
    public function up()
    {
        $t = $this->createTable('skeleton_items', array('autoincrementKey' => 'item_id'));
        $t->column('item_owner', 'string', array('limit' => 255, 'null' => false));
        $t->column('item_data', 'string', array('limit' => 64, 'null' => false));
        $t->end();

        $this->addIndex('skeleton_items', array('item_owner'));
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->dropTable('skeleton_items');
    }
}
