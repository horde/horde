<?php

function migrate_sqlng($db)
{
    $migration = new Horde_Db_Migration_Base($db);

    /* Cleanup potential left-overs. */
    try {
        $migration->dropTable('test_shares');
        $migration->dropTable('test_shares_groups');
        $migration->dropTable('test_shares_users');
    } catch (Horde_Db_Exception $e) {
    }

    $t = $migration->createTable('test_shares', array('primaryKey' => 'share_id'));
    $t->column('share_name', 'string', array('limit' => 255, 'null' => false));
    $t->column('share_owner', 'string', array('limit' => 255));
    $t->column('share_parents', 'text');
    $t->column('share_flags', 'integer', array('default' => 0, 'null' => false));
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
    $t->column('attribute_name', 'string', array('limit' => 255));
    $t->column('attribute_desc', 'string', array('limit' => 255));
    $t->end();

    $migration->addIndex('test_shares', array('share_name'));
    $migration->addIndex('test_shares', array('share_owner'));
    $migration->addIndex('test_shares', array('perm_creator_' . Horde_Perms::SHOW));
    $migration->addIndex('test_shares', array('perm_creator_' . Horde_Perms::READ));
    $migration->addIndex('test_shares', array('perm_creator_' . Horde_Perms::EDIT));
    $migration->addIndex('test_shares', array('perm_creator_' . Horde_Perms::DELETE));
    $migration->addIndex('test_shares', array('perm_default_' . Horde_Perms::SHOW));
    $migration->addIndex('test_shares', array('perm_default_' . Horde_Perms::READ));
    $migration->addIndex('test_shares', array('perm_default_' . Horde_Perms::EDIT));
    $migration->addIndex('test_shares', array('perm_default_' . Horde_Perms::DELETE));
    $migration->addIndex('test_shares', array('perm_guest_' . Horde_Perms::SHOW));
    $migration->addIndex('test_shares', array('perm_guest_' . Horde_Perms::READ));
    $migration->addIndex('test_shares', array('perm_guest_' . Horde_Perms::EDIT));
    $migration->addIndex('test_shares', array('perm_guest_' . Horde_Perms::DELETE));

    $t = $migration->createTable('test_shares_groups');
    $t->column('share_id', 'integer', array('null' => false));
    $t->column('group_uid', 'string', array('limit' => 255, 'null' => false));
    $t->column('perm_' . Horde_Perms::SHOW, 'boolean', array('default' => false, 'null' => false));
    $t->column('perm_' . Horde_Perms::READ, 'boolean', array('default' => false, 'null' => false));
    $t->column('perm_' . Horde_Perms::EDIT, 'boolean', array('default' => false, 'null' => false));
    $t->column('perm_' . Horde_Perms::DELETE, 'boolean', array('default' => false, 'null' => false));
    $t->end();

    $migration->addIndex('test_shares_groups', array('share_id'));
    $migration->addIndex('test_shares_groups', array('group_uid'));
    $migration->addIndex('test_shares_groups', array('perm_' . Horde_Perms::SHOW));
    $migration->addIndex('test_shares_groups', array('perm_' . Horde_Perms::READ));
    $migration->addIndex('test_shares_groups', array('perm_' . Horde_Perms::EDIT));
    $migration->addIndex('test_shares_groups', array('perm_' . Horde_Perms::DELETE));

    $t = $migration->createTable('test_shares_users');
    $t->column('share_id', 'integer', array('null' => false));
    $t->column('user_uid', 'string', array('limit' => 255));
    $t->column('perm_' . Horde_Perms::SHOW, 'boolean', array('default' => false, 'null' => false));
    $t->column('perm_' . Horde_Perms::READ, 'boolean', array('default' => false, 'null' => false));
    $t->column('perm_' . Horde_Perms::EDIT, 'boolean', array('default' => false, 'null' => false));
    $t->column('perm_' . Horde_Perms::DELETE, 'boolean', array('default' => false, 'null' => false));
    $t->end();

    $migration->addIndex('test_shares_users', array('share_id'));
    $migration->addIndex('test_shares_users', array('user_uid'));
    $migration->addIndex('test_shares_users', array('perm_' . Horde_Perms::SHOW));
    $migration->addIndex('test_shares_users', array('perm_' . Horde_Perms::READ));
    $migration->addIndex('test_shares_users', array('perm_' . Horde_Perms::EDIT));
    $migration->addIndex('test_shares_users', array('perm_' . Horde_Perms::DELETE));

    $migration->migrate('up');
}
