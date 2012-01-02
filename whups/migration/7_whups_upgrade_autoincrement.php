<?php
/**
 * Change columns to autoincrement.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsdl.php BSD
 * @package  Whups
 */
class WhupsUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('whups_tickets', 'ticket_id', 'autoincrementKey');
        try {
            $this->dropTable('whups_tickets_seq');
        } catch (Horde_Db_Exception $e) {
        }

        $this->changeColumn('whups_queues', 'queue_id', 'autoincrementKey');
        try {
            $this->dropTable('whups_queues_seq');
        } catch (Horde_Db_Exception $e) {
        }

        $this->changeColumn('whups_types', 'type_id', 'autoincrementKey');
        try {
            $this->dropTable('whups_types_seq');
        } catch (Horde_Db_Exception $e) {
        }

        $this->changeColumn('whups_states', 'state_id', 'autoincrementKey');
        try {
            $this->dropTable('whups_states_seq');
        } catch (Horde_Db_Exception $e) {
        }

        $this->changeColumn('whups_replies', 'reply_id', 'autoincrementKey');
        try {
            $this->dropTable('whups_replies_seq');
        } catch (Horde_Db_Exception $e) {
        }

        $this->changeColumn('whups_attributes_desc', 'attribute_id', 'autoincrementKey');
        try {
            $this->dropTable('whups_attributes_desc_seq');
        } catch (Horde_Db_Exception $e) {
        }

        $this->changeColumn('whups_comments', 'comment_id', 'autoincrementKey');
        try {
            $this->dropTable('whups_comments_seq');
        } catch (Horde_Db_Exception $e) {
        }

        $this->changeColumn('whups_logs', 'log_id', 'autoincrementKey');
        try {
            $this->dropTable('whups_logs_seq');
        } catch (Horde_Db_Exception $e) {
        }

        $this->changeColumn('whups_priorities', 'priority_id', 'autoincrementKey');
        try {
            $this->dropTable('whups_priorities_seq');
        } catch (Horde_Db_Exception $e) {
        }

        $this->changeColumn('whups_versions', 'version_id', 'autoincrementKey');
        try {
            $this->dropTable('whups_version_seq');
        } catch (Horde_Db_Exception $e) {
        }

        $this->changeColumn('whups_queries', 'query_id', 'autoincrementKey');
        try {
            $this->dropTable('whups_queries_seq');
        } catch (Horde_Db_Exception $e) {
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('whups_tickets', 'ticket_id', 'integer', array('null' => false));
        $this->changeColumn('whups_queues', 'queue_id', 'integer', array('null' => false));
        $this->changeColumn('whups_types', 'type_id', 'integer', array('null' => false));
        $this->changeColumn('whups_states', 'state_id', 'integer', array('null' => false));
        $this->changeColumn('whups_replies', 'reply_id', 'integer', array('null' => false));
        $this->changeColumn('whups_attributes_desc', 'attribute_id', 'integer', array('null' => false));
        $this->changeColumn('whups_comments', 'comment_id', 'integer', array('null' => false));
        $this->changeColumn('whups_logs', 'log_id', 'integer', array('null' => false));
        $this->changeColumn('whups_priorities', 'priority_id', 'integer', array('null' => false));
        $this->changeColumn('whups_versions', 'version_id', 'integer', array('null' => false));
        $this->changeColumn('whups_queries', 'query_id', 'integer', array('null' => false));
    }
}
