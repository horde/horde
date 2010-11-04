<?php
/**
 * Create whups base tables as of Whups 2.3.5
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Whups
 */
class WhupsBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('whups_tickets', $tableList)) {
            $t = $this->createTable('whups_tickets', array('primaryKey' => false));
            $t->column('ticket_id', 'integer', array('null' => false));
            $t->column('ticket_summary', 'string', array('limit' => 255));
            $t->column('user_id_requester', 'string', array('limit' => 255, 'null' => false));
            $t->column('queue_id', 'integer', array('null' => false));
            $t->column('version_id', 'integer');
            $t->column('type_id', 'integer', array('null' => false));
            $t->column('state_id', 'integer', array('null' => false));
            $t->column('priority_id', 'integer', array('null' => false));
            $t->column('ticket_timestamp', 'integer', array('null' => false));
            $t->column('ticket_due', 'integer');
            $t->column('date_updated', 'integer');
            $t->column('date_assigned', 'integer');
            $t->column('date_resolved', 'integer');
            $t->primaryKey(array('ticket_id'));
            $t->end();

            $this->addIndex('whups_tickets', array('queue_id'));
            $this->addIndex('whups_tickets', array('state_id'));
            $this->addIndex('whups_tickets', array('user_id_requester'));
            $this->addIndex('whups_tickets', array('version_id'));
            $this->addIndex('whups_tickets', array('priority_id'));
        }

        if (!in_array('whups_ticket_owners', $tableList)) {
            $t = $this->createTable('whups_ticket_owners', array('primaryKey' => false));
            $t->column('ticket_id', 'integer', array('null' => false));
            $t->column('ticket_owner', 'string', array('null' => false, 'limit' => 255));
            $t->primaryKey(array('ticket_id', 'ticket_owner'));
            $t->end();

            $this->addIndex('whups_ticket_owners', 'ticket_id');
            $this->addIndex('whups_ticket_owners', 'ticket_owner');
        }

        if (!in_array('whups_guests', $tableList)) {
            $t = $this->createTable('whups_guests', array('primaryKey' => false));
            $t->column('guest_id', 'string', array('limit' => 255, 'null' => false));
            $t->column('guest_email', 'string', array('limit' => 255, 'null' => false));
            $t->primaryKey(array('guest_id'));
            $t->end();
        }

        if (!in_array('whups_queues', $tableList)) {
            $t = $this->createTable('whups_queues', array('primaryKey' => false));
            $t->column('queue_id', 'integer', array('null' => false));
            $t->column('queue_name', 'string', array('limit' => 64, 'null' => false));
            $t->column('queue_description', 'string', array('limit' => 255));
            $t->column('queue_versioned', 'smallint', array('default' => 0, 'null' => false));
            $t->column('queue_slug', 'text', array('limit' => 64));
            $t->column('queue_email', 'text', array('limit' => 64));
            $t->primaryKey(array('queue_id'));
            $t->end();
        }

        if (!in_array('whups_queues_users', $tableList)) {
            $t = $this->createTable('whups_queues_users', array('primaryKey' => false));
            $t->column('queue_id', 'integer', array('null' => false));
            $t->column('user_uid', 'string', array('limit' => 250, 'null' => false));
            $t->primaryKey(array('queue_id', 'user_uid'));
            $t->end();
        }

        if (!in_array('whups_types', $tableList)) {
            $t = $this->createTable('whups_types', array('primaryKey' => false));
            $t->column('type_id', 'integer', array('null' => false));
            $t->column('type_name', 'string', array('limit' => 64, 'null' => false));
            $t->column('type_description', 'string', array('limit' => 255));
            $t->primaryKey(array('type_id'));
            $t->end();
        }

        if (!in_array('whups_types_queues', $tableList)) {
            $t = $this->createTable('whups_types_queues', array('primaryKey' => false));
            $t->column('type_id', 'integer', array('null' => false));
            $t->column('queue_id', 'integer', array('null' => false));
            $t->column('type_default', 'smallint', array('null' => false, 'default' => 0));
            $t->end();

            $this->addIndex('whups_types_queues', array('queue_id', 'type_id'));
        }

        if (!in_array('whups_states', $tableList)) {
            $t = $this->createTable('whups_states', array('primaryKey' => false));
            $t->column('state_id', 'integer', array('null' => false));
            $t->column('type_id', 'integer', array('null' => false));
            $t->column('state_name', 'string', array('limit' => 64, 'null' => false));
            $t->column('state_description', 'string', array('limit' => 255));
            $t->column('state_category', 'string', array('limit' => 16));
            $t->column('state_default', 'smallint', array('default' => 0, 'null' => false));
            $t->primaryKey(array('state_id'));
            $t->end();

            $this->addIndex('whups_states', array('type_id'));
            $this->addIndex('whups_states', array('state_category'));
        }

        if (!in_array('whups_replies', $tableList)) {
            $t = $this->createTable('whups_replies', array('primaryKey' => false));
            $t->column('type_id', 'integer', array('null' => false));
            $t->column('reply_id', 'integer', array('null' => false));
            $t->column('reply_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('reply_text', 'text', array('null' => false));
            $t->primaryKey(array('reply_id'));
            $t->end();

            $this->addIndex('whups_replies', array('type_id'));
            $this->addIndex('whups_replies', array('reply_name'));
        }

        if (!in_array('whups_attributes_desc', $tableList)) {
            $t = $this->createTable('whups_attributes_desc', array('primaryKey' => false));
            $t->column('attribute_id', 'integer', array('null' => false));
            $t->column('type_id', 'integer', array('null' => false));
            $t->column('attribute_name', 'string', array('null' => false, 'limit' => 64));
            $t->column('attribute_description', 'string', array('null' => false, 'limit' => 255));
            $t->column('attribute_type', 'string', array('default' => 'text', 'null' => false, 'limit' => 255));
            $t->column('attribute_params', 'text');
            $t->column('attribute_required', 'smallint');
            $t->primaryKey(array('attribute_id'));
            $t->end();
        }

        if (!in_array('whups_attributes', $tableList)) {
            $t = $this->createTable('whups_attributes', array('primaryKey' => false));
            $t->column('ticket_id', 'integer', array('null' => false));
            $t->column('attribute_id', 'integer', array('null' => false));
            $t->column('attribute_value', 'string', array('limit' => 255));
            $t->end();
        }

        if (!in_array('whups_comments', $tableList)) {
            $t = $this->createTable('whups_comments', array('primaryKey' => false));
            $t->column('comment_id', 'integer', array('null' => false));
            $t->column('ticket_id', 'integer', array('null' => false));
            $t->column('user_id_creator', 'string', array('limit' => 255, 'null' => false));
            $t->column('comment_text', 'text');
            $t->column('comment_timestamp', 'integer');
            $t->primaryKey(array('comment_id'));
            $t->end();

            $this->addIndex('whups_comments', array('ticket_id'));
        }

        if (!in_array('whups_logs', $tableList)) {
            $t = $this->createTable('whups_logs', array('primaryKey' => false));
            $t->column('log_id', 'integer', array('null' => false));
            $t->column('transaction_id', 'integer', array('null' => false));
            $t->column('ticket_id', 'integer', array('null' => false));
            $t->column('log_timestamp', 'integer', array('null' => false));
            $t->column('log_type', 'string', array('limit' => 255, 'null' => false));
            $t->column('log_value', 'string', array('null' => false));
            $t->column('log_value_num', 'integer');
            $t->column('user_id', 'string', array('limit' => 255, 'null' => false));
            $t->primaryKey(array('log_id'));
            $t->end();

            $this->addIndex('whups_logs', array('transaction_id'));
            $this->addIndex('whups_logs', array('ticket_id'));
            $this->addIndex('whups_logs', array('log_timestamp'));
        }

        if (!in_array('whups_priorities', $tableList)) {
            $t = $this->createTable('whups_priorities', array('primaryKey' => false));
            $t->column('priority_id', 'integer', array('null' => false));
            $t->column('type_id', 'integer', array('null' => false));
            $t->column('priority_name', 'string', array('limit' => 64));
            $t->column('priority_description', 'string', array('limit' => 255));
            $t->column('priority_default', 'smallint', array('defalut' => 0, 'null' => false));
            $t->primaryKey(array('priority_id'));
            $t->end();

            $this->addIndex('whups_priorities', array('type_id'));
        }

        if (!in_array('whups_versions', $tableList)) {
            $t = $this->createTable('whups_versions', array('primaryKey' => false));
            $t->column('version_id', 'integer', array('null' => false));
            $t->column('queue_id', 'integer', array('null' => false));
            $t->column('version_name', 'string', array('limit' => 64));
            $t->column('version_description', 'string', array('limit' => 255));
            $t->column('version_active', 'integer', array('default' => 1));
            $t->primaryKey(array('version_id'));
            $t->end();

            $this->addIndex('whups_versions', array('version_active'));
        }

        if (!in_array('whups_ticket_listeners', $tableList)) {
            $t = $this->createTable('whups_ticket_listeners', array('primaryKey' => false));
            $t->column('ticket_id', 'integer', array('null' => false));
            $t->column('user_uid', 'string', array('limit' => 255, 'null' => false));
            $t->end();

            $this->addIndex('whups_ticket_listeners', array('ticket_id'));
        }

        if (!in_array('whups_queries', $tableList)) {
            $t = $this->createTable('whups_queries', array('primaryKey' => false));
            $t->column('query_id', 'integer', array('null' => false));
            $t->column('query_parameters', 'text');
            $t->column('query_object', 'text');
            $t->primaryKey(array('query_id'));
            $t->end();
        }

        if (!in_array('whups_shares', $tableList)) {
            $t = $this->createTable('whups_shares', array('primaryKey' => false));
            $t->column('share_id', 'integer', array('null' => false));
            $t->column('share_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('share_owner', 'string', array('limit' => 255, 'null' => false));
            $t->column('share_flags', 'smallint', array('default' => 0, 'null' => false));
            $t->column('perm_creator', 'smallint', array('default' => 0, 'null' => false));
            $t->column('perm_default', 'smallint', array('default' => 0, 'null' => false));
            $t->column('perm_guest', 'smallint', array('default' => 0, 'null' => false));
            $t->column('attribute_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('attribute_slug', 'string', array('limit' => 255));
            $t->primaryKey(array('share_id'));
            $t->end();

            $this->addIndex('whups_shares', array('share_name'));
            $this->addIndex('whups_shares', array('share_owner'));
            $this->addIndex('whups_shares', array('perm_creator'));
            $this->addIndex('whups_shares', array('perm_default'));
            $this->addIndex('whups_shares', array('perm_guest'));
        }

        if (!in_array('whups_shares_groups', $tableList)) {
            $t = $this->createTable('whups_shares_groups');
            $t->column('share_id', 'integer', array('null' => false));
            $t->column('group_uid', 'string', array('limit' => 255, 'null' => false));
            $t->column('perm', 'smallint', array('null' => false));
            $t->end();

            $this->addIndex('whups_shares_groups', array('share_id'));
            $this->addIndex('whups_shares_groups', array('group_uid'));
            $this->addIndex('whups_shares_groups', array('perm'));
        }

        if (!in_array('whups_shares_users', $tableList)) {
            $t = $this->createTable('whups_shares_users');

            $t->column('share_id', 'integer', array('null' => false));
            $t->column('user_uid', 'string', array('limit' => 255));
            $t->column('perm', 'smallint', array('null' => false));
            $t->end();

            $this->addIndex('whups_shares_users', array('share_id'));
            $this->addIndex('whups_shares_users', array('user_uid'));
            $this->addIndex('whups_shares_users', array('perm'));
        }
    }

    /**
     * Downgrade to 0
     */
    public function down()
    {
        $this->dropTable('whups_tickets');
        $this->dropTable('whups_ticket_owners');
        $this->dropTable('whups_guests');
        $this->dropTable('whups_queues');
        $this->dropTable('whups_queues_users');
        $this->dropTable('whups_types');
        $this->dropTable('whups_types_queues');
        $this->dropTable('whups_states');
        $this->dropTable('whups_replies');
        $this->dropTable('whups_attributes_desc');
        $this->dropTable('whups_attributes');
        $this->dropTable('whups_comments');
        $this->dropTable('whups_logs');
        $this->dropTable('whups_priorities');
        $this->dropTable('whups_versions');
        $this->dropTable('whups_ticket_listeners');
        $this->dropTable('whups_queries');
        $this->dropTable('whups_shares');
        $this->dropTable('whups_shares_groups');
        $this->dropTable('whups_shares_users');
    }

}