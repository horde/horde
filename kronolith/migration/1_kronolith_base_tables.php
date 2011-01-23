<?php
/**
 * Create kronolith base tables as of Kronolith 2.3.5
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Kronolith
 */
class KronolithBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('kronolith_events', $tableList)) {
            $t = $this->createTable('kronolith_events', array('primaryKey' => false));
            $t->column('event_id', 'string', array('limit' => 32, 'null' => false));
            $t->column('event_uid', 'string', array('limit' => 255, 'null' => false));
            $t->column('calendar_id', 'string', array('limit' => 255, 'null' => false));
            $t->column('event_creator_id', 'string', array('limit' => 255, 'null' => false));
            $t->column('event_description', 'text');
            $t->column('event_location', 'text');
            $t->column('event_status', 'integer', array('default' => 0));
            $t->column('event_attendees', 'text');
            $t->column('event_keywords', 'text');
            $t->column('event_exceptions', 'text');
            $t->column('event_title', 'string', array('limit' => 255));
            $t->column('event_category', 'string', array('limit' => 80));
            $t->column('event_recurtype', 'integer', array('default' => 0));
            $t->column('event_recurinterval', 'integer');
            $t->column('event_recurdays', 'integer');
            $t->column('event_recurenddate', 'datetime');
            $t->column('event_recurcount', 'integer');
            $t->column('event_start', 'datetime');
            $t->column('event_end', 'datetime');
            $t->column('event_alarm', 'integer', array('default' => 0));
            $t->column('event_modified', 'integer', array('default' => 0));
            $t->column('event_private', 'integer', array('default' => 0, 'null' => false));
            $t->primaryKey(array('event_id'));
            $t->end();

            $this->addIndex('kronolith_events', array('calendar_id'));
            $this->addIndex('kronolith_events', array('event_uid'));
        }

        if (!in_array('kronolith_storage', $tableList)) {
            $t = $this->createTable('kronolith_storage');
            $t->column('vfb_owner', 'string', array('limit' => 255));
            $t->column('vfb_email', 'string', array('limit' => 255, 'null' => false));
            $t->column('vfb_serialized', 'text', array('null' => false));
            $t->end();

            $this->addIndex('kronolith_storage', array('vfb_owner'));
            $this->addIndex('kronolith_storage', array('vfb_email'));
        }

        if (!in_array('kronolith_shares', $tableList)) {
            $t = $this->createTable('kronolith_shares', array('primaryKey' => false));
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

            $this->addIndex('kronolith_shares', array('share_name'));
            $this->addIndex('kronolith_shares', array('share_owner'));
            $this->addIndex('kronolith_shares', array('perm_creator'));
            $this->addIndex('kronolith_shares', array('perm_default'));
            $this->addIndex('kronolith_shares', array('perm_guest'));
        }

        if (!in_array('kronolith_shares_groups', $tableList)) {
            $t = $this->createTable('kronolith_shares_groups');
            $t->column('share_id', 'integer', array('null' => false));
            $t->column('group_uid', 'string', array('limit' => 255, 'null' => false));
            $t->column('perm', 'integer', array('null' => false));
            $t->end();

            $this->addIndex('kronolith_shares_groups', array('share_id'));
            $this->addIndex('kronolith_shares_groups', array('group_uid'));
            $this->addIndex('kronolith_shares_groups', array('perm'));
        }

        if (!in_array('kronolith_shares_users', $tableList)) {
            $t = $this->createTable('kronolith_shares_users');

            $t->column('share_id', 'integer', array('null' => false));
            $t->column('user_uid', 'string', array('limit' => 255, 'null' => false));
            $t->column('perm', 'integer', array('null' => false));
            $t->end();

            $this->addIndex('kronolith_shares_users', array('share_id'));
            $this->addIndex('kronolith_shares_users', array('user_uid'));
            $this->addIndex('kronolith_shares_users', array('perm'));
        }
    }

    /**
     * Downgrade to 0
     */
    public function down()
    {
        $this->dropTable('kronolith_events');
        $this->dropTable('kronolith_shares');
        $this->dropTable('kronolith_storage');
        $this->dropTable('kronolith_shares_groups');
        $this->dropTable('kronolith_shares_users');
    }

}