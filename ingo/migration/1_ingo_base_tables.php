<?php
/**
 * Create Ingo base tables.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Ingo
 */
class IngoBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('ingo_rules', $tableList)) {
            $t = $this->createTable('ingo_rules', array('primaryKey' => false));
            $t->column('rule_id', 'integer', array('null' => false));
            $t->column('rule_owner', 'string', array('limit' => 255, 'null' => false));
            $t->column('rule_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('rule_action', 'integer', array('null' => false));
            $t->column('rule_value', 'string', array('limit' => 255));
            $t->column('rule_flags', 'integer');
            $t->column('rule_conditions', 'text');
            $t->column('rule_combine', 'integer');
            $t->column('rule_stop', 'integer');
            $t->column('rule_active', 'integer', array('default' => 1, 'null' => false));
            $t->column('rule_order', 'integer', array('default' => 0, 'null' => false));
            $t->primaryKey(array('rule_id'));
            $t->end();
            $this->addIndex('ingo_rules', array('rule_owner'));
        }

        if (!in_array('ingo_lists', $tableList)) {
            $t = $this->createTable('ingo_lists', array('primaryKey' => false));
            $t->column('list_owner', 'string', array('limit' => 255, 'null' => false));
            $t->column('list_blacklist', 'integer', array('default' => 0));
            $t->column('list_address', 'string', array('limit' => 255, 'null' => false));
            $t->end();
            $this->addIndex('ingo_lists', array('list_owner', 'list_blacklist'));
        }

        if (!in_array('ingo_forwards', $tableList)) {
            $t = $this->createTable('ingo_forwards', array('primaryKey' => false));
            $t->column('forward_owner', 'string', array('limit' => 255, 'null' => false));
            $t->column('forward_addresses', 'text');
            $t->column('forward_keep', 'integer', array('default' => 0, 'null' => false));
            $t->end();
        }

        if (!in_array('ingo_vacations', $tableList)) {
            $t = $this->createTable('ingo_vacations', array('primaryKey' => false));
            $t->column('vacation_owner', 'string', array('limit' => 255, 'null' => false));
            $t->column('vacation_addresses', 'text');
            $t->column('vacation_subject', 'string', array('limit' => 255));
            $t->column('vacation_reason', 'text');
            $t->column('vacation_days', 'integer', array('default' => 7));
            $t->column('vacation_start', 'integer');
            $t->column('vacation_end', 'integer');
            $t->column('vacation_excludes', 'text');
            $t->column('vacation_ignorelists', 'integer', array('default' => 1));
            $t->primaryKey(array('vacation_owner'));
            $t->end();
        }

        if (!in_array('ingo_spam', $tableList)) {
            $t = $this->createTable('ingo_spam', array('primaryKey' => false));
            $t->column('spam_owner', 'string', array('limit' => 255, 'null' => false));
            $t->column('spam_level', 'integer', array('default' => 5));
            $t->column('spam_folder', 'string', array('limit' => 255));
            $t->primaryKey(array('spam_owner'));
            $t->end();
        }
        if (!in_array('ingo_shares', $tableList)) {
            $t = $this->createTable('ingo_shares', array('primaryKey' => false));
            $t->column('share_id', 'integer', array('null' => false));
            $t->column('share_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('share_owner', 'string', array('limit' => 255, 'null' => false));
            $t->column('share_flags', 'integer', array('default' => 0, 'null' => false));
            $t->column('perm_creator', 'integer', array('default' => 0, 'null' => false));
            $t->column('perm_default', 'integer', array('default' => 0, 'null' => false));
            $t->column('perm_guest', 'integer', array('default' => 0, 'null' => false));
            $t->column('attribute_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('attribute_desc', 'string', array('limit' => 255));
            $t->primaryKey(array('share_id'));
            $t->end();

            $this->addIndex('ingo_shares', array('share_name'));
            $this->addIndex('ingo_shares', array('share_owner'));
            $this->addIndex('ingo_shares', array('perm_creator'));
            $this->addIndex('ingo_shares', array('perm_default'));
            $this->addIndex('ingo_shares', array('perm_guest'));
        }

        if (!in_array('ingo_shares_groups', $tableList)) {
            $t = $this->createTable('ingo_shares_groups');
            $t->column('share_id', 'integer', array('null' => false));
            $t->column('group_uid', 'string', array('limit' => 255, 'null' => false));
            $t->column('perm', 'integer', array('null' => false));
            $t->end();

            $this->addIndex('ingo_shares_groups', array('share_id'));
            $this->addIndex('ingo_shares_groups', array('group_uid'));
            $this->addIndex('ingo_shares_groups', 'perm');
        }

        if (!in_array('ingo_shares_users', $tableList)) {
            $t = $this->createTable('ingo_shares_users');
            $t->column('share_id', 'integer', array('null' => false));
            $t->column('user_uid', 'string', array('limit' => 255, 'null' => false));
            $t->column('perm', 'integer', array('null' => false));
            $t->end();

            $this->addIndex('ingo_shares_users', array('share_id'));
            $this->addIndex('ingo_shares_users', array('user_uid'));
            $this->addIndex('ingo_shares_users', array('perm'));
        }

    }

    /**
     * Downgrade
     *
     */
    public function down()
    {
        $this->dropTable('ingo_rules');
        $this->dropTable('ingo_lists');
        $this->dropTable('ingo_forwards');
        $this->dropTable('ingo_vacations');
        $this->dropTable('ingo_spam');
        $this->dropTable('ingo_shares');
        $this->dropTable('ingo_shares_groups');
        $this->dropTable('ingo_shares_users');
    }

}
