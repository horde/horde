<?php
/**
 * @author   Ian Roth <iron_hat@hotmail.com>
 * @category Horde
 * @license 
 * @package  Jonah
 */

require_once dirname(__FILE__) . '/../lib/Jonah.php';

/**
 * Adds tables for the Sqlng share driver.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * @author   Ian Roth <iron_hat@hotmail.com>
 * @category Horde
 * @license
 * @package  Jonah
 */
class JonahUpgradeSqlng extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $t = $this->createTable('jonah_sharesng', array('primaryKey' => 'share_id'));
        $t->column('share_name', 'string', array('limit' => 255, 'null' => false));
        $t->column('share_owner', 'string', array('limit' => 255));
        $t->column('share_flags', 'integer', array('default' => 0, 'null' => false));
        $t->column('share_parents', 'text');
        $t->column('perm_creator_' . Horde_Perms::SHOW, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_creator_' . Horde_Perms::READ, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_creator_' . Horde_Perms::EDIT, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_creator_' . Horde_Perms::DELETE, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_creator_' . Jonah::PERMS_DELEGATE, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_default_' . Horde_Perms::SHOW, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_default_' . Horde_Perms::READ, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_default_' . Horde_Perms::EDIT, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_default_' . Horde_Perms::DELETE, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_default_' . Jonah::PERMS_DELEGATE, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_guest_' . Horde_Perms::SHOW, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_guest_' . Horde_Perms::READ, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_guest_' . Horde_Perms::EDIT, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_guest_' . Horde_Perms::DELETE, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_guest_' . Jonah::PERMS_DELEGATE, 'boolean', array('default' => false, 'null' => false));
        $t->column('attribute_name', 'string', array('limit' => 255, 'null' => false));
        $t->column('attribute_desc', 'string', array('limit' => 255));
        $t->column('attribute_slug', 'string', array('limit' => 64));
        $t->column('attribute_full_feed', 'integer', array('default' => 0, 'null' =>false));
        $t->column('attribute_interval', 'integer');
        $t->column('attribute_url', 'string', array('limit' => 255));
        $t->column('attribute_link', 'string', array('limit' => 255));
        $t->column('attribute_page_link', 'string', array('limit' => 255));
        $t->column('attribute_story_url', 'string', array('limit' => 255));
        $t->column('attribute_img', 'string', array('limit' => 255));
        $t->column('attribute_updated', 'integer');
        $t->end();

        $this->addIndex('jonah_sharesng', array('share_name'));
        $this->addIndex('jonah_sharesng', array('share_owner'));
        $this->addIndex('jonah_sharesng', array('perm_creator_' . Horde_Perms::SHOW));
        $this->addIndex('jonah_sharesng', array('perm_creator_' . Horde_Perms::READ));
        $this->addIndex('jonah_sharesng', array('perm_creator_' . Horde_Perms::EDIT));
        $this->addIndex('jonah_sharesng', array('perm_creator_' . Horde_Perms::DELETE));
        $this->addIndex('jonah_sharesng', array('perm_creator_' . Jonah::PERMS_DELEGATE));
        $this->addIndex('jonah_sharesng', array('perm_default_' . Horde_Perms::SHOW));
        $this->addIndex('jonah_sharesng', array('perm_default_' . Horde_Perms::READ));
        $this->addIndex('jonah_sharesng', array('perm_default_' . Horde_Perms::EDIT));
        $this->addIndex('jonah_sharesng', array('perm_default_' . Horde_Perms::DELETE));
        $this->addIndex('jonah_sharesng', array('perm_default_' . Jonah::PERMS_DELEGATE));
        $this->addIndex('jonah_sharesng', array('perm_guest_' . Horde_Perms::SHOW));
        $this->addIndex('jonah_sharesng', array('perm_guest_' . Horde_Perms::READ));
        $this->addIndex('jonah_sharesng', array('perm_guest_' . Horde_Perms::EDIT));
        $this->addIndex('jonah_sharesng', array('perm_guest_' . Horde_Perms::DELETE));
        $this->addIndex('jonah_sharesng', array('perm_guest_' . Jonah::PERMS_DELEGATE));

        $t = $this->createTable('jonah_sharesng_groups', array('primaryKey' => false));
        $t->column('share_id', 'integer', array('null' => false));
        $t->column('group_uid', 'string', array('limit' => 255, 'null' => false));
        $t->column('perm_' . Horde_Perms::SHOW, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_' . Horde_Perms::READ, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_' . Horde_Perms::EDIT, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_' . Horde_Perms::DELETE, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_' . Jonah::PERMS_DELEGATE, 'boolean', array('default' => false, 'null' => false));
        $t->end();

        $this->addIndex('jonah_sharesng_groups', array('share_id'));
        $this->addIndex('jonah_sharesng_groups', array('group_uid'));
        $this->addIndex('jonah_sharesng_groups', array('perm_' . Horde_Perms::SHOW));
        $this->addIndex('jonah_sharesng_groups', array('perm_' . Horde_Perms::READ));
        $this->addIndex('jonah_sharesng_groups', array('perm_' . Horde_Perms::EDIT));
        $this->addIndex('jonah_sharesng_groups', array('perm_' . Horde_Perms::DELETE));
        $this->addIndex('jonah_sharesng_groups', array('perm_' . Jonah::PERMS_DELEGATE));

        $t = $this->createTable('jonah_sharesng_users', array('primaryKey' => false));
        $t->column('share_id', 'integer', array('null' => false));
        $t->column('user_uid', 'string', array('limit' => 255, 'null' => false));
        $t->column('perm_' . Horde_Perms::SHOW, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_' . Horde_Perms::READ, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_' . Horde_Perms::EDIT, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_' . Horde_Perms::DELETE, 'boolean', array('default' => false, 'null' => false));
        $t->column('perm_' . Jonah::PERMS_DELEGATE, 'boolean', array('default' => false, 'null' => false));
        $t->end();

        $this->addIndex('jonah_sharesng_users', array('share_id'));
        $this->addIndex('jonah_sharesng_users', array('user_uid'));
        $this->addIndex('jonah_sharesng_users', array('perm_' . Horde_Perms::SHOW));
        $this->addIndex('jonah_sharesng_users', array('perm_' . Horde_Perms::READ));
        $this->addIndex('jonah_sharesng_users', array('perm_' . Horde_Perms::EDIT));
        $this->addIndex('jonah_sharesng_users', array('perm_' . Horde_Perms::DELETE));
        $this->addIndex('jonah_sharesng_users', array('perm_' . Jonah::PERMS_DELEGATE));

        $this->dataUp();
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->dropTable('jonah_sharesng');
        $this->dropTable('jonah_sharesng_groups');
        $this->dropTable('jonah_sharesng_users');
    }

    public function dataUp()
    {
        $whos = array('creator', 'default', 'guest');
        $perms = array(Horde_Perms::SHOW,
                       Horde_Perms::READ,
                       Horde_Perms::EDIT,
                       Horde_Perms::DELETE,
                       Jonah::PERMS_DELEGATE);

        $sql = 'INSERT INTO jonah_sharesng (share_id, share_name, share_owner, share_flags, attribute_name, attribute_desc, attribute_color';
        $count = 0;
        foreach ($whos as $who) {
            foreach ($perms as $perm) {
                $sql .= ', perm_' . $who . '_' . $perm;
                $count++;
            }
        }
        $sql .= ') VALUES (?, ?, ?, ?, ?, ?, ?' . str_repeat(', ?', $count) . ')';

        foreach ($this->select('SELECT * FROM jonah_shares') as $share) {
            $values = array($share['share_id'],
                            $share['share_name'],
                            $share['share_owner'],
                            $share['share_flags'],
                            $share['attribute_name'],
                            $share['attribute_desc'],
                            $share['attribute_color']);
            foreach ($whos as $who) {
                foreach ($perms as $perm) {
                    $values[] = (bool)($share['perm_' . $who] & $perm);
                }
            }
            $this->insert($sql, $values);
        }

        foreach (array('user', 'group') as $what) {
            $sql = 'INSERT INTO jonah_sharesng_' . $what . 's (share_id, ' . $what . '_uid';
            $count = 0;
            foreach ($perms as $perm) {
                $sql .= ', perm_' . $perm;
                $count++;
            }
            $sql .= ') VALUES (?, ?' . str_repeat(', ?', $count) . ')';

            foreach ($this->select('SELECT * FROM jonah_shares_' . $what . 's') as $share) {
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
