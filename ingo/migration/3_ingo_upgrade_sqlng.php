<?php
/**
 * Adds tables for the Sqlng share driver.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Ingo
 */
class IngoUpgradeSqlng extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $t = $this->createTable('ingo_sharesng', array('primaryKey' => 'share_id'));
        $t->column('share_name', 'string', array('limit' => 255, 'null' => false));
        $t->column('share_owner', 'string', array('limit' => 255));
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
        $t->column('attribute_name', 'string', array('limit' => 255, 'null' => false));
        $t->column('attribute_desc', 'string', array('limit' => 255));
        $t->end();

        $this->addIndex('ingo_sharesng', array('share_name'));
        $this->addIndex('ingo_sharesng', array('share_owner'));
        $this->addIndex('ingo_sharesng', array('perm_creator_' . Horde_Perms::SHOW));
        $this->addIndex('ingo_sharesng', array('perm_creator_' . Horde_Perms::READ));
        $this->addIndex('ingo_sharesng', array('perm_creator_' . Horde_Perms::EDIT));
        $this->addIndex('ingo_sharesng', array('perm_creator_' . Horde_Perms::DELETE));
        $this->addIndex('ingo_sharesng', array('perm_default_' . Horde_Perms::SHOW));
        $this->addIndex('ingo_sharesng', array('perm_default_' . Horde_Perms::READ));
        $this->addIndex('ingo_sharesng', array('perm_default_' . Horde_Perms::EDIT));
        $this->addIndex('ingo_sharesng', array('perm_default_' . Horde_Perms::DELETE));
        $this->addIndex('ingo_sharesng', array('perm_guest_' . Horde_Perms::SHOW));
        $this->addIndex('ingo_sharesng', array('perm_guest_' . Horde_Perms::READ));
        $this->addIndex('ingo_sharesng', array('perm_guest_' . Horde_Perms::EDIT));
        $this->addIndex('ingo_sharesng', array('perm_guest_' . Horde_Perms::DELETE));

        $t = $this->createTable('ingo_sharesng_groups', array('primaryKey' => false));
        $t->column('share_id', 'integer', array('null' => false));
        $t->column('group_uid', 'string', array('limit' => 255, 'null' => false));
        $t->column('perm_' . Horde_Perms::SHOW, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_' . Horde_Perms::READ, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_' . Horde_Perms::EDIT, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_' . Horde_Perms::DELETE, 'boolean', array('default' => false, 'null' => false));
        $t->end();

        $this->addIndex('ingo_sharesng_groups', array('share_id'));
        $this->addIndex('ingo_sharesng_groups', array('group_uid'));
        $this->addIndex('ingo_sharesng_groups', array('perm_' . Horde_Perms::SHOW));
        $this->addIndex('ingo_sharesng_groups', array('perm_' . Horde_Perms::READ));
        $this->addIndex('ingo_sharesng_groups', array('perm_' . Horde_Perms::EDIT));
        $this->addIndex('ingo_sharesng_groups', array('perm_' . Horde_Perms::DELETE));

        $t = $this->createTable('ingo_sharesng_users', array('primaryKey' => false));
        $t->column('share_id', 'integer', array('null' => false));
        $t->column('user_uid', 'string', array('limit' => 255, 'null' => false));
        $t->column('perm_' . Horde_Perms::SHOW, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_' . Horde_Perms::READ, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_' . Horde_Perms::EDIT, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_' . Horde_Perms::DELETE, 'boolean', array('default' => false, 'null' => false));
        $t->end();

        $this->addIndex('ingo_sharesng_users', array('share_id'));
        $this->addIndex('ingo_sharesng_users', array('user_uid'));
        $this->addIndex('ingo_sharesng_users', array('perm_' . Horde_Perms::SHOW));
        $this->addIndex('ingo_sharesng_users', array('perm_' . Horde_Perms::READ));
        $this->addIndex('ingo_sharesng_users', array('perm_' . Horde_Perms::EDIT));
        $this->addIndex('ingo_sharesng_users', array('perm_' . Horde_Perms::DELETE));

        $this->dataUp();
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->dropTable('ingo_sharesng');
        $this->dropTable('ingo_sharesng_groups');
        $this->dropTable('ingo_sharesng_users');
    }

    public function dataUp()
    {
        $whos = array('creator', 'default', 'guest');
        $perms = array(Horde_Perms::SHOW,
                       Horde_Perms::READ,
                       Horde_Perms::EDIT,
                       Horde_Perms::DELETE);

        $sql = 'INSERT INTO ingo_sharesng (share_id, share_name, share_owner, share_flags, attribute_name, attribute_desc';
        $count = 0;
        foreach ($whos as $who) {
            foreach ($perms as $perm) {
                $sql .= ', perm_' . $who . '_' . $perm;
                $count++;
            }
        }
        $sql .= ') VALUES (?, ?, ?, ?, ?, ?' . str_repeat(', ?', $count) . ')';

        foreach ($this->select('SELECT * FROM ingo_shares') as $share) {
            $values = array($share['share_id'],
                            $share['share_name'],
                            $share['share_owner'],
                            $share['share_flags'],
                            $share['attribute_name'],
                            $share['attribute_desc']);
            foreach ($whos as $who) {
                foreach ($perms as $perm) {
                    $values[] = (bool)($share['perm_' . $who] & $perm);
                }
            }
            $this->insert($sql, $values);
        }

        foreach (array('user', 'group') as $what) {
            $sql = 'INSERT INTO ingo_sharesng_' . $what . 's (share_id, ' . $what . '_uid';
            $count = 0;
            foreach ($perms as $perm) {
                $sql .= ', perm_' . $perm;
                $count++;
            }
            $sql .= ') VALUES (?, ?' . str_repeat(', ?', $count) . ')';

            foreach ($this->select('SELECT * FROM ingo_shares_' . $what . 's') as $share) {
                $values = array($share['share_id'],
                                $share[$what . '_uid']);
                foreach ($perms as $perm) {
                    $values[] = (bool)($share['perm'] & $perm);
                }
                $this->insert($sql, $values);
            }
        }
    }
}
