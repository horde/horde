<?php
/**
 * Whups_Driver_sql class - implements a Whups backend for the
 * PEAR::DB abstraction layer.
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Robert E. Coyle <robertecoyle@hotmail.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */
class Whups_Driver_Sql extends Whups_Driver
{

    /**
     * The database connection object.
     *
     * @var Horde_Db_Adapter_Base
     */
    protected $_db;

    /**
     * A mapping of attributes from generic Whups names to DB backend fields.
     *
     * @var array
     */
    protected $_map = array(
        'id' => 'ticket_id',
        'summary' => 'ticket_summary',
        'requester' => 'user_id_requester',
        'queue' => 'queue_id',
        'version' => 'version_id',
        'type' => 'type_id',
        'state' => 'state_id',
        'priority' => 'priority_id',
        'timestamp' => 'ticket_timestamp',
        'due' => 'ticket_due',
        'date_updated' => 'date_updated',
        'date_assigned' => 'date_assigned',
        'date_resolved' => 'date_resolved'
    );

    /**
     * Local cache for guest email addresses.
     *
     * @var array
     */
    private $_guestEmailCache = array();

    /**
     * Local cache of internal queue hashes
     *
     * @var array
     */
    private $_internalQueueCache = array();

    /**
     * Local queues internal cache
     *
     * @var array
     */
     private $_queues = null;

    /**
     * Local slug cache
     *
     * @var array
     */
     private $_slugs = null;

