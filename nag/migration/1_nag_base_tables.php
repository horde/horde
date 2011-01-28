<?php
/**
 * Create Nag base tables (as of Nag 2.x).
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Nag
 */
class NagBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('nag_tasks', $tableList)) {
            $t = $this->createTable('nag_tasks', array('primaryKey' => false));
            $t->column('task_id', 'string', array('limit' => 32, 'null' => false));
            $t->column('task_owner', 'string', array('null' => false));
            $t->column('task_creator', 'string', array('null' => false));
            $t->column('task_parent', 'string');
            $t->column('task_assignee', 'string');
            $t->column('task_name', 'string', array('null' => false));
            $t->column('task_uid', 'string', array('null' => false));
            $t->column('task_desc', 'text');
            $t->column('task_start', 'integer');
            $t->column('task_due', 'integer');
            $t->column('task_priority', 'integer', array('default' => 0, 'null' => false));
            $t->column('task_estimate', 'float');
            $t->column('task_category', 'string', array('limit' => 80));
            $t->column('task_completed', 'integer', array('limit' => 1, 'default' => 0, 'null' => false));
            $t->column('task_completed_date', 'integer');
            $t->column('task_alarm', 'integer', array('default' => 0, 'null' => false));
            $t->column('task_alarm_methods', 'text');
            $t->column('task_private', 'integer', array('limit' => 1, 'default' => 0, 'null' => false));
            $t->primaryKey(array('task_id'));
            $t->end();

            $this->addIndex('nag_tasks', array('task_owner'));
            $this->addIndex('nag_tasks', array('task_uid'));
            $this->addIndex('nag_tasks', array('task_start'));
        }

        if (!in_array('nag_shares', $tableList)) {
            $t = $this->createTable('nag_shares', array('primaryKey' => 'share_id'));
            $t->column('share_id', 'integer', array('null' => false));
            $t->column('share_name', 'string', array('null' => false));
            $t->column('share_owner', 'string');
            $t->column('share_flags', 'integer', array('limit' => 2, 'default' => 0, 'null' => false));
            $t->column('perm_creator', 'integer', array('limit' => 2, 'default' => 0, 'null' => false));
            $t->column('perm_default', 'integer', array('limit' => 2, 'default' => 0, 'null' => false));
            $t->column('perm_guest', 'integer', array('limit' => 2, 'default' => 0, 'null' => false));
            $t->column('attribute_name', 'string', array('null' => false));
            $t->column('attribute_desc', 'string');
            $t->column('attribute_color', 'string', array('limit' => 7));
            $t->primaryKey(array('share_id'));
            $t->end();

            $this->addIndex('nag_shares', array('share_name'));
            $this->addIndex('nag_shares', array('share_owner'));
            $this->addIndex('nag_shares', array('perm_creator'));
            $this->addIndex('nag_shares', array('perm_default'));
            $this->addIndex('nag_shares', array('perm_guest'));
        }

        if (!in_array('nag_shares_groups', $tableList)) {
            $t = $this->createTable('nag_shares_groups', array('primaryKey' => false));
            $t->column('share_id', 'integer', array('null' => false));
            $t->column('group_uid', 'string', array('null' => false));
            $t->column('perm', 'integer', array('limit' => 2, 'null' => false));
            $t->end();

            $this->addIndex('nag_shares_groups', array('share_id'));
            $this->addIndex('nag_shares_groups', array('group_uid'));
            $this->addIndex('nag_shares_groups', array('perm'));
        }

        if (!in_array('nag_shares_users', $tableList)) {
            $t = $this->createTable('nag_shares_users', array('primaryKey' => false));
            $t->column('share_id', 'integer', array('null' => false));
            $t->column('user_uid', 'string', array('limit' => 255, 'null' => false));
            $t->column('perm', 'integer', array('limit' => 2, 'null' => false));
            $t->end();

            $this->addIndex('nag_shares_users', array('share_id'));
            $this->addIndex('nag_shares_users', array('user_uid'));
            $this->addIndex('nag_shares_users', array('perm'));
        }
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        $this->dropTable('nag_tasks');
        $this->dropTable('nag_shares');
        $this->dropTable('nag_shares_groups');
        $this->dropTable('nag_shares_users');
    }
}
