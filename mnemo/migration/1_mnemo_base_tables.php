<?php
/**
 * Create Mnemo base tables.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Mnemo
 */
class MnemoBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('mnemo_memos', $tableList)) {
            $t = $this->createTable('mnemo_memos', array('primaryKey' => false));
            $t->column('memo_owner', 'string', array('limit' => 255, 'null' => false));
            $t->column('memo_id', 'string', array('limit' => 32, 'null' => false));
            $t->column('memo_uid', 'string', array('limit' => 255, 'null' => false));
            $t->column('memo_desc', 'string', array('limit' => 64, 'null' => false));
            $t->column('memo_body', 'text');
            $t->column('memo_category', 'string', array('limit' => 80));
            $t->column('memo_private', 'integer', array('default' => 0, 'null' => false));
            $t->primaryKey(array('memo_owner', 'memo_id'));
            $t->end();

            $this->addIndex('mnemo_memos', 'memo_owner');
            $this->addIndex('mnemo_memos', 'memo_uid');
        }

        if (!in_array('mnemo_shares', $tableList)) {
            $t = $this->createTable('mnemo_shares', array('primaryKey' => false));
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
            $this->addIndex('mnemo_shares', 'share_name');
            $this->addIndex('mnemo_shares', 'share_owner');
            $this->addIndex('mnemo_shares', 'perm_creator');
            $this->addIndex('mnemo_shares', 'perm_default');
            $this->addIndex('mnemo_shares', 'perm_guest');
        }

        if (!in_array('mnemo_shares_groups', $tableList)) {
            $t = $this->createTable('mnemo_shares_groups');
            $t->column('share_id', 'integer', array('null' => false));
            $t->column('group_uid', 'string', array('limit' => 255, 'null' => false));
            $t->column('perm', 'integer', array('null' => false));
            $t->end();

            $this->addIndex('mnemo_shares_groups', 'share_id');
            $this->addIndex('mnemo_shares_groups', 'group_uid');
            $this->addIndex('mnemo_shares_groups', 'perm');
        }

        if (!in_array('mnemo_shares_users', $tableList)) {
            $t = $this->createTable('mnemo_shares_users');
            $t->column('share_id', 'integer', array('null' => false));
            $t->column('user_uid', 'string', array('limit' => 255));
            $t->column('perm', 'integer', array('null' => false));
            $t->end();

            $this->addIndex('mnemo_shares_users', 'share_id');
            $this->addIndex('mnemo_shares_users', 'user_uid');
            $this->addIndex('mnemo_shares_users', 'perm');
        }
    }

    /**
     * Downgrade
     *
     */
    public function down()
    {
        $this->dropTable('mnemo_memos');
        $this->dropTable('mnemo_shares');
        $this->dropTable('mnemo_shares_users');
        $this->dropTable('mnemo_shares_groups');
    }

}
