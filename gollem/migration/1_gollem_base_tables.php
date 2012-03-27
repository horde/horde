<?php
/**
 * Gollem base tables.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */
class GollemBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $t = $this->createTable('gollem_shares', array('autoincrementKey' => false));
        $t->column('share_id', 'integer', array('null' => false));
        $t->column('share_name', 'string', array('limit' => 255, 'null' => false));
        $t->column('share_owner', 'string', array('limit' => 255, 'null' => false));
        $t->column('share_flags', 'integer', array('default' => 0, 'null' => false));
        $t->column('share_parents', 'text');
        $t->column('perm_creator', 'integer', array('default' => 0, 'null' => false));
        $t->column('perm_default', 'integer', array('default' => 0, 'null' => false));
        $t->column('perm_guest', 'integer', array('default' => 0, 'null' => false));
        $t->column('attribute_name', 'string', array('limit' => 255, 'null' => false));
        $t->primaryKey(array('share_id'));
        $t->end();
        $this->addIndex('gollem_shares', array('share_name'));
        $this->addIndex('gollem_shares', array('share_owner'));
        $this->addIndex('gollem_shares', array('perm_creator'));
        $this->addIndex('gollem_shares', array('perm_default'));
        $this->addIndex('gollem_shares', array('perm_guest'));

        $t = $this->createTable('gollem_shares_groups');
        $t->column('share_id', 'integer', array('null' => false));
        $t->column('group_uid', 'string', array('limit' => 255, 'null' => false));
        $t->column('perm', 'integer', array('null' => false));
        $t->end();

        $this->addIndex('gollem_shares_groups', array('share_id'));
        $this->addIndex('gollem_shares_groups', array('group_uid'));
        $this->addIndex('gollem_shares_groups', array('perm'));

        $t = $this->createTable('gollem_shares_users');
        $t->column('share_id', 'integer', array('null' => false));
        $t->column('user_uid', 'string', array('limit' => 255, 'null' => false));
        $t->column('perm', 'integer', array('null' => false));
        $t->end();

        $this->addIndex('gollem_shares_users', array('share_id'));
        $this->addIndex('gollem_shares_users', array('user_uid'));
        $this->addIndex('gollem_shares_users', array('perm'));

        $t = $this->createTable('gollem_sharesng', array('autoincrementKey' => 'share_id'));
        $t->column('share_name', 'string', array('limit' => 255, 'null' => false));
        $t->column('share_owner', 'string', array('limit' => 255));
        $t->column('share_flags', 'integer', array('default' => 0, 'null' => false));
        $t->column('share_parents', 'text');
        $t->column('perm_creator_' . Horde_Perms::SHOW, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_creator_' . Horde_Perms::READ, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_creator_' . Horde_Perms::EDIT, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_creator_' . Horde_Perms::DELETE, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_default_' . Horde_Perms::SHOW, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_default_' . Horde_Perms::READ, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_default_' . Horde_Perms::EDIT, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_default_' . Horde_Perms::DELETE, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_guest_' . Horde_Perms::SHOW, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_guest_' . Horde_Perms::READ, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_guest_' . Horde_Perms::EDIT, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_guest_' . Horde_Perms::DELETE, 'boolean', array('default' => false, 'null' => false));
        $t->column('attribute_name', 'string', array('limit' => 255, 'null' => false));
        $t->end();

        $this->addIndex('gollem_sharesng', array('share_name'));
        $this->addIndex('gollem_sharesng', array('share_owner'));
        $this->addIndex('gollem_sharesng', array('perm_creator_' . Horde_Perms::SHOW));
        $this->addIndex('gollem_sharesng', array('perm_creator_' . Horde_Perms::READ));
        $this->addIndex('gollem_sharesng', array('perm_creator_' . Horde_Perms::EDIT));
        $this->addIndex('gollem_sharesng', array('perm_creator_' . Horde_Perms::DELETE));
        $this->addIndex('gollem_sharesng', array('perm_default_' . Horde_Perms::SHOW));
        $this->addIndex('gollem_sharesng', array('perm_default_' . Horde_Perms::READ));
        $this->addIndex('gollem_sharesng', array('perm_default_' . Horde_Perms::EDIT));
        $this->addIndex('gollem_sharesng', array('perm_default_' . Horde_Perms::DELETE));
        $this->addIndex('gollem_sharesng', array('perm_guest_' . Horde_Perms::SHOW));
        $this->addIndex('gollem_sharesng', array('perm_guest_' . Horde_Perms::READ));
        $this->addIndex('gollem_sharesng', array('perm_guest_' . Horde_Perms::EDIT));
        $this->addIndex('gollem_sharesng', array('perm_guest_' . Horde_Perms::DELETE));

        $t = $this->createTable('gollem_sharesng_groups', array('autoincrementKey' => false));
        $t->column('share_id', 'integer', array('null' => false));
        $t->column('group_uid', 'string', array('limit' => 255, 'null' => false));
        $t->column('perm_' . Horde_Perms::SHOW, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_' . Horde_Perms::READ, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_' . Horde_Perms::EDIT, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_' . Horde_Perms::DELETE, 'boolean', array('default' => false, 'null' => false));
        $t->end();

        $this->addIndex('gollem_sharesng_groups', array('share_id'));
        $this->addIndex('gollem_sharesng_groups', array('group_uid'));
        $this->addIndex('gollem_sharesng_groups', array('perm_' . Horde_Perms::SHOW));
        $this->addIndex('gollem_sharesng_groups', array('perm_' . Horde_Perms::READ));
        $this->addIndex('gollem_sharesng_groups', array('perm_' . Horde_Perms::EDIT));
        $this->addIndex('gollem_sharesng_groups', array('perm_' . Horde_Perms::DELETE));

        $t = $this->createTable('gollem_sharesng_users', array('autoincrementKey' => false));
        $t->column('share_id', 'integer', array('null' => false));
        $t->column('user_uid', 'string', array('limit' => 255, 'null' => false));
        $t->column('perm_' . Horde_Perms::SHOW, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_' . Horde_Perms::READ, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_' . Horde_Perms::EDIT, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_' . Horde_Perms::DELETE, 'boolean', array('default' => false, 'null' => false));
        $t->end();

        $this->addIndex('gollem_sharesng_users', array('share_id'));
        $this->addIndex('gollem_sharesng_users', array('user_uid'));
        $this->addIndex('gollem_sharesng_users', array('perm_' . Horde_Perms::SHOW));
        $this->addIndex('gollem_sharesng_users', array('perm_' . Horde_Perms::READ));
        $this->addIndex('gollem_sharesng_users', array('perm_' . Horde_Perms::EDIT));
        $this->addIndex('gollem_sharesng_users', array('perm_' . Horde_Perms::DELETE));
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->dropTable('gollem_shares');
        $this->dropTable('gollem_shares_users');
        $this->dropTable('gollem_shares_groups');
        $this->dropTable('gollem_sharesng');
        $this->dropTable('gollem_sharesng_groups');
        $this->dropTable('gollem_sharesng_users');
    }
}