    /**
     * Adds a new queue to the backend.
     *
     * @params string $name         The queue name.
     * @params string $description  The queue description.
     * @params string $slug         The queue slug.
     * @params string $email        The queue email address.
     *
     * @return integer  The new queue_id
     * @throws Whups_Exception
     */
    public function addQueue($name, $description, $slug = '', $email = '')
    {
        // Check for slug uniqueness
        if (!empty($slug)) {
            $query = 'SELECT count(queue_slug) FROM whups_queues '
                . 'WHERE queue_slug = ?';
            $result = $this->_db->selectValue($query, array($slug));
            if ($result > 0) {
                throw new Whups_Exception(
                  _("That queue slug is already taken. Please select another."));
            }
        }
        $query = 'INSERT INTO whups_queues '
            . '(queue_name, queue_description, queue_slug, queue_email) '
            . 'VALUES (?, ?, ?, ?)';
        $values = array(
            Horde_String::convertCharset($name, 'UTF-8',
                                         $this->_params['charset']),
            Horde_String::convertCharset($description, 'UTF-8',
                                         $this->_params['charset']),
            $slug,
            $email);
        try {
            $result = $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return $result;
    }

    /**
     * Add a new type
     *
     * @param string $name         The type name
     * @param string $description  The description
     *
     * @return integer  The new type_id
     * @throws Whups_Exception
     */
    public function addType($name, $description)
    {
        $query = 'INSERT INTO whups_types' .
                 ' (type_name, type_description) VALUES (?, ?)';
        $values = array(Horde_String::convertCharset($name, 'UTF-8',
                                                     $this->_params['charset']),
                        Horde_String::convertCharset($description, 'UTF-8',
                                                     $this->_params['charset']));
        try {
            $result = $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return $result;
    }

    /**
     * Add a new state
     *
     * @param integer $typeId     The typeId of the type this state is
     *                            associated with.
     * @param string $name        The name of the new state.
     * @param string $description The state's description.
     * @param string $category    The state's state category.
     *
     * @return integer  The new state's state_id.
     * @throws Whups_Exception
     */
    public function addState($typeId, $name, $description, $category)
    {
        $query = 'INSERT INTO whups_states (type_id, state_name, '
            . 'state_description, state_category) VALUES (?, ?, ?, ?)';
        $values = array($typeId,
                        Horde_String::convertCharset($name, 'UTF-8',
                                                     $this->_params['charset']),
                        Horde_String::convertCharset($description, 'UTF-8',
                                                     $this->_params['charset']),
                        Horde_String::convertCharset($category, 'UTF-8',
                                                     $this->_params['charset']));
        try {
            $result = $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return $result;
    }

    /**
     * Add a new priority
     *
     * @param integer $typeId      The typeId to associate priority with.
     * @param string $name         The priority name
     * @param string $description  The priorty description.
     *
     * @return integer  The new priority's priority_id
     * @throws Whups_Exception
     */
    public function addPriority($typeId, $name, $description)
    {
        $query = 'INSERT INTO whups_priorities (type_id, '
            . 'priority_name, priority_description) VALUES (?, ?, ?)';
        $values = array($typeId,
                        Horde_String::convertCharset($name, 'UTF-8',
                                                     $this->_params['charset']),
                        Horde_String::convertCharset($description, 'UTF-8',
                                                     $this->_params['charset']));
        try {
            $result = $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return $result;
    }

    /**
     * Adds a new version to the specified queue.
     *
     * @param integer $queueId     The queueId to add the version to.
     * @param string $name         The name of the new version.
     * @param string $description  The descriptive text for the new version.
     * @param boolean $active      Whether the version is still active.
     *
     * @return integer  The new version id
     * @throws Whups_Exception
     */
    public function addVersion($queueId, $name, $description, $active)
    {
        $query = 'INSERT INTO whups_versions (queue_id, '
            . 'version_name, version_description, version_active) VALUES (?, ?, ?, ?)';
        $values = array((int)$queueId,
                        Horde_String::convertCharset($name, 'UTF-8',
                                                     $this->_params['charset']),
                        Horde_String::convertCharset($description, 'UTF-8',
                                                     $this->_params['charset']),
                        (int)$active);
        try {
            $result = $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw Whups_Exception($e);
        }

        return $result;
    }

    /**
     * Adds a form reply to the backend.
     *
     * @param integer $type  The ticket type id for which to add the new reply.
     * @param string $name   The reply name.
     * @param string $text   The reply text.
     *
     * @return integer  The id of the new form reply.
     * @throws Whups_Exception
     */
    public function addReply($type, $name, $text)
    {
        $query = 'INSERT INTO whups_replies (type_id, '
            . 'reply_name, reply_text) VALUES (?, ?, ?)';
        $values = array($type,
                        Horde_String::convertCharset($name, 'UTF-8',
                                               $this->_params['charset']),
                        Horde_String::convertCharset($text, 'UTF-8',
                                               $this->_params['charset']));
        try {
            $result = $this->_db->query($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return $result;
    }


    /**
     * Add a new ticket
     *
     * @param array $info  A ticket info array.
     * @param string $requester  The ticket requester.
     *
     * @return integer  The new ticket's id.
     * @throws Whups_Exception
     */
    public function addTicket(array &$info, $requester)
    {
        $type = $info['type'];
        $state = $info['state'];
        $priority = $info['priority'];
        $queue = $info['queue'];
        $summary = $info['summary'];
        $version = isset($info['version']) ? $info['version'] : null;
        $due = isset($info['due']) ? $info['due'] : null;
        $comment = $info['comment'];
        $attributes = isset($info['attributes']) ? $info['attributes'] : array();

        // Create the ticket.
        $query = 'INSERT INTO whups_tickets (ticket_summary, '
            . 'user_id_requester, type_id, state_id, priority_id, queue_id, '
            . 'ticket_timestamp, ticket_due, version_id)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $values = array(Horde_String::convertCharset(
                            $summary, 'UTF-8',
                            $this->_params['charset']),
                        $requester,
                        $type,
                        $state,
                        $priority,
                        $queue,
                        time(),
                        $due,
                        $version);

        try {
            $ticket_id = $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        // Is there a more effecient way to do this? Need the ticketId before
        // we can insert this.
        if (!empty($info['user_email'])) {
            $requester = $ticket_id * -1;
            $sql = 'UPDATE whups_tickets SET user_id_requester = ? WHERE '
                . 'ticket_id = ?';
            try {
                $this->_db->update($sql, array($requester, $ticket_id));
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }

        if ($requester < 0) {
            $query = 'INSERT INTO whups_guests (guest_id, guest_email) '
                . 'VALUES (?, ?)';
            $values = array((string)$requester, $info['user_email']);
            try {
                $this->_db->insert($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }

        $commentId = $this->addComment(
            $ticket_id, $comment, $requester,
            isset($info['user_email']) ? $info['user_email'] : null);

        $transaction = $this->updateLog($ticket_id,
                                        $requester,
                                        array('state' => $state,
                                              'priority' => $priority,
                                              'type' => $type,
                                              'summary' => $summary,
                                              'due' => $due,
                                              'comment' => $commentId,
                                              'queue' => $queue));

        // Store the last-transaction id in the ticket's info for later use if
        // needed.
        $info['last-transaction'] = $transaction;

        // Assign the ticket, if requested.
        $owners = array_merge(
            isset($info['owners']) ? $info['owners'] : array(),
            isset($info['group_owners']) ? $info['group_owners'] : array());
        foreach ($owners as $owner) {
            $this->addTicketOwner($ticket_id, $owner);
            $this->updateLog($ticket_id, $requester,
                             array('assign' => $owner),
                             $transaction);
        }

        // Add any supplied attributes for this ticket.
        foreach ($attributes as $attribute_id => $attribute_value) {
            $this->_setAttributeValue(
                $ticket_id, $attribute_id, $attribute_value);

            $this->updateLog(
                $ticket_id, $requester,
                array('attribute' => $attribute_id . ':' . $attribute_value,
                      'attribute_' . $attribute_id => $attribute_value),
                $transaction);
        }

        return $ticket_id;
    }

    /**
     * Add a new comment to a ticket.
     *
     * @param integer $ticket_id     The ticket to add comment to.
     * @param string $comment        The comment text.
     * @param string $creator        The creator of the comment
     * @param string $creator_email  The (optional) creator's email.
     *
     * @return integer  The new comment's comment_id.
     * @throws Whups_Exception
     */
    public function addComment($ticket_id, $comment, $creator, $creator_email = null)
    {
        if (empty($creator) || $creator < 0) {
            $creator = '-' . $id . '_comment';
        }

        // Add the row.
        try {
            $id = $this->_db->insert(
                'INSERT INTO whups_comments (ticket_id, user_id_creator, '
                    . ' comment_text, comment_timestamp) VALUES (?, ?, ?, ?)',
                array(
                    (int)$ticket_id,
                    $creator,
                    Horde_String::convertCharset($comment, 'UTF-8', $this->_params['charset']),
                    time()));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        if ($creator < 0 && !empty($creator_email)) {
            $query = 'INSERT INTO whups_guests (guest_id, guest_email)'
                . ' VALUES (?, ?)';
            $values = array((string)$creator, $creator_email);
            try {
                $this->_db->insert($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }

        return $id;
    }

    /**
     * Update any details of a ticket that are stored in the main
     * whups_tickets table. Does not update the ticket log (so that it can be
     * used for things low-level enough to not show up there. In general, you
     * should *always* update the log; Whups_Ticket::commit() will take care
     * of this in most cases).
     *
     * @param integer $ticketId    The id of the ticket to update.
     * @param array   $attributes  The array of attributes (key => value) to
     *                             change.
     * @throws Whups_Exception
     */
    public function updateTicket($ticketId, $attributes)
    {
        if (!count($attributes)) {
            return;
        }

        $query = '';
        $values = array();
        foreach ($attributes as $field => $value) {
            if (empty($this->_map[$field])) {
                continue;
            }

            $query .= $this->_map[$field] . ' = ?, ';
            $values[] = Horde_String::convertCharset(
                $value, 'UTF-8', $this->_params['charset']);
        }

        // Don't try to execute an empty query (if we didn't find any updates
        // to make).
        if (empty($query)) {
            return;
        }

        $query = 'UPDATE whups_tickets SET ' . substr($query, 0, -2)
            . ' WHERE ticket_id = ?';
        $values[] = (int)$ticketId;

        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Add a owner to the specified ticket.
     *
     * @param integer $ticketId  The ticket to add owner to.
     * @param string $owner      The owner id to add.
     *
     * @throws Whups_Exception
     */
    public function addTicketOwner($ticketId, $owner)
    {
        try {
            $this->_db->insert(
                'INSERT INTO whups_ticket_owners (ticket_id, ticket_owner) VALUES (?, ?)',
                array($ticketId, $owner));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Remove a ticket owner from the specified ticket.
     *
     * @param integer $ticketId  The ticket id.
     * @param string $owner      The owner to remove.
     *
     * @throws Whups_Exception
     */
    public function deleteTicketOwner($ticketId, $owner)
    {
        try {
            $this->_db->delete(
                'DELETE FROM whups_ticket_owners WHERE ticket_owner = ? AND ticket_id = ?',
                array($owner, $ticketId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Remove a ticket from storage.
     *
     * @param integer $id  The ticket id.
     *
     * @throws Whups_Exception
     */
    public function deleteTicket($id)
    {
        $id = (int)$id;

        $tables = array(
            'whups_ticket_listeners',
            'whups_logs',
            'whups_comments',
            'whups_tickets',
            'whups_attributes');

        if (!empty($GLOBALS['conf']['vfs']['type'])) {
            try {
                $vfs = $GLOBALS['injector']
                    ->getInstance('Horde_Core_Factory_Vfs')
                    ->create();
            } catch (Horde_Vfs_Exception $e) {
                throw new Whups_Exception($e);
            }

            if ($vfs->isFolder(Whups::VFS_ATTACH_PATH, $id)) {
                try {
                    $vfs->deleteFolder(Whups::VFS_ATTACH_PATH, $id, true);
                } catch (Horde_Vfs_Exception $e) {
                    throw new Whups_Exception($e);
                }
            }
        }

        // Attempt to clean up everything.
        $sql = 'SELECT DISTINCT transaction_id FROM whups_logs WHERE ticket_id = ?';
        try {
            $txs = $this->_db->selectValues($sql, array($id));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
        foreach ($tables as $table) {
            $query = 'DELETE FROM ' . $table . ' WHERE ticket_id = ?';
            $values = array($id);
            try {
                $this->_db->delete($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }

        try {
            $sql = 'DELETE FROM whups_transactions WHERE transaction_id IN '
                . '(' . str_repeat('?,', count($txs) - 1) . '?)';
            $this->_db->delete($sql, $txs);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Execute a Whups query
     *
     * @param Whups_Query $query     The query object to execute.
     * @param Horde_Variables $vars  The variables
     * @param boolean $get_details   Get all details
     * @param boolean $munge         @TODO (?)
     *
     * @return array  An array of ticket_ids that match the query criteria.
     * @throws Whups_Exception
     */
    public function executeQuery(
        Whups_Query $query, Horde_Variables $vars, $get_details = true,
        $munge = true)
    {
        $this->jtables = array();
        $this->joins   = array();

        $where = $query->reduce($this, '_clauseFromQuery', $vars);
        if (!$where) {
            $GLOBALS['notification']->push(_("No query to run"), 'horde.message');
            return array();
        }

        if ($this->joins) {
            $joins = implode(' ', $this->joins);
        } else {
            $joins = '';
        }

        $sql = "SELECT whups_tickets.ticket_id FROM whups_tickets $joins "
            . "WHERE $where";

        try {
            $ids = $this->_db->selectValues($sql);
        } catch (Horde_Db_Exception $e) {
            $GLOBALS['notification']->push($e->getMessage(), 'horde.error');
            return array();
        }

        if (!count($ids)) {
            return array();
        }

        if ($get_details) {
            $ids = $this->getTicketsByProperties(array('id' => $ids), $munge);
        }

        return $ids;
    }

    public function _clauseFromQuery(
        $args, $type, $criterion, $cvalue, $operator, $value)
    {
        switch ($type) {
        case Whups_Query::TYPE_AND:
            return $this->_concatClauses($args, 'AND');

        case Whups_Query::TYPE_OR:
            return $this->_concatClauses($args, 'OR');

        case Whups_Query::TYPE_NOT:
            return $this->_notClause($args);

        case Whups_Query::TYPE_CRITERION:
            return $this->_criterionClause($criterion, $cvalue, $operator, $value);
        }
    }

    protected function _concatClauses($args, $conjunction)
    {
        $count = count($args);

        if ($count == 0) {
            $result = '';
        } elseif ($count == 1) {
            $result = $args[0];
        } else {
            $result = '(' . $args[0] . ')';

            for ($i = 1; $i < $count; $i++) {
                if ($args[$i] != '') {
                    $result .= ' ' . $conjunction . ' (' . $args[$i] . ')';
                }
            }
        }

        return $result;
    }

    protected function _notClause($args)
    {
        if (count($args) == 0) {
            return '';
        }

        if (count($args) !== 1) {
            throw InvalidArgumentException();
        }

        return 'NOT (' . $args[0] . ')';
    }

    /**
     * @TODO: The rdbms specific clauses should be refactored to use
     * Horde_Db_Adapter_Base_Schema#buildClause
     *
     * @return string
     */
    protected function _criterionClause($criterion, $cvalue, $operator, $value)
    {
        $func    = '';
        $funcend = '';

        switch ($operator) {
        case Whups_Query::OPERATOR_GREATER: $op = '>'; break;
        case Whups_Query::OPERATOR_LESS:    $op = '<'; break;
        case Whups_Query::OPERATOR_EQUAL:   $op = '='; break;
        case Whups_Query::OPERATOR_PATTERN: $op = 'LIKE'; break;

        case Whups_Query::OPERATOR_CI_SUBSTRING:
            $value = '%' . str_replace(array('%', '_'), array('\%', '\_'), $value) . '%';
            if ($this->_db->phptype == 'pgsql') {
                $op = 'ILIKE';
            } else {
                $op = 'LIKE';
                $func = 'LOWER(';
                $funcend = ')';
            }
            break;

        case Whups_Query::OPERATOR_CS_SUBSTRING:
            // FIXME: Does not work in Postgres.
            $func    = 'LOCATE(' . $this->_db->quoteString($value) . ', ';
            $funcend = ')';
            $op      = '>';
            $value   = 0;
            break;

        case Whups_Query::OPERATOR_WORD:
            // TODO: There might be a better way to avoid missing
            // words at the start and end of the text field.
            if ($this->_db->phptype == 'pgsql') {
                $func = "' ' || ";
                $funcend = " || ' '";
            } else {
                $func    = "CONCAT(' ', CONCAT(";
                $funcend = ", ' '))";
            }
            $op      = 'LIKE';
            $value = '%' . str_replace(array('%', '_'), array('\%', '\_'), $value) . '%';
            break;
        }

        $qvalue = $this->_db->quoteString($value);
        $done = false;
        $text = '';

        switch ($criterion) {
        case Whups_Query::CRITERION_ID:
            $text = "{$func}whups_tickets.ticket_id{$funcend}";
            break;

        case Whups_Query::CRITERION_QUEUE:
            $text = "{$func}whups_tickets.queue_id{$funcend}";
            break;

        case Whups_Query::CRITERION_VERSION:
            $text = "{$func}whups_tickets.version_id{$funcend}";
            break;

        case Whups_Query::CRITERION_TYPE:
            $text = "{$func}whups_tickets.type_id{$funcend}";
            break;

        case Whups_Query::CRITERION_STATE:
            $text = "{$func}whups_tickets.state_id{$funcend}";
            break;

        case Whups_Query::CRITERION_PRIORITY:
            $text = "{$func}whups_tickets.priority_id{$funcend}";
            break;

        case Whups_Query::CRITERION_SUMMARY:
            $text = "{$func}whups_tickets.ticket_summary{$funcend}";
            break;

        case Whups_Query::CRITERION_TIMESTAMP:
            $text = "{$func}whups_tickets.ticket_timestamp{$funcend}";
            break;

        case Whups_Query::CRITERION_UPDATED:
            $text = "{$func}whups_tickets.date_updated{$funcend}";
            break;

        case Whups_Query::CRITERION_RESOLVED:
            $text = "{$func}whups_tickets.date_resolved{$funcend}";
            break;

        case Whups_Query::CRITERION_ASSIGNED:
            $text = "{$func}whups_tickets.date_assigned{$funcend}";
            break;

        case Whups_Query::CRITERION_DUE:
            $text = "{$func}whups_tickets.ticket_due{$funcend}";
            break;

        case Whups_Query::CRITERION_ATTRIBUTE:
            $cvalue = (int)$cvalue;

            if (!isset($this->jtables['whups_attributes'])) {
                $this->jtables['whups_attributes'] = 1;
            }
            $v = $this->jtables['whups_attributes']++;

            $this->joins[] = "LEFT JOIN whups_attributes wa$v ON (whups_tickets.ticket_id = wa$v.ticket_id AND wa$v.attribute_id = $cvalue)";
            $text = "{$func}wa$v.attribute_value{$funcend} $op $qvalue";
            $done = true;
            break;

        case Whups_Query::CRITERION_OWNERS:
            if (!isset($this->jtables['whups_ticket_owners'])) {
                $this->jtables['whups_ticket_owners'] = 1;
            }
            $v = $this->jtables['whups_ticket_owners']++;

            $this->joins[] = "LEFT JOIN whups_ticket_owners wto$v ON whups_tickets.ticket_id = wto$v.ticket_id";
            $qvalue = $this->_db->quotestring('user:' . $value);
            $text = "{$func}wto$v.ticket_owner{$funcend} $op $qvalue";
            $done = true;
            break;

        case Whups_Query::CRITERION_REQUESTER:
            if (!isset($this->jtables['whups_guests'])) {
                $this->jtables['whups_guests'] = 1;
            }
            $v = $this->jtables['whups_guests']++;

            $this->joins[] = "LEFT JOIN whups_guests wg$v ON whups_tickets.user_id_requester = wg$v.guest_id";
            $text = "{$func}whups_tickets.user_id_requester{$funcend} $op $qvalue OR {$func}wg$v.guest_email{$funcend} $op $qvalue";
            $done = true;
            break;

        case Whups_Query::CRITERION_GROUPS:
            if (!isset($this->jtables['whups_ticket_owners'])) {
                $this->jtables['whups_ticket_owners'] = 1;
            }
            $v = $this->jtables['whups_ticket_owners']++;

            $this->joins[] = "LEFT JOIN whups_ticket_owners wto$v ON whups_tickets.ticket_id = wto$v.ticket_id";
            $qvalue = $this->_db->quoteString('group:' . $value);
            $text = "{$func}wto$v.ticket_owner{$funcend} $op $qvalue";
            $done = true;
            break;

        case Whups_Query::CRITERION_ADDED_COMMENT:
            if (!isset($this->jtables['whups_comments'])) {
                $this->jtables['whups_comments'] = 1;
            }
            $v = $this->jtables['whups_comments']++;

            $this->joins[] = "LEFT JOIN whups_comments wc$v ON (whups_tickets.ticket_id = wc$v.ticket_id)";
            $text = "{$func}wc$v.user_id_creator{$funcend} $op $qvalue";
            $done = true;
            break;

        case Whups_Query::CRITERION_COMMENT:
            if (!isset($this->jtables['whups_comments'])) {
                $this->jtables['whups_comments'] = 1;
            }
            $v = $this->jtables['whups_comments']++;

            $this->joins[] = "LEFT JOIN whups_comments wc$v ON (whups_tickets.ticket_id = wc$v.ticket_id)";
            $text = "{$func}wc$v.comment_text{$funcend} $op $qvalue";
            $done = true;
            break;
        }

        if ($done == false) {
            $text .= " $op $qvalue";
        }

        return $text;
    }

    /**
     * Get tickets by searching for it's properties
     *
     * @param array $info        An array of properties to search for.
     * @param boolean $munge     Munge the query (?)
     * @param boolean $perowner  Group the results per owner?
     *
     * @return array  An array of ticket information hashes.
     * @throws Whups_Exception
     */
    public function getTicketsByProperties(array $info, $munge = true, $perowner = false)
    {
        // Search conditions.
        $where = $this->_generateWhere(
            'whups_tickets',
            array('ticket_id', 'type_id', 'state_id', 'priority_id', 'queue_id'),
            $info, 'integer');

        $where2 = $this->_generateWhere(
            'whups_tickets', array('user_id_requester'), $info, 'string');

        if (empty($where)) {
            $where = $where2;
        } elseif (!empty($where2)) {
            $where .= ' AND ' . $where2;
        }

        // Add summary filter if present.
        if (!empty($info['summary'])) {
            $where = $this->_addWhere(
                $where, 1,
                'LOWER(whups_tickets.ticket_summary) LIKE '
                . $this->_db->quotestring('%' . Horde_String::lower($info['summary']) . '%'));
        }

        // Add date fields.
        if (!empty($info['ticket_timestamp'])) {
            $where = $this->_addDateWhere($where, $info['ticket_timestamp'], 'ticket_timestamp');
        }
        if (!empty($info['date_updated'])) {
            $where = $this->_addDateWhere($where, $info['date_updated'], 'date_updated');
        }
        if (!empty($info['date_assigned'])) {
            $where = $this->_addDateWhere($where, $info['date_assigned'], 'date_assigned');
        }
        if (!empty($info['date_resolved'])) {
            $where = $this->_addDateWhere($where, $info['date_resolved'], 'date_resolved');
        }
        if (!empty($info['ticket_due'])) {
            $where = $this->_addDateWhere($where, $info['ticket_due'], 'ticket_due');
        }

        $fields = array(
            'ticket_id AS id',
            'ticket_summary AS summary',
            'user_id_requester',
            'state_id AS state',
            'type_id AS type',
            'priority_id AS priority',
            'queue_id AS queue',
            'date_updated',
            'date_assigned',
            'date_resolved',
            'version_id AS version');

        $fields = $this->_prefixTableToColumns('whups_tickets', $fields)
            . ', whups_tickets.ticket_timestamp AS timestamp, whups_tickets.ticket_due AS due';
        $tables = 'whups_tickets';
        $join = '';
        $groupby = 'whups_tickets.ticket_id, whups_tickets.ticket_summary, whups_tickets.user_id_requester, whups_tickets.state_id, whups_tickets.type_id, whups_tickets.priority_id, whups_tickets.queue_id, whups_tickets.ticket_timestamp, whups_tickets.ticket_due, whups_tickets.date_updated, whups_tickets.date_assigned, whups_tickets.date_resolved';

        // State filters.
        if (isset($info['category'])) {
            if (is_array($info['category'])) {
                $cat = '';
                foreach ($info['category'] as $category) {
                    if (!empty($cat)) {
                        $cat .= ' OR ';
                    }
                    $cat .= 'whups_states.state_category = '
                        . $this->_db->quotestring($category);
                }
                $cat = ' AND (' . $cat . ')';
            } else {
                $cat = isset($info['category'])
                    ? ' AND whups_states.state_category = '
                        . $this->_db->quotestring($info['category'])
                    : '';
            }
        } else {
            $cat = '';
        }

        // Type filters.
        if (isset($info['type_id'])) {
            if (is_array($info['type_id'])) {
                $t = array();
                foreach ($info['type_id'] as $type) {
                    $t[] = 'whups_tickets.type_id = '
                        . $this->_db->quotestring($type);
                }
                $t = ' AND (' . implode(' OR ', $t) . ')';
            } else {
                $t = isset($info['type_id'])
                    ? ' AND whups_tickets.type_id = '
                        . $this->_db->quotestring($info['type_id'])
                    : '';
            }

            $this->_addWhere($where, $t, $t);
        }

        $nouc = isset($info['nouc'])
            ? " AND whups_states.state_category <> 'unconfirmed'" : '';
        $nores = isset($info['nores'])
            ? " AND whups_states.state_category <> 'resolved'" : '';
        $nonew = isset($info['nonew'])
            ? " AND whups_states.state_category <> 'new'" : '';
        $noass = isset($info['noass'])
            ? " AND whups_states.state_category <> 'assigned'" : '';

        $uc = isset($info['uc'])
            ? " AND whups_states.state_category = 'unconfirmed'" : '';
        $res = isset($info['res'])
            ? " AND whups_states.state_category = 'resolved'" : '';
        $new = isset($info['new'])
            ? " AND whups_states.state_category = 'new'" : '';
        $ass = isset($info['ass'])
            ? " AND whups_states.state_category = 'assigned'" : '';

        // If there are any state filters, add them in.
        if ($nouc || $nores || $nonew || $noass ||
            $uc || $res || $new || $ass || $cat) {
            $where = $this->_addWhere($where, 1, "(whups_tickets.type_id = whups_states.type_id AND whups_tickets.state_id = whups_states.state_id$nouc$nores$nonew$noass$uc$res$new$ass$cat)");
        }

        // Initialize join clauses.
        $join = '';

        // Handle owner properties.
        if (isset($info['owner'])) {
            $join .= ' INNER JOIN whups_ticket_owners ON whups_tickets.ticket_id = whups_ticket_owners.ticket_id AND ';
            if (is_array($info['owner'])) {
                $clauses = array();
                foreach ($info['owner'] as $owner) {
                    $clauses[] = 'whups_ticket_owners.ticket_owner = '
                        . $this->_db->quotestring($owner);
                }
                $join .= '(' . implode(' OR ', $clauses) . ')';
            } else {
                $join .= 'whups_ticket_owners.ticket_owner = '
                    . $this->_db->quotestring($info['owner']);
            }
        }
        if (isset($info['notowner'])) {
            if ($info['notowner'] === true) {
                // Filter for tickets with no owner.
                $join .= ' LEFT JOIN whups_ticket_owners ON whups_tickets.ticket_id = whups_ticket_owners.ticket_id AND whups_ticket_owners.ticket_owner IS NOT NULL';
            } else {
                $join .= ' LEFT JOIN whups_ticket_owners ON whups_tickets.ticket_id = whups_ticket_owners.ticket_id AND whups_ticket_owners.ticket_owner = ' . $this->_db->quotestring($info['notowner']);
            }
            $where = $this->_addWhere($where, 1,
                                      'whups_ticket_owners.ticket_id IS NULL');
        }

        if ($munge) {
            $myqueues = $GLOBALS['registry']->hasMethod('tickets/listQueues') == $GLOBALS['registry']->getApp();
            $myversions = $GLOBALS['registry']->hasMethod('tickets/listVersions') == $GLOBALS['registry']->getApp();
            $fields = "$fields, " .
                'whups_types.type_name AS type_name, ' .
                'whups_states.state_name AS state_name, ' .
                'whups_states.state_category AS state_category, ' .
                'whups_priorities.priority_name AS priority_name';

            $join .=
                ' INNER JOIN whups_types ON whups_tickets.type_id = whups_types.type_id' .
                ' INNER JOIN whups_states ON whups_tickets.state_id = whups_states.state_id' .
                ' INNER JOIN whups_priorities ON whups_tickets.priority_id = whups_priorities.priority_id' .
                ' INNER JOIN whups_states state2 ON whups_tickets.type_id = state2.type_id';

            $groupby .= ', whups_types.type_name, whups_states.state_name, whups_states.state_category';
            if ($myversions) {
                $versions = array();
                $fields .= ', whups_versions.version_name AS version_name'
                    . ', whups_versions.version_description AS version_description'
                    . ', whups_versions.version_active AS version_active';
                $join .= ' LEFT JOIN whups_versions ON whups_tickets.version_id = whups_versions.version_id';
                $groupby .= ', whups_versions.version_name, whups_versions.version_description, whups_versions.version_active, whups_tickets.version_id';
            }
            if ($myqueues) {
                $queues = array();
                $fields .= ', whups_queues.queue_name AS queue_name';
                $join .= ' INNER JOIN whups_queues ON whups_tickets.queue_id = whups_queues.queue_id';
                $groupby .= ', whups_queues.queue_name';
            }
            $groupby .= ', whups_priorities.priority_name';
        }

        if ($perowner) {
            $join .= ' LEFT JOIN whups_ticket_owners ON whups_tickets.ticket_id = whups_ticket_owners.ticket_id';
            $fields .= ', whups_ticket_owners.ticket_owner AS owner';
            $groupby .= ', whups_ticket_owners.ticket_owner';
        }

        $query = "SELECT $fields FROM $tables$join "
            . (!empty($where) ? "WHERE $where " : '')
            . 'GROUP BY ' . $groupby;

        try {
            $info = $this->_db->selectAll($query);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        if (!count($info)) {
            return array();
        }

        $info = Horde_String::convertCharset($info, $this->_params['charset'], 'UTF-8');

        $tickets = array();
        foreach ($info as $ticket) {
            if ($munge) {
                if (!$myqueues) {
                    if (!isset($queues[$ticket['queue']])) {
                        $queues[$ticket['queue']] = $GLOBALS['registry']->call(
                            'tickets/getQueueDetails',
                            array($ticket['queue']));
                    }
                    $ticket['queue_name'] = $queues[$ticket['queue']]['name'];
                    if (isset($queues[$ticket['queue']]['link'])) {
                        $ticket['queue_link'] = $queues[$ticket['queue']]['link'];
                    }
                }
                if (!$myversions) {
                    if (!isset($versions[$ticket['version']])) {
                        $versions[$ticket['version']] = $GLOBALS['registry']->call(
                            'tickets/getVersionDetails',
                            array($ticket['version']));
                    }
                    $ticket['version_name'] = $versions[$ticket['version']]['name'];
                    if (isset($versions[$ticket['version']]['link'])) {
                        $ticket['version_link'] = $versions[$ticket['version']]['link'];
                    }
                }
            }
            $tickets[$ticket['id']] = $ticket;
        }

        $owners = $this->getOwners(array_keys($tickets));
        foreach ($owners as $id => $row) {
            if (empty($tickets[$id]['owners'])) {
                $tickets[$id]['owners'] = array();
            }
            $tickets[$id]['owners'][] = $row;
        }

        $attributes = $this->getTicketAttributesWithNames(array_keys($tickets));
        foreach ($attributes as $row) {
            $attribute_id = 'attribute_' . $row['attribute_id'];
            $tickets[$row['id']][$attribute_id] = $row['attribute_value'];
            $tickets[$row['id']][$attribute_id . '_name'] = $row['attribute_name'];
        }

        return array_values($tickets);
    }

    /**
     * Get a ticket's details from storage.
     *
     * @param integer $ticket      The ticket id.
     * @param boolean $checkPerms  Enforce permissions?
     *
     * @return array  A ticket information hash.
     * @throws Horde_Exception_NotFound, Horde_Exception_PermissionDenied
     */
    public function getTicketDetails($ticket, $checkPerms = true)
    {
        $result = $this->getTicketsByProperties(array('id' => $ticket));
        if (!isset($result[0])) {
            throw new Horde_Exception_NotFound(
                sprintf(_("Ticket %s was not found."), $ticket));
        } else {
            $queues = Whups::permissionsFilter(
                $this->getQueues(), 'queue', Horde_Perms::READ,
                $GLOBALS['registry']->getAuth(), $result[0]['user_id_requester']);
            if ($checkPerms &&
                !in_array($result[0]['queue'], array_flip($queues))) {

                throw new Horde_Exception_PermissionDenied(
                    sprintf(_("You do not have permission to access this ticket (%s)."), $ticket));
            }
        }

        return $result[0];
    }

    /**
     * Obtain the current state of the specified ticket.
     *
     * @param integer $ticket_id  The ticket id.
     *
     * @return integer  The state id
     * @throws Whups_Exception
     */
    public function getTicketState($ticket_id)
    {
        $query = 'SELECT whups_tickets.state_id, whups_states.state_category '
            . 'FROM whups_tickets INNER JOIN whups_states '
            . 'ON whups_tickets.state_id = whups_states.state_id '
            . 'WHERE ticket_id = ?';

        try {
            $state = $this->_db->SelectOne($query, array($ticket_id));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return $state;
    }

    /**
     * Get a guest's email address
     *
     * @param integer $guest_id  The guest id.
     *
     * @return string  The guest's email address.
     * @throws Whups_Exception
     */
    public function getGuestEmail($guest_id)
    {
        if (!isset($this->_guestEmailCache[$guest_id])) {
            $query = 'SELECT guest_email FROM whups_guests WHERE guest_id = ?';
            $values = array($guest_id);
            try {
                $result = $this->_db->selectValue($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
            $this->_guestEmailCache[$guest_id] = Horde_String::convertCharset(
                $result, $this->_params['charset'], 'UTF-8');
        }

        return $this->_guestEmailCache[$guest_id];
    }


    /**
     * Fetch ticket's history from storage
     *
     * @param integer $ticket_id  The ticket's id.
     *
     * @return array The ticket's history.
     * @throws Whups_Exception
     */
    protected function _getHistory($ticket_id)
    {
        $where = 'whups_logs.ticket_id = ' . (int)$ticket_id;
        $join  = 'LEFT JOIN whups_comments
                    ON whups_logs.log_type = \'comment\'
                    AND whups_logs.log_value_num = whups_comments.comment_id
                  LEFT JOIN whups_versions
                    ON whups_logs.log_type = \'version\'
                    AND whups_logs.log_value_num = whups_versions.version_id
                  LEFT JOIN whups_states
                    ON whups_logs.log_type = \'state\'
                    AND whups_logs.log_value_num = whups_states.state_id
                  LEFT JOIN whups_priorities
                    ON whups_logs.log_type = \'priority\'
                    AND whups_logs.log_value_num = whups_priorities.priority_id
                  LEFT JOIN whups_types
                    ON whups_logs.log_type = \'type\'
                    AND whups_logs.log_value_num = whups_types.type_id
                  LEFT JOIN whups_attributes_desc
                    ON whups_logs.log_type = \'attribute\'
                    AND whups_logs.log_value_num = whups_attributes_desc.attribute_id
                  LEFT JOIN whups_transactions
                    ON whups_logs.transaction_id = whups_transactions.transaction_id';

        $fields = $this->_prefixTableToColumns('whups_comments',
                                               array('comment_text'))
            . ', whups_transactions.transaction_timestamp AS timestamp, whups_logs.ticket_id'
            . ', whups_logs.log_type, whups_logs.log_value'
            . ', whups_logs.log_value_num, whups_logs.log_id'
            . ', whups_logs.transaction_id, whups_transactions.transaction_user_id user_id'
            . ', whups_priorities.priority_name, whups_states.state_name, whups_versions.version_name'
            . ', whups_types.type_name, whups_attributes_desc.attribute_name';

        $query = "SELECT $fields FROM whups_logs $join WHERE $where "
            . "ORDER BY whups_logs.transaction_id";

        try {
            $history = $this->_db->selectAll($query);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        $history = Horde_String::convertCharset(
            $history, $this->_params['charset'], 'UTF-8');
        for ($i = 0, $iMax = count($history); $i < $iMax; ++$i) {
            if ($history[$i]['log_type'] == 'queue') {
                $queue = $this->getQueue($history[$i]['log_value_num']);
                $history[$i]['queue_name'] = $queue ? $queue['name'] : null;
            }
        }

        return $history;
    }

    /**
     * Deletes all changes of a transaction.
     *
     * @param integer $transaction  A transaction id.
     * @throws Whups_Exception
     */
    public function deleteHistory($transaction)
    {
        $transaction = (int)$transaction;

        /* Deleting comments. */
        $query = 'SELECT log_value FROM whups_logs WHERE log_type = ? AND transaction_id = ?';
        $values = array('comment', $transaction);

        try {
            $comments = $this->_db->selectValues($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        if ($comments) {
            $query = sprintf(
                'DELETE FROM whups_comments WHERE comment_id IN (%s)',
                implode(',', $comments));
            try {
                $this->_db->delete($query);
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }

        /* Deleting attachments. */
        if (isset($GLOBALS['conf']['vfs']['type'])) {
            $query = 'SELECT ticket_id, log_value FROM whups_logs WHERE log_type = ? AND transaction_id = ?';
            $values = array('attachment', $transaction);
            try {
                $attachments = $this->_db->selectAll($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }

            $vfs = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Vfs')
                ->create();
            foreach ($attachments as $attachment) {
                $dir = Whups::VFS_ATTACH_PATH . '/' . $attachment['ticket_id'];
                if ($vfs->exists($dir, $attachment['log_value'])) {
                    try {
                        $result = $vfs->deleteFile($dir, $attachment['log_value']);
                    } catch (Horde_Vfs_Exception $e) {
                        throw new Whups_Exception($e);
                    }
                } else {
                    Horde::logMessage(sprintf(_("Attachment %s not found."),
                                              $attachment['log_value']),
                                      'WARN');
                }
            }
        }

        $query = 'DELETE FROM whups_logs WHERE transaction_id = ?';
        $delete_transaction = 'DELETE FROM whups_transactions WHERE transaction_id = ?';
        try {
            $this->_db->delete($query, array($transaction));
            $this->_db->delete($delete_transaction, array($transaction));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Return a list of queues with open tickets, and the number of
     * open tickets in each.
     *
     * @param array $queues Array of queue ids to summarize.
     *
     * @return array  An array containing a hash of the queues' summaries.
     * @throws Whups_Exception
     */
    public function getQueueSummary($queue_ids)
    {
        $qstring = (int)array_shift($queue_ids);
        while ($queue_ids) {
            $qstring .= ', ' . (int)array_shift($queue_ids);
        }

        $sql = 'SELECT q.queue_id AS id, q.queue_slug AS slug, '
            . 'q.queue_name AS name, q.queue_description AS description, '
            . 'ty.type_name as type, COUNT(t.ticket_id) AS open_tickets '
            . 'FROM whups_queues q LEFT JOIN whups_tickets t '
            . 'ON q.queue_id = t.queue_id '
            . 'INNER JOIN whups_states s '
            . 'ON (t.state_id = s.state_id AND s.state_category != \'resolved\') '
            . 'INNER JOIN whups_types ty ON ty.type_id = t.type_id '
            . 'WHERE q.queue_id IN (' . $qstring . ') '
            . 'GROUP BY q.queue_id, q.queue_slug, q.queue_name, '
            . 'q.queue_description, ty.type_name ORDER BY q.queue_name';

        try {
            $queues = $this->_db->selectAll($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return Horde_String::convertCharset(
            $queues, $this->_params['charset'], 'UTF-8');
    }

    /**
     * Get an internal representation of the queue (?).
     *
     * @param integer $queueId  The queue Id.
     *
     * @return array  A queue hash.
     * @throws Whups_Exception
     */
    public function getQueueInternal($queueId)
    {
        if (isset($this->_internalQueueCache[$queueId])) {
            return $this->_internalQueueCache[$queueId];
        }

        $query = 'SELECT queue_id, queue_name, queue_description, '
            . 'queue_versioned, queue_slug, queue_email '
            . 'FROM whups_queues WHERE queue_id = ?';
        $values = array((int)$queueId);
        try {
            $queue = $this->_db->selectOne($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        if (!$queue) {
            return array();
        }

        $queue = Horde_String::convertCharset($queue, $this->_params['charset'], 'UTF-8');
        $this->_internalQueueCache[$queueId] = array(
            'id' => (int)$queue['queue_id'],
            'name' => $queue['queue_name'],
            'description' => $queue['queue_description'],
            'versioned' => (bool)$queue['queue_versioned'],
            'slug' => $queue['queue_slug'],
            'email' => $queue['queue_email'],
            'readonly' => false);

        return $this->_internalQueueCache[$queueId];
    }


    /**
     * Obtain internal queue hash by slug.
     *
     * @param string $slug  The queue slug.
     *
     * @return array An internal queue hash.
     * @throws Whups_Exception
     */
    public function getQueueBySlugInternal($slug)
    {
        $query = 'SELECT queue_id, queue_name, queue_description, '
            . 'queue_versioned, queue_slug FROM whups_queues WHERE '
            . 'queue_slug = ?';
        $values = array((string)$slug);
        try {
            $queue = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
        if (!count($queue)) {
            return $queue;
        }

        $queue = Horde_String::convertCharset(
            $queue, $this->_params['charset'], 'UTF-8');
        $queue = $queue[0];
        return array(
            'id' => $queue[0],
            'name' => $queue[1],
            'description' => $queue[2],
            'versioned' => $queue[3],
            'slug' => $queue[4],
            'readonly' => false);
    }

    /**
     * Get list of available queues.
     *
     * @return array  An hash of queue_id => queue_name
     * @throws Whups_Exception
     */
    public function getQueuesInternal()
    {
        if (!is_null($this->_queues)) {
            return $this->_queues;
        }

        $query = 'SELECT queue_id, queue_name FROM whups_queues '
            . 'ORDER BY queue_name';
        try {
            $this->_queues = $this->_db->selectAssoc($query);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        $this->_queues = Horde_String::convertCharset(
            $this->_queues, $this->_params['charset'], 'UTF-8');

        return $this->_queues;
    }

    /**
     * Get list of all availabel slugs.
     *
     * @return array  A hash of queue_id => queue_slugs
     * @throws Whups_Exception
     */
    public function getSlugs()
    {
        if (!is_null($this->_slugs)) {
            return $this->_slugs;
        }

        $query = 'SELECT queue_id, queue_slug FROM whups_queues '
            . 'WHERE queue_slug IS NOT NULL AND queue_slug <> \'\' '
            . 'ORDER BY queue_slug';

        try {
            $queues = $this->_db->selectAssoc($query);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        $this->_slugs = Horde_String::convertCharset(
            $queues, $this->_params['charset'], 'UTF-8');

        return $this->_slugs;
    }

    /**
     * Update a queue
     *
     * @param integer $queueId     Queue Id.
     * @param string $name         Queue name.
     * @param string $description  The queue description.
     * @param array $types         An array of type ids for this queue.
     * @param integer $versioned   Is this queue versioned? (1 = true, 0 = false)
     * @param string $slug         The queue slug.
     * @param string $email        Email address for queue.
     * @param integer $default  The default type of ticket for this queue.
     *
     * @throws Whups_Exception
     */
    public function updateQueue(
        $queueId, $name, $description, array $types = array(), $versioned = 0,
        $slug = '', $email = '', $default = null)
    {
        global $registry;

        if ($registry->hasMethod('tickets/listQueues') == $registry->getApp()) {
            // Is slug unique?
            try {
                $result = $this->_db->selectValue(
                    'SELECT count(queue_slug) FROM whups_queues WHERE queue_slug = ? AND queue_id <> ?',
                     array($slug, $queueId));
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
            if ($result > 0) {
                throw new Whups_Exception(
                    _("That queue slug is already taken. Please select another."));
            }

            // First update the queue entry itself.
            $query = 'UPDATE whups_queues SET queue_name = ?, '
                     . 'queue_description = ?, queue_versioned = ?, '
                     . 'queue_slug = ?, queue_email = ? WHERE queue_id = ?';
            $values = array(
                Horde_String::convertCharset(
                    $name,
                    'UTF-8',
                    $this->_params['charset']),
                Horde_String::convertCharset(
                    $description,
                    'UTF-8',
                    $this->_params['charset']),
                (empty($versioned) ? 0 : 1),
                    $slug,
                    $email,
                    $queueId);

            try {
                $this->_db->update($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }

        // Clear all previous type-queue associations.
        $query = 'DELETE FROM whups_types_queues WHERE queue_id = ?';
        $values = array($queueId);
        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        // Add the new associations.
        if (is_array($types)) {
            foreach ($types as $typeId) {
                $query = 'INSERT INTO whups_types_queues '
                    . '(queue_id, type_id, type_default) VALUES (?, ?, ?)';
                $values = array($queueId, $typeId, $default == $typeId ? 1 : 0);

                try {
                    $this->_db->insert($query, $values);
                } catch (Horde_Db_Exception $e) {
                    throw new Whups_Exception($e);
                }
            }
        }
    }

    /**
     * Obtain the specified queue's default type.
     *
     * @param integer $queue  The queue id.
     *
     * @return integer  The default type's type_id
     */
    public function getDefaultType($queue)
    {
        try {
            $type = $this->_db->selectValue(
                'SELECT type_id FROM whups_types_queues WHERE type_default = 1 AND queue_id = ?',
                array($queue));
        } catch (Horde_Db_Exception $e) {
            return null;
        }

        return $type;
    }

    /**
     * Deletes an entire queue, and all references to it.
     *
     * @param integer $queueId  The queue id to delete.
     *
     * @throws Whups_Exception
     */
    public function deleteQueue($queueId)
    {
        // Clean up the tickets associated with the queue.
        $query = 'SELECT ticket_id FROM whups_tickets WHERE queue_id = ?';
        $values = array($queueId);
        try {
            $result = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        foreach ($result as $ticket) {
          $this->deleteTicket($ticket['ticket_id']);
        }

        // Now remove all references to the queue itself
        // Note that whups_tickets could be in this list below, but there
        // should never be tickets left for the queue at this point
        // because they were all deleted above.
        $tables = array(
            'whups_queues_users',
            'whups_types_queues',
            'whups_versions',
            'whups_queues');
        foreach ($tables as $table) {
            $query = 'DELETE FROM ' . $table . ' WHERE queue_id = ?';
            $values = array($queueId);
            try {
                $this->_db->delete($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }

        return parent::deleteQueue($queueId);
    }

    /**
     * Update type/queue associations(?)
     *
     * @param array  An array of mappings(?)
     *
     * @throws Whups_Exception
     */
    public function updateTypesQueues(array $tmPairs)
    {
        // Do this as a transaction.
        $this->_db->beginDbTransaction();

        // Delete existing associations.
        $query = 'DELETE FROM whups_types_queues';
        try {
            $this->_db->delete($query);
        } catch (Horde_Db_Exception $e) {
            $this->_db->rollbackDbTransaction();
            throw new Whups_Exception($e);
        }

        // Insert new associations.
        foreach ($tmPairs as $pair) {
            $query = 'INSERT INTO whups_types_queues (queue_id, type_id) '
                . 'VALUES (?, ?)';
            $values = array((int)$pair[0], (int)$pair[1]);
            try {
                $this->_db->insert($query, $values);
            } catch (Horde_Db_Exception $e) {
                $this->_db->rollbackDbTransaction();
                throw new Whups_Exception($e);
            }
        }

        try {
            $this->_db->commitDbTransaction();
        } catch (Horde_Db_Exception $e) {
            $this->_db->rollbackDbTransaction();
            throw new Whups_Exception($e);
        }
    }

    /**
     * Get list of queue's users
     *
     * @param integer $queueId  The queue id to list users for.
     *
     * @return array  An array of queue users.
     * @throws Whups_Execption
     */
    public function getQueueUsers($queueId)
    {
        $query = 'SELECT user_uid AS u1, user_uid AS u2 FROM whups_queues_users'
            . ' WHERE queue_id = ? ORDER BY u1';
        $values = array($queueId);
        try {
            $users = $this->_db->selectAssoc($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return $users;
    }

    /**
     * Add a new queue user.
     *
     * @param integer $queueId  The queue id.
     * @param string  $userId   The user id to add.
     *
     * @throws Whups_Exception
     */
    public function addQueueUser($queueId, $userId)
    {
        if (!is_array($userId)) {
            $userId = array($userId);
        }
        foreach ($userId as $user) {
            $query = 'INSERT INTO whups_queues_users (queue_id, user_uid) '
                . 'VALUES (?, ?)';
            $values = array($queueId, $user);
            try {
                $this->_db->insert($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }
    }

    /**
     * Remove a user from a queue.
     *
     * @param integer $queueId  The queue id.
     * @param string $userId    The user id to remove.
     *
     * @throws Whups_Exception
     */
    public function removeQueueUser($queueId, $userId)
    {
        $query = 'DELETE FROM whups_queues_users' .
                 ' WHERE queue_id = ? AND user_uid = ?';
        $values = array($queueId, $userId);

        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Get a type from storage
     *
     * @param integer $typeId  The type id to retrieve.
     *
     * @return array  The type information
     * @throws Whups_Exception
     */
    public function getType($typeId)
    {
        if (empty($typeId)) {
            return false;
        }
        $query = 'SELECT type_id, type_name, type_description '
            . 'FROM whups_types WHERE type_id = ?';
        $values = array($typeId);
        try {
            $type[$typeId] = $this->_db->selectOne($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        $type = Horde_String::convertCharset(
            $type, $this->_params['charset'], 'UTF-8');
        return array(
            'id' => $typeId,
            'name' => isset($type[$typeId]['type_name']) ? $type[$typeId]['type_name'] : '',
            'description' => isset($type[$typeId]['type_description']) ? $type[$typeId]['type_description'] : '');
    }

    /**
     *
     * @param integer $queueId  The queue id
     *
     * @return array  An array of type_id => type_name
     * @throws Whups_Exception
     */
    public function getTypes($queueId)
    {
        $query = 'SELECT t.type_id, t.type_name '
            . 'FROM whups_types t, whups_types_queues tm '
            . 'WHERE tm.queue_id = ? AND tm.type_id = t.type_id '
            . 'ORDER BY t.type_name';
        $values = array($queueId);
        try {
            $types = $this->_db->selectAssoc($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return Horde_String::convertCharset(
            $types, $this->_params['charset'], 'UTF-8');
    }

    /**
     * Get list of available type ids.
     *
     * @param integer $queueId  The queue id to obtain type ids for.
     *
     * @return array  An array of available typeIds for the specified queue.
     * @throws Whups_Exception
     */
    public function getTypeIds($queueId)
    {
        $query = 'SELECT type_id FROM whups_types_queues '
            . 'WHERE queue_id = ? ORDER BY type_id';
        $values = array($queueId);
        try {
            return $this->_db->selectValues($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Get list of ALL available types.
     *
     * @return array  A hash of type_id => type_name
     * @throws Whups_Exception
     */
    public function getAllTypes()
    {
        $query = 'SELECT type_id, type_name FROM whups_types ORDER BY type_name';
        try {
            $types = $this->_db->selectAssoc($query);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return Horde_String::convertCharset(
            $types, $this->_params['charset'], 'UTF-8');
    }

    /**
     *
     * @return array
     * @throws Whups_Exception
     */
    public function getAllTypeInfo()
    {
        $query = 'SELECT type_id, type_name, type_description '
            . 'FROM whups_types ORDER BY type_id';
        try {
            $info = $this->_db->selectAll($query);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return Horde_String::convertCharset(
            $info, $this->_params['charset'], 'UTF-8');
    }

    /**
     *
     * @param integer $type  The type_id
     *
     * @return string
     * @throws Whups_Exception
     */
    public function getTypeName($type)
    {
        $query = 'SELECT type_name FROM whups_types WHERE type_id = ?';
        $values = array($type);
        try {
            $name = $this->_db->selectValue($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return Horde_String::convertCharset(
            $name, $this->_params['charset'], 'UTF-8');
    }

    /**
     * Updates a type
     *
     * @param integer $typeId
     * @param string $name
     * @param string $description
     *
     * @throws Whups_Exception
     */
    public function updateType($typeId, $name, $description)
    {
        $query = 'UPDATE whups_types' .
                 ' SET type_name = ?, type_description = ? WHERE type_id = ?';
        $values = array(Horde_String::convertCharset($name, 'UTF-8',
                                                     $this->_params['charset']),
                        Horde_String::convertCharset($description, 'UTF-8',
                                                     $this->_params['charset']),
                        $typeId);
        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Delete a type from storage
     *
     * @param integer $typeId  The type_id to delete
     *
     * @throws Whups_Exception
     */
    public function deleteType($typeId)
    {
        $values = array((int)$typeId);
        try {
            $this->_db->delete(
                'DELETE FROM whups_states WHERE type_id = ?',
                $values);

            $this->_db->delete(
                'DELETE FROM whups_priorities WHERE type_id = ?',
                 $values);

            $this->_db->delete(
                'DELETE FROM whups_attributes_desc WHERE type_id = ?',
                $values);

            $this->_db->delete(
                'DELETE FROM whups_types WHERE type_id = ?',
                $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Fetch available states for given type/category
     *
     * @param string $type
     * @param string $category
     * @param string $notcategory
     *
     * @return array An array of states.
     * @throws Whups_Exception
     */
    public function getStates($type = null, $category = '', $notcategory = '')
    {
        $fields = 'state_id, state_name';
        $from = 'whups_states';
        $order = 'state_category, state_name';
        if (empty($type)) {
            $fields .= ', whups_types.type_id, type_name';
            $from .= ' LEFT JOIN whups_types ON whups_states.type_id = whups_types.type_id';
            $where = '';
            $order = 'type_name, ' . $order;
        } else {
            $where = 'type_id = ' . $type;
        }

        if (!is_array($category)) {
            $where = $this->_addWhere($where, $category, 'state_category = ' . $this->_db->quoteString($category));
        } else {
            $clauses = array();
            foreach ($category as $cat) {
                $clauses[] = 'state_category = ' . $this->_db->quoteString($cat);
            }
            if (count($clauses))
                $where = $this->_addWhere($where, $cat, implode(' OR ', $clauses));
        }

        if (!is_array($notcategory)) {
            $where = $this->_addWhere($where, $notcategory, 'state_category <> ' . $this->_db->quoteString($notcategory));
        } else {
            $clauses = array();
            foreach ($notcategory as $notcat) {
                $clauses[] = 'state_category <> ' . $this->_db->quoteString($notcat);
            }
            if (count($clauses)) {
                $where = $this->_addWhere($where, $notcat, implode(' OR ', $clauses));
            }
        }
        if (!empty($where)) {
            $where = ' WHERE ' . $where;
        }

        $query = "SELECT $fields FROM $from$where ORDER BY $order";
        try {
            $states = $this->_db->selectAll($query);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        $return = array();
        if (empty($type)) {
            foreach ($states as $state) {
                $return[$state['state_id']] = $state['state_name'] . ' (' . $state['type_name'] . ')';
            }
        } else {
            foreach ($states as $state) {
                $return[$state['state_id']] = $state['state_name'];
            }
        }

        return Horde_String::convertCharset(
            $return, $this->_params['charset'], 'UTF-8');
    }

    /**
     *
     * @param integer $stateId
     *
     * @return array  A state definition array.
     */
    public function getState($stateId)
    {
        if (empty($stateId)) {
            return false;
        }
        $query = 'SELECT state_name, state_description, '
            . 'state_category, type_id FROM whups_states WHERE state_id = ?';
        $values = array($stateId);
        try {
            $state[$stateId] = $this->_db->selectOne($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        $state = Horde_String::convertCharset(
            $state, $this->_params['charset'], 'UTF-8');
        return array(
            'id' => $stateId,
            'name' => isset($state[$stateId]['state_name']) ? $state[$stateId]['state_name'] : '',
            'description' => isset($state[$stateId]['state_description']) ? $state[$stateId]['state_description'] : '',
            'category' => isset($state[$stateId]['state_category']) ? $state[$stateId]['state_category'] : '',
            'type' => isset($state[$stateId]['type_id']) ? $state[$stateId]['type_id'] : '');
    }

    /**
     *
     * @param integer $type  The type_id
     *
     * @return array
     * @throws Whups_Exception
     */
    public function getAllStateInfo($type)
    {
        $query = 'SELECT state_id, state_name, state_description, '
            . 'state_category FROM whups_states WHERE type_id = ? '
            . 'ORDER BY state_id';
        $values = array($type);
        try {
            $info = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return Horde_String::convertCharset(
            $info, $this->_params['charset'], 'UTF-8');
    }

    /**
     *
     * @param integer $stateId     The state_id
     * @param string $name         The name
     * @param string $description  The description
     * @param string $category     The category
     *
     * @throws Whups_Exception
     */
    public function updateState($stateId, $name, $description, $category)
    {
        $query = 'UPDATE whups_states SET state_name = ?, '
            . 'state_description = ?, state_category = ? WHERE state_id = ?';
        $values = array(Horde_String::convertCharset($name, 'UTF-8',
                                                     $this->_params['charset']),
                        Horde_String::convertCharset($description, 'UTF-8',
                                                     $this->_params['charset']),
                        Horde_String::convertCharset($category, 'UTF-8',
                                                     $this->_params['charset']),
                        $stateId);
        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Get the default state for the specified ticket type.
     *
     * @param integer $type  The type_id
     *
     * @return integer  The default state_id for the specified type.
     * @throws Whups_Exception
     */
    public function getDefaultState($type)
    {
        $query = 'SELECT state_id FROM whups_states '
            . 'WHERE state_default = 1 AND type_id = ?';
        $values = array($type);
        try {
            return $this->_db->selectValue($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     *
     * @param integer $type   The type_id
     * @param integer $state  The state to set as default
     *
     * @throws Whups_Exception
     */
    public function setDefaultState($type, $state)
    {
        $query = 'UPDATE whups_states SET state_default = 0 WHERE type_id = ?';
        $values = array($type);
        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
        $query = 'UPDATE whups_states SET state_default = 1 WHERE state_id = ?';
        $values = array($state);
        try {
            $this->_db->query($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Deletes a state from storage.
     *
     * @param integer $state_id  The state id to delete
     *
     * @throws Whups_Exception
     */
    public function deleteState($state_id)
    {
        $query = 'DELETE FROM whups_states WHERE state_id = ?';
        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Retrieve query details.
     *
     * @param integer $queryId
     *
     * @return array
     * @throws Whups_Exception
     */
    public function getQuery($queryId)
    {
        if (empty($queryId)) {
            return false;
        }
        $query = 'SELECT query_parameters, query_object FROM whups_queries '
            . 'WHERE query_id = ?';
        $values = array((int)$queryId);
        try {
            $query = $this->_db->selectOne($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return Horde_String::convertCharset(
            $query, $this->_params['charset'], 'UTF-8');
    }

    /**
     * Save query details, inserting a new query row if necessary.
     *
     * @param Whups_Query $query
     */
    public function saveQuery($query)
    {
        try {
            $exists = $this->_db->selectValue(
                'SELECT 1 FROM whups_queries WHERE query_id = ?',
                array((int)$query->id));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        if ($exists) {
            $q = 'UPDATE whups_queries SET query_parameters = ?, '
                . 'query_object = ? WHERE query_id = ?';
            $values = array(
                serialize($query->parameters),
                serialize($query->query),
                $query->id);
        } else {
            $q = 'INSERT INTO whups_queries (query_id, query_parameters, '
                . 'query_object) VALUES (?, ?, ?)';
            $values = array(
                $query->id,
                serialize($query->parameters),
                serialize($query->query));
        }
        $values = Horde_String::convertCharset(
            $values, 'UTF-8', $this->_params['charset']);

        try {
            $this->_db->execute($q, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Delete query details.
     *
     * @param integer $queryId
     */
    public function deleteQuery($queryId)
    {
        $query = 'DELETE FROM whups_queries WHERE query_id = ?';
        $values = array((int)$queryId);
        try {
            return $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    public function isCategory($category, $state_id)
    {
        $query = 'SELECT 1 FROM whups_states '
            . 'WHERE state_id = ? AND state_category = ?';
        $values = array((int)$state_id, $category);
        try {
            return $this->_db->selectValue($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    public function getAllPriorityInfo($type)
    {
        $query = 'SELECT priority_id, priority_name, priority_description '
            . 'FROM whups_priorities WHERE type_id = ? ORDER BY priority_id';
        $values = array((int)$type);
        try {
            $info = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return Horde_String::convertCharset(
            $info, $this->_params['charset'], 'UTF-8');
    }

    public function getPriorities($type = null)
    {
        $fields = 'priority_id, priority_name';
        $from = 'whups_priorities';
        $order = 'priority_name';
        if (empty($type)) {
            $fields .= ', whups_types.type_id, type_name';
            $from .= ' LEFT JOIN whups_types ON whups_priorities.type_id = whups_types.type_id';
            $where = '';
            $order = 'type_name, ' . $order;
        } else {
            $where = ' WHERE type_id = ' . $type;
        }

        $query = "SELECT $fields FROM $from$where ORDER BY $order";
        try {
            $priorities = $this->_db->selectAll($query);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        $return = array();
        if (empty($type)) {
            foreach ($priorities as $priority) {
                $return[$priority['priority_id']] = $priority['priority_name'] . ' (' . $priority['type_name'] . ')';
            }
        } else {
            foreach ($priorities as $priority) {
                $return[$priority['priority_id']] = $priority['priority_name'];
            }
        }

        return Horde_String::convertCharset(
            $return, $this->_params['charset'], 'UTF-8');
    }

    public function getPriority($priorityId)
    {
        if (empty($priorityId)) {
            return false;
        }
        $query = 'SELECT priority_name, priority_description, '
            . 'type_id FROM whups_priorities WHERE priority_id = ?';
        $values = array((int)$priorityId);
        try {
            $row = $this->_db->selectOne($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
        foreach ($row as $key => $value) {
            $priority[$priorityId][$key] = $value;
        }
        $priority = Horde_String::convertCharset(
            $priority, $this->_params['charset'], 'UTF-8');

        return array('id' => $priorityId,
                     'name' => isset($priority[$priorityId]['priority_name'])
                         ? $priority[$priorityId]['priority_name'] : '',
                     'description' => isset($priority[$priorityId]['priority_description'])
                         ? $priority[$priorityId]['priority_description'] : '',
                     'type' => isset($priority[$priorityId]['type_id'])
                         ? $priority[$priorityId]['type_id'] : '');
    }

    public function updatePriority($priorityId, $name, $description)
    {
        $query = 'UPDATE whups_priorities' .
                 ' SET priority_name = ?, priority_description = ?' .
                 ' WHERE priority_id = ?';
        $values = array(Horde_String::convertCharset($name, 'UTF-8',
                                                     $this->_params['charset']),
                        Horde_String::convertCharset($description, 'UTF-8',
                                                     $this->_params['charset']),
                        $priorityId);
        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    public function getDefaultPriority($type)
    {
        $query = 'SELECT priority_id FROM whups_priorities '
            . 'WHERE priority_default = 1 AND type_id = ?';
        $values = array($type);
        try {
            return $this->_db->selectValue($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    public function setDefaultPriority($type, $priority)
    {
        $query = 'UPDATE whups_priorities SET priority_default = 0 '
            . 'WHERE type_id = ?';
        $values = array($type);
        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
        $query = 'UPDATE whups_priorities SET priority_default = 1 '
            . 'WHERE priority_id = ?';
        $values = array($priority);
        try {
           $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    public function deletePriority($priorityId)
    {
        $query = 'DELETE FROM whups_priorities WHERE priority_id = ?';
        $values = array($priorityId);
        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    public function getVersionInfoInternal($queue)
    {
        $query = 'SELECT version_id, version_name, version_description, version_active '
            . 'FROM whups_versions WHERE queue_id = ?'
            . ' ORDER BY version_id';
        $values = array($queue);
        try {
            $info = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
        return Horde_String::convertCharset(
            $info, $this->_params['charset'], 'UTF-8');
    }

    public function getVersionInternal($versionId)
    {
        if (empty($versionId)) {
            return false;
        }
        $query = 'SELECT version_id, version_name, version_description, '
            . 'version_active FROM whups_versions WHERE version_id = ?';
        $values = array($versionId);
        try {
            $version = $this->_db->selectAssoc($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        $version = Horde_String::convertCharset(
            $version, $this->_params['charset'], 'UTF-8');
        return array('id' => $versionId,
                     'name' => isset($version[$versionId][0])
                         ? $version[$versionId][0] : '',
                     'description' => isset($version[$versionId][1])
                         ? $version[$versionId][1] : '',
                     'active' => !empty($version[$versionId][2]));
    }

    public function updateVersion($versionId, $name, $description, $active)
    {
        $query = 'UPDATE whups_versions SET version_name = ?, '
            . 'version_description = ?, version_active = ? '
            . 'WHERE version_id = ?';
        $values = array(Horde_String::convertCharset($name, 'UTF-8',
                                                     $this->_params['charset']),
                        Horde_String::convertCharset($description, 'UTF-8',
                                                     $this->_params['charset']),
                        (int)$active,
                        (int)$versionId);
        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    public function deleteVersion($versionId)
    {
        $query = 'DELETE FROM whups_versions WHERE version_id = ?';
        $values = array($versionId);
        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Returns all available form replies for a ticket type.
     *
     * @param integer $type  A type id.
     *
     * @return array  A hash with reply ids as keys and reply hashes as values.
     */
    public function getReplies($type)
    {
        $query = 'SELECT reply_id, reply_name, reply_text '
            . 'FROM whups_replies WHERE type_id = ? ORDER BY reply_name';
        $values = array((int)$type);
        try {
            $info = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return Horde_String::convertCharset(
            $info, $this->_params['charset'], 'UTF-8');
    }

    /**
     * Returns a form reply information hash.
     *
     * @param integer $reply_id  A form reply id.
     *
     * @return array  A hash with all form reply information.
     */
    public function getReply($reply_id)
    {
        $query = 'SELECT reply_name, reply_text, type_id '
            . 'FROM whups_replies WHERE reply_id = ?';
        $values = array((int)$reply_id);
        try {
            $reply = $this->_db->selectOne($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return Horde_String::convertCharset(
            $reply, $this->_params['charset'], 'UTF-8');
    }

    /**
     * Updates a form reply in the backend.
     *
     * @param integer $reply  A reply id.
     * @param string $name    The new reply name.
     * @param string $text    The new reply text.
     *
     * @throws Whups_Exception
     */
    public function updateReply($reply, $name, $text)
    {
        $query = 'UPDATE whups_replies SET reply_name = ?, '
            . 'reply_text = ? WHERE reply_id = ?';
        $values = array(Horde_String::convertCharset($name, 'UTF-8',
                                                     $this->_params['charset']),
                        Horde_String::convertCharset($text, 'UTF-8',
                                                     $this->_params['charset']),
                        $reply);
        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Deletes a form reply from the backend.
     *
     * @param integer $reply  A reply id.
     */
    public function deleteReply($reply)
    {
        $query = 'DELETE FROM whups_replies WHERE reply_id = ?';
        $values = array((int)$reply);

        try {
            $this->_db->query($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        parent::deleteReply($reply);
    }

    public function addListener($ticket, $user)
    {
        $query = 'INSERT INTO whups_ticket_listeners (ticket_id, user_uid)' .
            ' VALUES (?, ?)';
        $values = array($ticket, $user);
        try {
            $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    public function deleteListener($ticket, $user)
    {
        $query = 'DELETE FROM whups_ticket_listeners WHERE ticket_id = ?' .
            ' AND user_uid = ?';
        $values = array($ticket, $user);
        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    public function getListeners(
        $ticket, $withowners = true, $withrequester = true,
        $withresponsible = false)
    {
        $query = 'SELECT DISTINCT l.user_uid' .
                 ' FROM whups_ticket_listeners l, whups_tickets t' .
                 ' WHERE (l.ticket_id = ?)';
        $values = array($ticket);
        try {
            $users = $this->_db->selectValues($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        $tinfo = $this->getTicketDetails($ticket);
        $requester = $tinfo['user_id_requester'];
        if ($withresponsible) {
            $users = array_merge($users, $this->getQueueUsers($tinfo['queue']));
        }

        // Tricky - handle case where owner = requester.
        $owner_is_requester = false;
        if (isset($tinfo['owners'])) {
            foreach ($tinfo['owners'] as $owner) {
                $owner = str_replace('user:', '', $owner);
                if ($owner == $requester) {
                    $owner_is_requester = true;
                }
                if ($withowners) {
                    $users[$owner] = $owner;
                } else {
                    if (isset($users[$owner])) {
                        unset($users[$owner]);
                    }
                }
            }
        }

        if (!$withrequester) {
            if (isset($users[$requester]) && (!$withowners || $owner_is_requester)) {
                unset($users[$requester]);
            }
        } elseif (!empty($requester)) {
            $users[$requester] = $requester;
        }

        return $users;
    }

    /**
     *
     * @return the new attribute id
     * @throws Whups_Exception
     */
    public function addAttributeDesc($type_id, $name, $desc, $type, $params, $required)
    {
        // TODO: Make sure we're not adding a duplicate here (can be
        // done in the db schema).

        // FIXME: This assumes that $type_id is a valid type id.
        $query = 'INSERT INTO whups_attributes_desc '
            . '(type_id, attribute_name, attribute_description, '
            . 'attribute_type, attribute_params, attribute_required)'
            . ' VALUES (?, ?, ?, ?, ?, ?)';
        $values = array(
            $type_id,
            Horde_String::convertCharset($name, 'UTF-8',
                                         $this->_params['charset']),
            Horde_String::convertCharset($desc, 'UTF-8',
                                         $this->_params['charset']),
            $type,
            serialize(
                Horde_String::convertCharset($params, 'UTF-8',
                                             $this->_params['charset'])),
            (int)($required == 'on'));

        try {
            return $this->_db->query($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    public function updateAttributeDesc(
        $attribute_id, $newname, $newdesc, $newtype, $newparams, $newrequired)
    {
        $query = 'UPDATE whups_attributes_desc '
            . 'SET attribute_name = ?, attribute_description = ?, '
            . 'attribute_type = ?, attribute_params = ?, '
            . 'attribute_required = ? WHERE attribute_id = ?';
        $values = array(
            Horde_String::convertCharset(
                $newname,
                'UTF-8',
                $this->_params['charset']),
            Horde_String::convertCharset(
                $newdesc,
                'UTF-8',
                $this->_params['charset']),
            $newtype,
            serialize(
                Horde_String::convertCharset(
                    $newparams,
                    'UTF-8',
                    $this->_params['charset'])),
            (int)($newrequired == 'on'),
            $attribute_id);

        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    public function deleteAttributeDesc($attribute_id)
    {
        $this->_db->beginDbTransaction();
        try {
            $this->_db->delete(
                'DELETE FROM whups_attributes_desc WHERE attribute_id = ?',
                 array($attribute_id));

            $this->_db->delete(
                'DELETE FROM whups_attributes WHERE attribute_id = ?',
                 array($attribute_id));
            $this->_db->commitDbTransaction();
        } catch (Horde_Db_Exception $e) {
            $this->_db->rollbackDbTransaction();
            throw new Whups_Exception($e);
        }
    }

    public function getAllAttributes()
    {
        $query = 'SELECT attribute_id, attribute_name, attribute_description, '
            . 'type_id FROM whups_attributes_desc';

        try {
            $attributes = $this->_db->selectAll($query);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
        return Horde_String::convertCharset(
            $attributes, $this->_params['charset'], 'UTF-8');
    }

    public function getAttributeDesc($attribute_id)
    {
        if (empty($attribute_id)) {
            return false;
        }

        $query = 'SELECT attribute_id, attribute_name, attribute_description, '
            . 'attribute_type, attribute_params, attribute_required '
            . 'FROM whups_attributes_desc WHERE attribute_id = ?';
        $values = array($attribute_id);
        try {
            $attribute = $this->_db->selectValues($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return array(
            'id' => $attribute_id,
            'attribute_name' => Horde_String::convertCharset(
                $attribute['attribute_name'], $this->_params['charset'], 'UTF-8'),
            'attribute_description' => Horde_String::convertCharset(
                $attribute['attribute_description'], $this->_params['charset'], 'UTF-8'),
            'attribute_type' => empty($attribute['attribute_type'])
                ? 'text' : $attribute['attribute_type'],
            'attribute_params' => Horde_String::convertCharset(
                @unserialize($attribute['attribute_params']),
                $this->_params['charset'], 'UTF-8'),
            'attribute_required' => (bool)$attribute['attribute_required']);
    }

    public function getAttributeName($attribute_id)
    {
        $query = 'SELECT attribute_name FROM whups_attributes_desc '
            . 'WHERE attribute_id = ?';
        $values = array($attribute_id);
        try {
            $name = $this->_db->selectValue($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
        return Horde_String::convertCharset(
            $name, $this->_params['charset'], 'UTF-8');
    }

    protected function _getAttributesForType($type = null, $raw = false)
    {
        $fields = 'attribute_id, attribute_name, attribute_description, '
            . 'attribute_type, attribute_params, attribute_required';
        $from = 'whups_attributes_desc';
        $order = 'attribute_name';
        if (empty($type)) {
            $fields .= ', whups_types.type_id, type_name';
            $from .= ' LEFT JOIN whups_types ON '
                . 'whups_attributes_desc.type_id = whups_types.type_id';
            $where = '';
            $order = 'type_name, ' . $order;
        } else {
            $where = ' WHERE type_id = ' . (int)$type;
        }

        $query = "SELECT $fields FROM $from$where ORDER BY $order";
        try {
            $attributes = $this->_db->selectAssoc($query);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
        foreach ($attributes as $id => $attribute) {
            if (empty($type) && !$raw) {
                $attributes[$id]['attribute_name'] =
                    $attribute['attribute_name']
                    . ' (' . $attribute['type_name'] . ')';
            }
            $attributes[$id]['attribute_name'] = Horde_String::convertCharset(
                $attribute['attribute_name'], $this->_params['charset'], 'UTF-8');
            $attributes[$id]['attribute_description'] = Horde_String::convertCharset(
                $attribute['attribute_description'], $this->_params['charset'], 'UTF-8');
            $attributes[$id]['attribute_type'] =
                empty($attribute['attribute_type'])
                ? 'text' : $attribute['attribute_type'];
            $attributes[$id]['attribute_params'] = Horde_String::convertCharset(
                @unserialize($attribute['attribute_params']),
                $this->_params['charset'], 'UTF-8');
            $attributes[$id]['attribute_required'] =
                (bool)$attribute['attribute_required'];
        }

        return $attributes;
    }

    public function getAttributeNamesForType($type_id)
    {
        if (empty($type_id)) {
            return array();
        }
        $query = 'SELECT attribute_name FROM whups_attributes_desc '
            .'WHERE type_id = ? ORDER BY attribute_name';
        $values = array((int)$type_id);
        try {
            $names = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return Horde_String::convertCharset(
            $names, $this->_params['charset'], 'UTF-8');
    }

    public function getAttributeInfoForType($type_id)
    {
        $query = 'SELECT attribute_id, attribute_name, attribute_description '
            . 'FROM whups_attributes_desc WHERE type_id = ? '
            . 'ORDER BY attribute_id';
        $values = array((int)$type_id);
        try {
            $info = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return Horde_String::convertCharset(
            $info, $this->_params['charset'], 'UTF-8');
    }

    protected function _setAttributeValue(
        $ticket_id, $attribute_id, $attribute_value)
    {
        $db_attribute_value = Horde_String::convertCharset(
            (string)$attribute_value, 'UTF-8', $this->_params['charset']);

        $this->_db->beginDbTransaction();
        try {
            $this->_db->delete(
                'DELETE FROM whups_attributes WHERE ticket_id = ? AND attribute_id = ?',
                 array($ticket_id, $attribute_id));

            if (!empty($attribute_value)) {
                $query = 'INSERT INTO whups_attributes'
                    . '(ticket_id, attribute_id, attribute_value)'
                    . ' VALUES (?, ?, ?)';
                $values = array($ticket_id, $attribute_id, $db_attribute_value);
                $inserted = $this->_db->insert(
                    'INSERT INTO whups_attributes (ticket_id, attribute_id, attribute_value) VALUES (?, ?, ?)',
                    $values);
            }
            $this->_db->commitDbTransaction();
        } catch (Horde_Db_Exception $e) {
            $this->_db->rollbackDbTransaction();
            throw new Whups_Exception($e);
        }
    }

    public function getTicketAttributes($ticket_id)
    {
        if (is_array($ticket_id)) {
            // No need to run a query for an empty array, and it would result
            // in an invalid SQL query anyway.
            if (!count($ticket_id)) {
                return array();
            }

            $query = 'SELECT ticket_id AS id, attribute_id, attribute_value '
                . 'FROM whups_attributes WHERE ticket_id IN ('
                . str_repeat('?, ', count($ticket_id) - 1) . '?)';

            try {
                $attributes = $this->_db->selectAll($query, $ticket_id);
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        } else {
            $query = 'SELECT attribute_id, attribute_value' .
                ' FROM whups_attributes WHERE ticket_id = ?';
            $values = array((int)$ticket_id);
            try {
                $attributes = $this->_db->selectAssoc($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }

        return Horde_String::convertCharset(
            $attributes, $this->_params['charset'], 'UTF-8');
    }

    public function getTicketAttributesWithNames($ticket_id)
    {
        if (is_array($ticket_id)) {
            // No need to run a query for an empty array, and it would result
            // in an invalid SQL query anyway.
            if (!count($ticket_id)) {
                return array();
            }

            $query = 'SELECT ticket_id AS id, d.attribute_name, '
                . 'a.attribute_id, a.attribute_value '
                . 'FROM whups_attributes a INNER JOIN whups_attributes_desc d '
                . 'ON (d.attribute_id = a.attribute_id)'
                . 'WHERE a.ticket_id IN ('
                . str_repeat('?, ', count($ticket_id) - 1) . '?)';

            try {
                $attributes = $this->_db->selectAll($query, $ticket_id);
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        } else {
            $query = 'SELECT d.attribute_name, a.attribute_value '
                . 'FROM whups_attributes a INNER JOIN whups_attributes_desc d '
                . 'ON (d.attribute_id = a.attribute_id)'
                . 'WHERE a.ticket_id = ? ORDER BY d.attribute_name';
            $values = array((int)$ticket_id);
            try {
                $attributes = $this->_db->selectAssoc($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }

        return Horde_String::convertCharset(
            $attributes, $this->_params['charset'], 'UTF-8');
    }

    protected function _getAllTicketAttributesWithNames($ticket_id)
    {
        $query = 'SELECT d.attribute_id, d.attribute_name, '
            . 'd.attribute_description, d.attribute_type, d.attribute_params, '
            . 'd.attribute_required, a.attribute_value '
            . 'FROM whups_attributes_desc d '
            . 'LEFT JOIN whups_tickets t ON (t.ticket_id = ?) '
            . 'LEFT OUTER JOIN whups_attributes a '
            . 'ON (d.attribute_id = a.attribute_id AND a.ticket_id = ?) '
            . 'WHERE d.type_id = t.type_id ORDER BY d.attribute_name';
        $values = array($ticket_id, $ticket_id);
        try {
            $attributes = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        foreach ($attributes as $id => $attribute) {
            $attributes[$id]['attribute_name'] = Horde_String::convertCharset(
                $attribute['attribute_name'], $this->_params['charset'], 'UTF-8');
            $attributes[$id]['attribute_description'] = Horde_String::convertCharset(
                $attribute['attribute_description'], $this->_params['charset'], 'UTF-8');
            $attributes[$id]['attribute_type'] =
                empty($attribute['attribute_type'])
                ? 'text' : $attribute['attribute_type'];
            $attributes[$id]['attribute_params'] = Horde_String::convertCharset(
                @unserialize($attribute['attribute_params']),
                $this->_params['charset'], 'UTF-8');
            $attributes[$id]['attribute_required'] =
                (bool)$attribute['attribute_required'];
        }

        return $attributes;
    }

    /**
     * Get all owners for the specified ticket.
     *
     * @param integer $ticketId  The ticket_id
     *
     * @return array  A id => owner hash
     */
    public function getOwners($ticketId)
    {
        if (is_array($ticketId)) {
            if (!count($ticketId)) {
                return array();
            }

            $query = 'SELECT ticket_id AS id, ticket_owner AS owner '
                . 'FROM whups_ticket_owners WHERE ticket_id '
                . 'IN (' . str_repeat('?, ', count($ticketId) - 1) . '?)';
            $values = $ticketId;
            try {
                return $this->_db->selectAssoc($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        } else {
            $query = 'SELECT ticket_id as id, ticket_owner as owner '
                . 'FROM whups_ticket_owners WHERE ticket_id = ?';
            $values = array((int)$ticketId);
            try {
                return $this->_db->selectAssoc($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }
    }

    /**
     * Add a new log entry
     *
     * @param integer $ticket_id      The ticket_id this log entry is for.
     * @param string $user            The user updating the ticket.
     * @param array $changes          An array of changes to make.
     * @param integer $transactionId  The transactionId to use.
     * @return type
     */
    public function updateLog(
        $ticket_id, $user, array $changes = array(), $transactionId = null)
    {
        if (is_null($transactionId)) {
            $transactionId = $this->newTransaction($user);
        }
        foreach ($changes as $type => $value) {
            $query = 'INSERT INTO whups_logs (transaction_id, '
                . 'ticket_id, log_type, log_value, '
                . 'log_value_num) VALUES (?, ?, ?, ?, ?)';
            $values = array(
                (int)$transactionId,
                (int)$ticket_id,
                $type,
                Horde_String::convertCharset((string)$value, 'UTF-8',
                                             $this->_params['charset']),
                (int)$value);
            try {
                $this->_db->insert($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }

        return $transactionId;
    }

    /**
     * Create a new transaction id for associating related entries in
     * the whups_transaction table.
     *
     * @return integer New transaction id.
     */
    public function newTransaction($creator, $creator_email = null)
    {
        $insert = 'INSERT INTO whups_transactions (transaction_timestamp, transaction_user_id)'
            . ' VALUES(?, ?)';

        if ((empty($creator) || $creator < 0) && !empty($creator_email)) {
            $creator = '-' . $transactionId . '_transaction';
            $query = 'INSERT INTO whups_guests (guest_id, guest_email)'
                . ' VALUES (?, ?)';
            $values = array((string)$creator, $creator_email);
            try {
                $result = $this->_db->insert($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }

        try {
            return $this->_db->insert($insert, array(time(), $creator));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    // /**
    //  * Return the db object we're using.
    //  *
    //  * return DB Database object.
    //  */
    // public function getDb()
    // {
    //     return $this->_db;
    // }

    /**
     * @TODO: get rid of this method, use injector factory.
     */
    function initialise()
    {
        $this->_db = $GLOBALS['injector']->getInstance('Horde_Db_Adapter');
        return true;
    }

    protected function _generateWhere($table, $fields, &$info, $type)
    {
        $where = '';
        $this->_mapFields($info);

        foreach ($fields as $field) {
            if (isset($info[$field])) {
                $prop = $info[$field];
                if (is_array($info[$field])) {
                    $clauses = array();
                    foreach ($prop as $pprop) {
                        if (@settype($pprop, $type)) {
                            $clauses[] = "$table.$field = " . $this->_db->quoteString($pprop);
                        }
                    }
                    if (count($clauses)) {
                        $where = $this->_addWhere($where, true, implode(' OR ', $clauses));
                    }
                } else {
                    $success = @settype($prop, $type);
                    $where = $this->_addWhere($where, !is_null($prop) && $success, "$table.$field = " . $this->_db->quoteString($prop));
                }
            }
        }

        foreach ($fields as $field) {
            if (isset($info["not$field"])) {
                $prop = $info["not$field"];

                if (strpos($prop, ',') === false) {
                    $success = @settype($prop, $type);
                    $where = $this->_addWhere($where, $prop && $success, "$table.$field <> " . $this->_db->quoteString($prop));
                } else {
                    $set = explode(',', $prop);

                    foreach ($set as $prop) {
                        $success = @settype($prop, $type);
                        $where = $this->_addWhere($where, $prop && $success, "$table.$field <> " . $this->_db->quoteString($prop));
                    }
                }
            }
        }

        return $where;
    }

    protected function _mapFields(&$info)
    {
        foreach ($info as $key => $val) {
            if ($key === 'id') {
                $info['ticket_id'] = $info['id'];
                unset($info['id']);
            } elseif ($key === 'state' ||
                      $key === 'type' ||
                      $key === 'queue' ||
                      $key === 'priority') {
                $info[$key . '_id'] = $info[$key];
                unset($info[$key]);
            } elseif ($key === 'requester') {
                $info['user_id_' . $key] = $info[$key];
                unset($info[$key]);
            }
        }
    }

    protected function _addWhere($where, $condition, $clause, $conjunction = 'AND')
    {
        if (!empty($condition)) {
            if (!empty($where)) {
                $where .= " $conjunction ";
            }

            $where .= "($clause)";
        }

        return $where;
    }

    protected function _addDateWhere($where, $data, $type)
    {
        if (is_array($data)) {
            if (!empty($data['from'])) {
                $where = $this->_addWhere($where, true,
                                          $type . ' >= ' . (int)$data['from']);
            }
            if (!empty($data['to'])) {
                $where = $this->_addWhere($where, true,
                                          $type . ' <= ' . (int)$data['to']);
            }
            return $where;
        }

        return $this->_addWhere($where, true, $type . ' = ' . (int)$data);
    }

    protected function _prefixTableToColumns($table, $columns)
    {
        $join = "";

        $clause = '';
        foreach ($columns as $column) {
            $clause .= "$join$table.$column";
            $join = ', ';
        }

        return $clause;
    }

}
