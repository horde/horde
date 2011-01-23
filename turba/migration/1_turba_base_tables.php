<?php
/**
 * Create turba base tables
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Turba
 */
class TurbaBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('turba_objects', $tableList)) {
            $t = $this->createTable('turba_objects', array('primaryKey' => false));
            $t->column('object_id', 'string', array('limit' => 32, 'null' => false));
            $t->column('owner_id', 'string', array('limit' => 255, 'null' => false));
            $t->column('object_type', 'string', array('limit' => 255, 'default' => 'Object', 'null' => false));
            $t->column('object_uid', 'string', array('limit' => 255));
            $t->column('object_members', 'text');
            $t->column('object_firstname', 'string', array('limit' => 255));
            $t->column('object_lastname', 'string', array('limit' => 255));
            $t->column('object_middlenames', 'string', array('limit' => 255));
            $t->column('object_nameprefix', 'string', array('limit' => 32));
            $t->column('object_namesuffix', 'string', array('limit' => 32));
            $t->column('object_alias', 'string', array('limit' => 32));
            $t->column('object_photo', 'binary');
            $t->column('object_phototype', 'string', array('limit' => 10));
            $t->column('object_bday', 'string', array('limit' => 10));
            $t->column('object_homestreet', 'string', array('limit' => 255));
            $t->column('object_homepob', 'string', array('limit' => 10));
            $t->column('object_homecity', 'string', array('limit' => 255));
            $t->column('object_homeprovince', 'string', array('limit' => 255));
            $t->column('object_homepostalcode', 'string', array('limit' => 10));
            $t->column('object_homecountry', 'string', array('limit' => 255));
            $t->column('object_workstreet', 'string', array('limit' => 255));
            $t->column('object_workpob', 'string', array('limit' => 10));
            $t->column('object_workcity', 'string', array('limit' => 255));
            $t->column('object_workprovince', 'string', array('limit' => 255));
            $t->column('object_workpostalcode', 'string', array('limit' => 10));
            $t->column('object_workcountry', 'string', array('limit' => 255));
            $t->column('object_tz', 'string', array('limit' => 32));
            $t->column('object_geo', 'string', array('limit' => 255));
            $t->column('object_email', 'string', array('limit' => 255));
            $t->column('object_homephone', 'string', array('limit' => 25));
            $t->column('object_workphone', 'string', array('limit' => 25));
            $t->column('object_cellphone', 'string', array('limit' => 25));
            $t->column('object_fax', 'string', array('limit' => 25));
            $t->column('object_pager', 'string', array('limit' => 25));
            $t->column('object_title', 'string', array('limit' => 255));
            $t->column('object_role', 'string', array('limit' => 255));
            $t->column('object_logo', 'binary');
            $t->column('object_logotype', 'string', array('limit' => 10));
            $t->column('object_company', 'string', array('limit' => 255));
            $t->column('object_category', 'string', array('limit' => 80));
            $t->column('object_notes', 'text');
            $t->column('object_url', 'string', array('limit' => 255));
            $t->column('object_freebusyurl', 'string', array('limit' => 255));
            $t->column('object_pgppublickey', 'text');
            $t->column('object_smimepublickey', 'text');
            $t->primaryKey(array('object_id'));
            $t->end();


            $this->addIndex('turba_objects', array('owner_id'));
            $this->addIndex('turba_objects', array('object_email'));
            $this->addIndex('turba_objects', array('object_firstname'));
            $this->addIndex('turba_objects', array('object_lastname'));
        }

        if (!in_array('turba_shares', $tableList)) {
            $t = $this->createTable('turba_shares', array('primaryKey' => false));
            $t->column('share_id', 'integer', array('null' => false));
            $t->column('share_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('share_owner', 'string', array('limit' => 255, 'null' => false));
            $t->column('share_flags', 'integer', array('default' => 0, 'null' => false));
            $t->column('perm_creator', 'integer', array('default' => 0, 'null' => false));
            $t->column('perm_default', 'integer', array('default' => 0, 'null' => false));
            $t->column('perm_guest', 'integer', array('default' => 0, 'null' => false));
            $t->column('attribute_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('attribute_desc', 'string', array('limit' => 255));
            $t->column('attribute_params', 'text');
            $t->primaryKey(array('share_id'));
            $t->end();

            $this->addIndex('turba_shares', array('share_name'));
            $this->addIndex('turba_shares', array('share_owner'));
            $this->addIndex('turba_shares', array('perm_creator'));
            $this->addIndex('turba_shares', array('perm_default'));
            $this->addIndex('turba_shares', array('perm_guest'));
        }

        if (!in_array('turba_shares_groups', $tableList)) {
            $t = $this->createTable('turba_shares_groups');
            $t->column('share_id', 'integer', array('null' => false));
            $t->column('group_uid', 'string', array('limit' => 255, 'null' => false));
            $t->column('perm', 'integer', array('null' => false));
            $t->end();

            $this->addIndex('turba_shares_groups', array('share_id'));
            $this->addIndex('turba_shares_groups', array('group_uid'));
            $this->addIndex('turba_shares_groups', array('perm'));
        }

        if (!in_array('turba_shares_users', $tableList)) {
            $t = $this->createTable('turba_shares_users');

            $t->column('share_id', 'integer', array('null' => false));
            $t->column('user_uid', 'string', array('limit' => 255, 'null' => false));
            $t->column('perm', 'integer', array('null' => false));
            $t->end();

            $this->addIndex('turba_shares_users', array('share_id'));
            $this->addIndex('turba_shares_users', array('user_uid'));
            $this->addIndex('turba_shares_users', array('perm'));
        }
    }

    /**
     * Downgrade to 0
     */
    public function down()
    {
        $this->dropTable('turba_objects');
        $this->dropTable('turba_shares');
        $this->dropTable('turba_shares_users');
        $this->dropTable('turba_shares_groups');
    }

}
