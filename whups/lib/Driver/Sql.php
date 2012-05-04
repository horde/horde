<?php
/**
 * Whups backend driver for the Horde_Db abstraction layer.
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
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
    protected $_guestEmailCache = array();

    /**
     * Local cache of internal queue hashes
     *
     * @var array
     */
    protected $_internalQueueCache = array();

    /**
     * Local queues internal cache
     *
     * @var array
     */
    protected $_queues = null;

    /**
     * Local slug cache
     *
     * @var array
     */
     protected $_slugs = null;

     public function setStorage($storage)
     {
        if (!($storage instanceof Horde_Db_Adapter_Base)) {
            throw new InvalidArgumentException("Missing Horde_Db_Adapter_Base");
        }
        $this->_db = $storage;
     }

    /**
     * Adds a new queue.
     *
     * @params string $name         A queue name.
     * @params string $description  A queue description.
     * @params string $slug         A queue slug.
     * @params string $email        A queue email address.
     *
     * @return integer  The new queue ID.
     * @throws Whups_Exception
     */
    public function addQueue($name, $description, $slug = '', $email = '')
    {
        // Check for slug uniqueness.
        if (strlen($slug) &&
            $this->_db->selectValue('SELECT 1 FROM whups_queues WHERE queue_slug = ?', array($slug))) {
            throw new Whups_Exception(
                _("That queue slug is already taken. Please select another."));
        }

        try {
            return $this->_db->insert(
                'INSERT INTO whups_queues (queue_name, queue_description, '
                    . 'queue_slug, queue_email) VALUES (?, ?, ?, ?)',
                array($this->_toBackend($name),
                      $this->_toBackend($description),
                      $slug,
                      $email));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Adds a new ticket type.
     *
     * @param string $name         A type name.
     * @param string $description  A type description.
     *
     * @return integer  The new type ID.
     * @throws Whups_Exception
     */
    public function addType($name, $description)
    {
        try {
            return $this->_db->insert(
                'INSERT INTO whups_types (type_name, type_description) '
                    . 'VALUES (?, ?)',
                array($this->_toBackend($name),
                      $this->_toBackend($description)));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Adds a new state to a ticket type.
     *
     * @param integer $typeId      A ticket type ID.
     * @param string $name         A state name
     * @param string $description  A state description.
     * @param string $category     A state category.
     *
     * @return integer  The new state ID.
     * @throws Whups_Exception
     */
    public function addState($typeId, $name, $description, $category)
    {
        try {
            return $this->_db->insert(
                'INSERT INTO whups_states (type_id, state_name, '
                    . 'state_description, state_category) VALUES (?, ?, ?, ?)',
                array((int)$typeId,
                      $this->_toBackend($name),
                      $this->_toBackend($description),
                      $this->_toBackend($category)));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Adds a new priority to a ticket type.
     *
     * @param integer $typeId      A ticket type ID.
     * @param string $name         A priority name.
     * @param string $description  A priority description.
     *
     * @return integer  The new priority ID.
     * @throws Whups_Exception
     */
    public function addPriority($typeId, $name, $description)
    {
        try {
            return $this->_db->insert(
                'INSERT INTO whups_priorities (type_id, priority_name, '
                    . 'priority_description) VALUES (?, ?, ?)',
                array((int)$typeId,
                      $this->_toBackend($name),
                      $this->_toBackend($description)));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Adds a new version to a queue.
     *
     * @param integer $queueId     A queue ID.
     * @param string $name         A version name.
     * @param string $description  A version description.
     * @param boolean $active      Whether the version is active.
     *
     * @return integer  The new version ID.
     * @throws Whups_Exception
     */
    public function addVersion($queueId, $name, $description, $active)
    {
        try {
            return $this->_db->insert(
                'INSERT INTO whups_versions (queue_id, version_name, '
                    . 'version_description, version_active) '
                    . 'VALUES (?, ?, ?, ?)',
                array((int)$queueId,
                      $this->_toBackend($name),
                      $this->_toBackend($description),
                      (boolean)$active));
        } catch (Horde_Db_Exception $e) {
            throw Whups_Exception($e);
        }
    }

    /**
     * Adds a form reply.
     *
     * @param integer $type  A ticket type ID.
     * @param string $name   A reply name.
     * @param string $text   A reply text.
     *
     * @return integer  The new form reply ID.
     * @throws Whups_Exception
     */
    public function addReply($type, $name, $text)
    {
        try {
            return $this->_db->insert(
                'INSERT INTO whups_replies (type_id, reply_name, reply_text) '
                    . 'VALUES (?, ?, ?)',
                array((int)$type,
                      $this->_toBackend($name),
                      $this->_toBackend($text)));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }


    /**
     * Adds a ticket.
     *
     * @param array $info        A ticket info hash. Will get a
     *                           'last-transaction' value added.
     * @param string $requester  A ticket requester.
     *
     * @return integer  The new ticket ID.
     * @throws Whups_Exception
     */
    public function addTicket(array &$info, $requester)
    {
        $type       = (int)$info['type'];
        $state      = (int)$info['state'];
        $priority   = (int)$info['priority'];
        $queue      = (int)$info['queue'];
        $summary    = $info['summary'];
        $version    = (int)isset($info['version']) ? $info['version'] : null;
        $due        = isset($info['due']) ? $info['due'] : null;
        $comment    = $info['comment'];
        $attributes = isset($info['attributes']) ? $info['attributes'] : array();

        // Create the ticket.
        try {
            $ticket_id = $this->_db->insert(
                'INSERT INTO whups_tickets (ticket_summary, '
                    . 'user_id_requester, type_id, state_id, priority_id, '
                    . 'queue_id, ticket_timestamp, ticket_due, version_id)'
                    . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                array($this->_toBackend($summary),
                      $requester,
                      $type,
                      $state,
                      $priority,
                      $queue,
                      time(),
                      $due,
                      $version));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        // Is there a more effecient way to do this? Need the ticketId before
        // we can insert this.
        if (!empty($info['user_email'])) {
            $requester = $ticket_id * -1;
            try {
                $this->_db->update(
                    'UPDATE whups_tickets SET user_id_requester = ? WHERE '
                        . 'ticket_id = ?',
                    array($requester, $ticket_id));
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }

        if ($requester < 0) {
            try {
                $this->_db->insert(
                    'INSERT INTO whups_guests (guest_id, guest_email) '
                        . 'VALUES (?, ?)',
                    array((string)$requester, $info['user_email']));
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }

        $commentId = $this->addComment(
            $ticket_id, $comment, $requester,
            isset($info['user_email']) ? $info['user_email'] : null);

        // If permissions were specified, set them.
        if (!empty($info['group'])) {
            Whups_Ticket::addCommentPerms($commentId, $info['group']);
        }

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
            $attribute_value = $this->_serializeAttribute($attribute_value);
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
     * Adds a new ticket comment.
     *
     * @param integer $ticket_id     A ticket ID.
     * @param string $comment        A comment text.
     * @param string $creator        The creator of the comment.
     * @param string $creator_email  The creator's email address.
     *
     * @return integer  The new comment ID.
     * @throws Whups_Exception
     */
    public function addComment($ticket_id, $comment, $creator,
                               $creator_email = null)
    {
        // Add the row.
        try {
            $id = $this->_db->insert(
                'INSERT INTO whups_comments (ticket_id, user_id_creator, '
                    . ' comment_text, comment_timestamp) VALUES (?, ?, ?, ?)',
                array((int)$ticket_id,
                      $creator,
                      $this->_toBackend($comment),
                      time()));

            if (empty($creator) || $creator < 0) {
                $creator = '-' . $id . '_comment';
            }
            $this->_db->update(
                'UPDATE whups_comments SET user_id_creator = ?'
                . ' WHERE comment_id = ?',
                array($creator, $id));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        // Hacky. $creator is a string at this point, but it can still evaluate
        // to a negative integer.
        if ($creator < 0 && !empty($creator_email)) {
            try {
                $this->_db->insert(
                    'INSERT INTO whups_guests (guest_id, guest_email)'
                        . ' VALUES (?, ?)',
                    array((string)$creator, $creator_email));
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }

        return $id;
    }

    /**
     * Updates a ticket.
     *
     * Does not update the ticket log (so that it can be used for things
     * low-level enough to not show up there. In general, you should *always*
     * update the log; Whups_Ticket::commit() will take care of this in most
     * cases).
     *
     * @param integer $ticketId  A ticket ID.
     * @param array $attributes  An attribute hash.
     *
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
            $values[] = $this->_toBackend($value);
        }

        // Don't try to execute an empty query (if we didn't find any updates
        // to make).
        if (empty($query)) {
            return;
        }

        $values[] = (int)$ticketId;

        try {
            $this->_db->update(
                'UPDATE whups_tickets SET ' . substr($query, 0, -2)
                    . ' WHERE ticket_id = ?',
                $values);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Adds a ticket owner.
     *
     * @param integer $ticketId  A ticket ID.
     * @param string $owner      An owner ID.
     *
     * @throws Whups_Exception
     */
    public function addTicketOwner($ticketId, $owner)
    {
        try {
            $this->_db->insert(
                'INSERT INTO whups_ticket_owners (ticket_id, ticket_owner) '
                    . 'VALUES (?, ?)',
                array($ticketId, $owner));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Removes a ticket owner.
     *
     * @param integer $ticketId  A ticket ID.
     * @param string $owner      An owner ID.
     *
     * @throws Whups_Exception
     */
    public function deleteTicketOwner($ticketId, $owner)
    {
        try {
            $this->_db->delete(
                'DELETE FROM whups_ticket_owners WHERE ticket_owner = ? '
                    . 'AND ticket_id = ?',
                array($owner, (int)$ticketId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Removes a ticket.
     *
     * @param integer $id  A ticket ID.
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
        try {
            $txs = $this->_db->selectValues(
                'SELECT DISTINCT transaction_id FROM whups_logs '
                    . 'WHERE ticket_id = ?',
                array($id));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        $this->_db->beginDbTransaction();
        foreach ($tables as $table) {
            try {
                $this->_db->delete(
                    'DELETE FROM ' . $table . ' WHERE ticket_id = ?',
                    array($id));
            } catch (Horde_Db_Exception $e) {
                $this->_db->rollbackDbTransaction();
                throw new Whups_Exception($e);
            }
        }

        if (!empty($txs)) {
            try {
                $this->_db->delete(
                    'DELETE FROM whups_transactions WHERE transaction_id IN '
                        . '(' . str_repeat('?,', count($txs) - 1) . '?)',
                    $txs);
            } catch (Horde_Db_Exception $e) {
                $this->_db->rollbackDbTransaction();
                throw new Whups_Exception($e);
            }
        }

        $this->_db->commitDbTransaction();
    }

    /**
     * Executes a query.
     *
     * @param Whups_Query $query     A query object.
     * @param Horde_Variables $vars  Request variables.
     * @param boolean $get_details   Whether to return all ticket details.
     * @param boolean $munge         @TODO (?)
     *
     * @return array  List of ticket IDs or ticket details that match the query
     *                criteria.
     * @throws Whups_Exception
     */
    public function executeQuery(Whups_Query $query, Horde_Variables $vars,
                                 $get_details = true, $munge = true)
    {
        $this->jtables = array();
        $this->joins   = array();

        $where = $query->reduce(array($this, 'clauseFromQuery'), $vars);
        if (!$where) {
            $GLOBALS['notification']->push(_("No query to run"), 'horde.message');
            return array();
        }

        if ($this->joins) {
            $joins = implode(' ', $this->joins);
        } else {
            $joins = '';
        }

        try {
            $ids = $this->_db->selectValues(
                "SELECT whups_tickets.ticket_id FROM whups_tickets $joins "
                . "WHERE $where");
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

    public function clauseFromQuery($args, $type, $criterion, $cvalue,
                                        $operator, $value)
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
            throw new InvalidArgumentException();
        }

        return 'NOT (' . $args[0] . ')';
    }

    /**
     * @todo: The RDBMS specific clauses should be refactored to use
     * Horde_Db_Adapter_Base_Schema#buildClause
     *
     * @return string
     */
    protected function _criterionClause($criterion, $cvalue, $operator, $value)
    {
        $func    = '';
        $funcend = '';
        $value = $this->_toBackend($value);

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
     * Returns tickets by searching for its properties.
     *
     * @param array $info        An array of properties to search for.
     * @param boolean $munge     Munge the query (?)
     * @param boolean $perowner  Group the results per owner?
     *
     * @return array  An array of ticket information hashes.
     * @throws Whups_Exception
     */
    public function getTicketsByProperties(array $info, $munge = true,
                                           $perowner = false)
    {
        if (isset($info['queue']) && !count($info['queue'])) {
            return array();
        }

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

        $info = $this->_fromBackend($info);

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
                $ticket['requester_formatted'] = Whups::formatUser($ticket['user_id_requester'], false, true, true);
            }
            $tickets[$ticket['id']] = $ticket;
        }

        $owners = $this->getOwners(array_keys($tickets));
        foreach ($owners as $id => $owners) {
            $tickets[$id]['owners'] = $owners;
            foreach($owners as $owner) {
                $tickets[$id]['owners_formatted'][] = Whups::formatUser($owner, false, true, true);
            }
        }
        $attributes = $this->getTicketAttributesWithNames(array_keys($tickets));
        foreach ($attributes as $row) {
            $attribute_id = 'attribute_' . $row['attribute_id'];
            try {
                $tickets[$row['id']][$attribute_id] =
                    Horde_Serialize::unserialize($row['attribute_value'],
                                                 Horde_Serialize::JSON);
            } catch (Horde_Serialize_Exception $e) {
                $tickets[$row['id']][$attribute_id] = $row['attribute_value'];
            }
            $tickets[$row['id']][$attribute_id . '_name'] = $row['attribute_name'];
        }

        return array_values($tickets);
    }

    /**
     * Returns ticket details.
     *
     * @param integer $ticket      A ticket ID.
     * @param boolean $checkPerms  Enforce permissions?
     *
     * @return array  A ticket information hash.
     * @throws Horde_Exception_NotFound
     * @throws Horde_Exception_PermissionDenied
     */
    public function getTicketDetails($ticket, $checkPerms = true)
    {
        $result = $this->getTicketsByProperties(array('id' => $ticket));

        if (!isset($result[0])) {
            throw new Horde_Exception_NotFound(
                sprintf(_("Ticket %s was not found."), $ticket));
        }

        $queues = Whups::permissionsFilter(
            $this->getQueues(), 'queue', Horde_Perms::READ,
            $GLOBALS['registry']->getAuth(), $result[0]['user_id_requester']);

        if ($checkPerms &&
            !in_array($result[0]['queue'], array_flip($queues))) {
            throw new Horde_Exception_PermissionDenied(
                sprintf(_("You do not have permission to access this ticket (%s)."), $ticket));
        }

        return $result[0];
    }

    /**
     * Returns a ticket state.
     *
     * @param integer $ticket_id  A ticket ID.
     *
     * @return integer  A state ID.
     * @throws Whups_Exception
     */
    public function getTicketState($ticket_id)
    {
        try {
            return $this->_db->SelectOne(
                'SELECT whups_tickets.state_id, whups_states.state_category '
                    . 'FROM whups_tickets INNER JOIN whups_states '
                    . 'ON whups_tickets.state_id = whups_states.state_id '
                    . 'WHERE ticket_id = ?',
                array($ticket_id));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Returns a guest's email address.
     *
     * @param string $guest_id  A guest ID.
     *
     * @return string  The guest's email address.
     * @throws Whups_Exception
     */
    public function getGuestEmail($guest_id)
    {
        if (!isset($this->_guestEmailCache[$guest_id])) {
            try {
                $result = $this->_db->selectValue(
                    'SELECT guest_email FROM whups_guests WHERE guest_id = ?',
                    array($guest_id));
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
            $this->_guestEmailCache[$guest_id] = $this->_fromBackend($result);
        }

        return $this->_guestEmailCache[$guest_id];
    }


    /**
     * Returns a ticket's history.
     *
     * @param integer $ticket_id  A ticket ID.
     *
     * @return array  The ticket's history.
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

        $history = $this->_fromBackend($history);
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
     * @param integer $transaction  A transaction ID.
     * @throws Whups_Exception
     */
    public function deleteHistory($transaction)
    {
        $transaction = (int)$transaction;
        $this->_db->beginDbTransaction();

        /* Deleting comments. */
        try {
            $comments = $this->_db->selectValues(
                'SELECT log_value FROM whups_logs WHERE log_type = ? '
                    . 'AND transaction_id = ?',
                array('comment', $transaction));
        } catch (Horde_Db_Exception $e) {
            $this->_db->rollbackDbTransaction();
            throw new Whups_Exception($e);
        }

        if ($comments) {
            $query = sprintf(
                'DELETE FROM whups_comments WHERE comment_id IN (%s)',
                implode(',', $comments));
            try {
                $this->_db->delete($query);
            } catch (Horde_Db_Exception $e) {
                $this->_db->rollbackDbTransaction();
                throw new Whups_Exception($e);
            }
        }

        /* Deleting attachments. */
        if (isset($GLOBALS['conf']['vfs']['type'])) {
            try {
                $attachments = $this->_db->selectAll(
                    'SELECT ticket_id, log_value FROM whups_logs '
                        . 'WHERE log_type = ? AND transaction_id = ?',
                    array('attachment', $transaction));
            } catch (Horde_Db_Exception $e) {
                $this->_db->rollbackDbTransaction();
                throw new Whups_Exception($e);
            }

            $vfs = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Vfs')
                ->create();
            foreach ($attachments as $attachment) {
                $dir = Whups::VFS_ATTACH_PATH . '/' . $attachment['ticket_id'];
                if ($vfs->exists($dir, $attachment['log_value'])) {
                    try {
                        $vfs->deleteFile($dir, $attachment['log_value']);
                    } catch (Horde_Vfs_Exception $e) {
                        $this->_db->rollbackDbTransaction();
                        throw new Whups_Exception($e);
                    }
                } else {
                    Horde::logMessage(sprintf(_("Attachment %s not found."),
                                              $attachment['log_value']),
                                      'WARN');
                }
            }
        }

        try {
            $this->_db->delete(
                'DELETE FROM whups_logs WHERE transaction_id = ?',
                array($transaction));
            $this->_db->delete(
                'DELETE FROM whups_transactions WHERE transaction_id = ?',
                array($transaction));
        } catch (Horde_Db_Exception $e) {
            $this->_db->rollbackDbTransaction();
            throw new Whups_Exception($e);
        }

        $this->_db->commitDbTransaction();
    }

    /**
     * Return a list of queues and the number of open tickets in each.
     *
     * @param array $queues  Array of queue IDs to summarize.
     *
     * @return array  A list of queue hashes.
     * @throws Whups_Exception
     */
    public function getQueueSummary($queue_ids)
    {
        $qstring = implode(', ', array_map('intval', $queue_ids));

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

        return $this->_fromBackend($queues);
    }

    /**
     * Returns a queue information hash.
     *
     * @param integer $queueId  A queue ID.
     *
     * @return array  A queue hash.
     * @throws Whups_Exception
     */
    public function getQueueInternal($queueId)
    {
        if (isset($this->_internalQueueCache[$queueId])) {
            return $this->_internalQueueCache[$queueId];
        }

        try {
            $queue = $this->_db->selectOne(
                'SELECT queue_id, queue_name, queue_description, '
                    . 'queue_versioned, queue_slug, queue_email '
                    . 'FROM whups_queues WHERE queue_id = ?',
                array((int)$queueId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        if (!$queue) {
            return array();
        }

        $queue = $this->_fromBackend($queue);
        $this->_internalQueueCache[$queueId] = array(
            'id'          => (int)$queue['queue_id'],
            'name'        => $queue['queue_name'],
            'description' => $queue['queue_description'],
            'versioned'   => (bool)$queue['queue_versioned'],
            'slug'        => $queue['queue_slug'],
            'email'       => $queue['queue_email'],
            'readonly'    => false);

        return $this->_internalQueueCache[$queueId];
    }


    /**
     * Returns a queue information hash.
     *
     * @param string $slug  A queue slug.
     *
     * @return array  A queue hash.
     * @throws Whups_Exception
     */
    public function getQueueBySlugInternal($slug)
    {
        try {
            $queue = $this->_db->selectOne(
                'SELECT queue_id, queue_name, queue_description, '
                    . 'queue_versioned, queue_slug FROM whups_queues WHERE '
                    . 'queue_slug = ?',
                array((string)$slug));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        if (!$queue) {
            return array();
        }

        $queue = $this->_fromBackend($queue);
        return array(
            'id'          => $queue['queue_id'],
            'name'        => $queue['queue_name'],
            'description' => $queue['queue_description'],
            'versioned'   => $queue['queue_versioned'],
            'slug'        => $queue['queue_slug'],
            'readonly'    => false);
    }

    /**
     * Returns a list of available queues.
     *
     * @return array  An hash of queue ID => queue name.
     * @throws Whups_Exception
     */
    public function getQueuesInternal()
    {
        if (is_null($this->_queues)) {
            try {
                $queues = $this->_db->selectAssoc(
                    'SELECT queue_id, queue_name FROM whups_queues '
                        . 'ORDER BY queue_name');
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
            $this->_queues = $this->_fromBackend($queues);
        }

        return $this->_queues;
    }

    /**
     * Returns a list of all available slugs.
     *
     * @return array  A hash of queue ID => queue slug.
     * @throws Whups_Exception
     */
    public function getSlugs()
    {
        if (is_null($this->_slugs)) {
            try {
                $queues = $this->_db->selectAssoc(
                    'SELECT queue_id, queue_slug FROM whups_queues '
                        . 'WHERE queue_slug IS NOT NULL AND queue_slug <> \'\' '
                        . 'ORDER BY queue_slug');
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
            $this->_slugs = $this->_fromBackend($queues);
        }

        return $this->_slugs;
    }

    /**
     * Updates a queue.
     *
     * @param integer $queueId     A queue ID.
     * @param string $name         A queue name.
     * @param string $description  A queue description.
     * @param array $types         A list of type IDs for this queue.
     * @param integer $versioned   Is this queue versioned? (1 = true,
     *                             0 = false)
     * @param string $slug         A queue slug.
     * @param string $email        A queue email address.
     * @param integer $default     The default ticket type.
     *
     * @throws Whups_Exception
     */
    public function updateQueue($queueId, $name, $description,
                                array $types = array(), $versioned = 0,
                                $slug = '', $email = '', $default = null)
    {
        global $registry;

        if ($registry->hasMethod('tickets/listQueues') == $registry->getApp()) {
            // Is slug unique?
            if (!empty($slug)) {
                if ($this->_db->selectValue('SELECT 1 FROM whups_queues WHERE queue_slug = ? AND queue_id <> ?', array($slug, $queueId))) {
                    throw new Whups_Exception(
                        _("That queue slug is already taken. Please select another."));
                }
            }

            // First update the queue entry itself.
            try {
                $this->_db->update(
                    'UPDATE whups_queues SET queue_name = ?, '
                        . 'queue_description = ?, queue_versioned = ?, '
                        . 'queue_slug = ?, queue_email = ? WHERE queue_id = ?',
                    array($this->_toBackend($name),
                          $this->_toBackend($description),
                          empty($versioned) ? 0 : 1,
                          $slug,
                          $email,
                          (int)$queueId));
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }

        // Clear all previous type-queue associations.
        try {
            $this->_db->delete(
                'DELETE FROM whups_types_queues WHERE queue_id = ?',
                array((int)$queueId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        // Add the new associations.
        if (is_array($types)) {
            foreach ($types as $typeId) {
                try {
                    $this->_db->insert(
                        'INSERT INTO whups_types_queues '
                        . '(queue_id, type_id, type_default) VALUES (?, ?, ?)',
                        array((int)$queueId,
                              (int)$typeId,
                              $default == $typeId ? 1 : 0));
                } catch (Horde_Db_Exception $e) {
                    throw new Whups_Exception($e);
                }
            }
        }
    }

    /**
     * Returns a queue's default ticket type.
     *
     * @param integer $queue  A queue ID.
     *
     * @return integer  A ticket type ID.
     */
    public function getDefaultType($queue)
    {
        try {
            return $this->_db->selectValue(
                'SELECT type_id FROM whups_types_queues '
                    . 'WHERE type_default = 1 AND queue_id = ?',
                array($queue));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Deletes an entire queue, and all references to it.
     *
     * @param integer $queueId  A queue ID.
     *
     * @throws Whups_Exception
     */
    public function deleteQueue($queueId)
    {
        // Clean up the tickets associated with the queue.
        try {
            $result = $this->_db->selectAll(
                'SELECT ticket_id FROM whups_tickets WHERE queue_id = ?',
                array((int)$queueId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
        foreach ($result as $ticket) {
            $this->deleteTicket($ticket['ticket_id']);
        }

        // Now remove all references to the queue itself.
        $tables = array(
            'whups_queues_users',
            'whups_types_queues',
            'whups_versions',
            'whups_queues');
        $this->_db->beginDbTransaction();
        foreach ($tables as $table) {
            try {
                $this->_db->delete(
                    'DELETE FROM ' . $table . ' WHERE queue_id = ?',
                    array((int)$queueId));
            } catch (Horde_Db_Exception $e) {
                $this->_db->rollbackDbTransaction();
                throw new Whups_Exception($e);
            }
        }
        $this->_db->commitDbTransaction();

        return parent::deleteQueue($queueId);
    }

    /**
     * Update type-queue-associations.
     *
     * @param array  An array of mappings.
     *
     * @throws Whups_Exception
     */
    public function updateTypesQueues(array $tmPairs)
    {
        // Do this as a transaction.
        $this->_db->beginDbTransaction();

        // Delete existing associations.
        try {
            $this->_db->delete('DELETE FROM whups_types_queues');
        } catch (Horde_Db_Exception $e) {
            $this->_db->rollbackDbTransaction();
            throw new Whups_Exception($e);
        }

        // Insert new associations.
        foreach ($tmPairs as $pair) {
            try {
                $this->_db->insert(
                    'INSERT INTO whups_types_queues (queue_id, type_id) '
                        . 'VALUES (?, ?)',
                    array((int)$pair[0], (int)$pair[1]));
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
     * Returns a list of responsible users.
     *
     * @param integer $queueId  A queue ID.
     *
     * @return array  An array of users responsible for the queue.
     * @throws Whups_Execption
     */
    public function getQueueUsers($queueId)
    {
        try {
            return $this->_db->selectValues(
                'SELECT user_uid FROM whups_queues_users'
                    . ' WHERE queue_id = ? ORDER BY user_uid',
                array((int)$queueId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Adds a responsible user.
     *
     * @param integer $queueId  A queue ID.
     * @param string  $userId   A user ID.
     *
     * @throws Whups_Exception
     */
    public function addQueueUser($queueId, $userId)
    {
        if (!is_array($userId)) {
            $userId = array($userId);
        }
        foreach ($userId as $user) {
            try {
                $this->_db->insert(
                    'INSERT INTO whups_queues_users (queue_id, user_uid) '
                        . 'VALUES (?, ?)',
                    array((int)$queueId, $user));
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }
    }

    /**
     * Removes a responsible user.
     *
     * @param integer $queueId  A queue ID.
     * @param string $userId    A user ID.
     *
     * @throws Whups_Exception
     */
    public function removeQueueUser($queueId, $userId)
    {
        try {
            $this->_db->delete(
                'DELETE FROM whups_queues_users'
                    . ' WHERE queue_id = ? AND user_uid = ?',
                array((int)$queueId, $userId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Returns a ticket type.
     *
     * @param integer $typeId  A ticket type ID.
     *
     * @return array  The ticket type information.
     * @throws Whups_Exception
     */
    public function getType($typeId)
    {
        try {
            $type = $this->_db->selectOne(
                'SELECT type_id, type_name, type_description '
                    . 'FROM whups_types WHERE type_id = ?',
                array((int)$typeId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        $type = $this->_fromBackend($type);

        return array('id'          => $typeId,
                     'name'        => $type['type_name'],
                     'description' => $type['type_description']);
    }

    /**
     * Returns a list of ticket types associated with a queue.
     *
     * @param integer $queueId  A queue ID.
     *
     * @return array  A hash of type ID => type name.
     * @throws Whups_Exception
     */
    public function getTypes($queueId)
    {
        try {
            $types = $this->_db->selectAssoc(
                'SELECT t.type_id, t.type_name '
                    . 'FROM whups_types t, whups_types_queues tm '
                    . 'WHERE tm.queue_id = ? AND tm.type_id = t.type_id '
                    . 'ORDER BY t.type_name',
                array((int)$queueId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return $this->_fromBackend($types);
    }

    /**
     * Returns a list of ticket type IDs associated with a queue.
     *
     * @param integer $queueId  A queue ID.
     *
     * @return array  A list of type IDs.
     * @throws Whups_Exception
     */
    public function getTypeIds($queueId)
    {
        try {
            return $this->_db->selectValues(
                'SELECT type_id FROM whups_types_queues '
                    . 'WHERE queue_id = ? ORDER BY type_id',
                array((int)$queueId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Returns a list of all available ticket types.
     *
     * @return array  A hash of type ID => type name.
     * @throws Whups_Exception
     */
    public function getAllTypes()
    {
        try {
            $types = $this->_db->selectAssoc(
                'SELECT type_id, type_name FROM whups_types ORDER BY type_name');
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return $this->_fromBackend($types);
    }

    /**
     * Returns a list of all available ticket types.
     *
     * @return array  A list of ticket type information hashes.
     * @throws Whups_Exception
     */
    public function getAllTypeInfo()
    {
        try {
            $info = $this->_db->selectAll(
                'SELECT type_id, type_name, type_description '
                . 'FROM whups_types ORDER BY type_id');
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return $this->_fromBackend($info);
    }

    /**
     * Returns a ticket type name.
     *
     * @param integer $type  A type ID.
     *
     * @return string  The ticket type's name.
     * @throws Whups_Exception
     */
    public function getTypeName($type)
    {
        try {
            $name = $this->_db->selectValue(
                'SELECT type_name FROM whups_types WHERE type_id = ?',
                array((int)$type));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return $this->_fromBackend($name);
    }

    /**
     * Updates a ticket type.
     *
     * @param integer $typeId      A ticket type ID.
     * @param string $name         A ticket type name.
     * @param string $description  A ticket type description.
     *
     * @throws Whups_Exception
     */
    public function updateType($typeId, $name, $description)
    {
        try {
            $this->_db->update(
                'UPDATE whups_types SET type_name = ?, type_description = ? '
                    . 'WHERE type_id = ?',
                array($this->_toBackend($name),
                      $this->_toBackend($description),
                      (int)$typeId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Deletes a ticket type.
     *
     * @param integer $typeId  A type ID.
     *
     * @throws Whups_Exception
     */
    public function deleteType($typeId)
    {
        $this->_db->beginDbTransaction();
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
            $this->_db->commitDbTransaction();
        } catch (Horde_Db_Exception $e) {
            $this->_db->rollbackDbTransaction();
            throw new Whups_Exception($e);
        }
    }

    /**
     * Returns available states for a ticket type and state category.
     *
     * @param integer $type              A ticket type ID.
     * @param string|array $category     State categories to include.
     * @param string|array $notcategory  State categories to not include.
     *
     * @return array  A hash of state ID => state name.
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

        return $this->_fromBackend($return);
    }

    /**
     * Returns a state information hash.
     *
     * @param integer $stateId  A state ID.
     *
     * @return array  A state definition hash.
     */
    public function getState($stateId)
    {
        try {
            $state = $this->_db->selectOne(
                'SELECT state_name, state_description, state_category, '
                    . 'type_id FROM whups_states WHERE state_id = ?',
                array((int)$stateId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        $state = $this->_fromBackend($state);

        return array('id'          => $stateId,
                     'name'        => $state['state_name'],
                     'description' => $state['state_description'],
                     'category'    => $state['state_category'],
                     'type'        => $state['type_id']);
    }

    /**
     * Returns all state information hashes for a ticket type.
     *
     * @param integer $type  A ticket type ID.
     *
     * @return array  A list of state hashes.
     * @throws Whups_Exception
     */
    public function getAllStateInfo($type)
    {
        try {
            $info = $this->_db->selectAll(
                'SELECT state_id, state_name, state_description, '
                    . 'state_category FROM whups_states WHERE type_id = ? '
                    . 'ORDER BY state_id',
                array((int)$type));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return $this->_fromBackend($info);
    }

    /**
     * Updates a state.
     *
     * @param integer $stateId     A state ID.
     * @param string $name         A state name.
     * @param string $description  A state description.
     * @param string $category     A state category.
     *
     * @throws Whups_Exception
     */
    public function updateState($stateId, $name, $description, $category)
    {
        try {
            $this->_db->update(
                'UPDATE whups_states SET state_name = ?, state_description = ?, '
                    . 'state_category = ? WHERE state_id = ?',
                array($this->_toBackend($name),
                      $this->_toBackend($description),
                      $this->_toBackend($category),
                      (int)$stateId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Returns the default state for a ticket type.
     *
     * @param integer $type  A type ID.
     *
     * @return integer  The default state ID for the specified type.
     * @throws Whups_Exception
     */
    public function getDefaultState($type)
    {
        try {
            return $this->_db->selectValue(
                'SELECT state_id FROM whups_states WHERE state_default = 1 '
                    . 'AND type_id = ?',
                array($type));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Sets the default state for a ticket type.
     *
     * @param integer $type   A type ID.
     * @param integer $state  A state ID.
     *
     * @throws Whups_Exception
     */
    public function setDefaultState($type, $state)
    {
        $this->_db->beginDbTransaction();
        try {
            $this->_db->update(
                'UPDATE whups_states SET state_default = 0 WHERE type_id = ?',
                array((int)$type));
            $this->_db->update(
                'UPDATE whups_states SET state_default = 1 WHERE state_id = ?',
                array((int)$state));
            $this->_db->commitDbTransaction();
        } catch (Horde_Db_Exception $e) {
            $this->_db->rollbackDbTransaction();
            throw new Whups_Exception($e);
        }
    }

    /**
     * Deletes a state.
     *
     * @param integer $state_id  A state ID.
     *
     * @throws Whups_Exception
     */
    public function deleteState($state_id)
    {
        try {
            $this->_db->delete('DELETE FROM whups_states WHERE state_id = ?',
                               array((int)$state_id));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Returns query details.
     *
     * @param integer $queryId  A query ID.
     *
     * @return array  Query information.
     * @throws Whups_Exception
     */
    public function getQuery($queryId)
    {
        try {
            $query = $this->_db->selectOne(
                'SELECT query_parameters, query_object FROM whups_queries '
                    . 'WHERE query_id = ?',
                array((int)$queryId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return $this->_fromBackend($query);
    }

    /**
     * Saves query details.
     *
     * If the query doesn't exist yes, it is added, update otherwise.
     *
     * @param Whups_Query $query  A query.
     *
     * @throws Whups_Exception
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

        $values = $this->_toBackend(array(serialize($query->parameters),
                                          serialize($query->query),
                                          $query->id));

        try {
            if ($exists) {
                $this->_db->update(
                    'UPDATE whups_queries SET query_parameters = ?, '
                        . 'query_object = ? WHERE query_id = ?',
                    $values);
            } else {
                $this->_db->insert(
                    'INSERT INTO whups_queries (query_parameters, '
                        . 'query_object, query_id) VALUES (?, ?, ?)',
                    $values);
            }
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Deletes a query.
     *
     * @param integer $queryId  A query ID.
     *
     * @throws Whups_Exception
     */
    public function deleteQuery($queryId)
    {
        try {
            $this->_db->delete('DELETE FROM whups_queries WHERE query_id = ?',
                               array((int)$queryId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Returns whether a state is of a certain category.
     *
     * @param string $category   A state category.
     * @param integer $state_id  A state ID.
     *
     * @return boolean  True if the state is of the given category.
     * @throws Whups_Exception
     */
    public function isCategory($category, $state_id)
    {
        try {
            return (bool)$this->_db->selectValue(
                'SELECT 1 FROM whups_states '
                    . 'WHERE state_id = ? AND state_category = ?',
                array((int)$state_id, $category));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Returns all priority information hashes for a ticket type.
     *
     * @param integer $type  A ticket type ID.
     *
     * @return array  A list of priority hashes.
     * @throws Whups_Exception
     */
    public function getAllPriorityInfo($type)
    {
        try {
            $info = $this->_db->selectAll(
                'SELECT priority_id, priority_name, priority_description '
                    . 'FROM whups_priorities WHERE type_id = ? '
                    . 'ORDER BY priority_id',
                array((int)$type));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return $this->_fromBackend($info);
    }

    /**
     * Returns a list of priorities.
     *
     * If the priorities are not limited to a ticket type, the priority names
     * are suffixed with associated ticket type names.
     *
     * @param integer $type  Limit result to this ticket type's priorities.
     *
     * @return array  A hash of priority ID => priority name.
     * @throws Whups_Exception
     */
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

        return $this->_fromBackend($return);
    }

    /**
     * Returns a priority information hash.
     *
     * @param integer $priorityId  A state ID.
     *
     * @return array  A priority definition hash.
     * @throws Whups_Exception
     */
    public function getPriority($priorityId)
    {
        try {
            $priority = $this->_db->selectOne(
                'SELECT priority_name, priority_description, type_id '
                    . 'FROM whups_priorities WHERE priority_id = ?',
                array((int)$priorityId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        $priority = $this->_fromBackend($priority);

        return array('id'          => $priorityId,
                     'name'        => $priority['priority_name'],
                     'description' => $priority['priority_description'],
                     'type'        => $priority['type_id']);
    }

    /**
     * Updates a priority.
     *
     * @param integer $priorityId  A priority ID.
     * @param string $name         A priority name.
     * @param string $description  A priority description.
     *
     * @throws Whups_Exception
     */
    public function updatePriority($priorityId, $name, $description)
    {
        try {
            $this->_db->update(
                'UPDATE whups_priorities SET priority_name = ?, '
                    . 'priority_description = ? WHERE priority_id = ?',
                array($this->_toBackend($name),
                      $this->_toBackend($description),
                      (int)$priorityId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Returns the default priority for a ticket type.
     *
     * @param integer $type  A type ID.
     *
     * @return integer  The default priority ID for the specified type.
     * @throws Whups_Exception
     */
    public function getDefaultPriority($type)
    {
        try {
            return $this->_db->selectValue(
                'SELECT priority_id FROM whups_priorities '
                    . 'WHERE priority_default = 1 AND type_id = ?',
                array((int)$type));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Sets the default priority for a ticket type.
     *
     * @param integer $type      A type ID.
     * @param integer $priority  A priority ID.
     *
     * @throws Whups_Exception
     */
    public function setDefaultPriority($type, $priority)
    {
        $this->_db->beginDbTransaction();
        try {
            $this->_db->update(
                'UPDATE whups_priorities SET priority_default = 0 '
                    . 'WHERE type_id = ?',
                array((int)$type));
            $this->_db->update(
                'UPDATE whups_priorities SET priority_default = 1 '
                    . 'WHERE priority_id = ?',
                array((int)$priority));
            $this->_db->commitDbTransaction();
        } catch (Horde_Db_Exception $e) {
            $this->_db->rollbackDbTransaction();
            throw new Whups_Exception($e);
        }
    }

    /**
     * Deletes a priority.
     *
     * @param integer $priorityId  A priority ID.
     *
     * @throws Whups_Exception
     */
    public function deletePriority($priorityId)
    {
        try {
            $this->_db->delete(
                'DELETE FROM whups_priorities WHERE priority_id = ?',
                array((int)$priorityId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Returns all versions of a queue.
     *
     * @param integer $queue  A queue ID.
     *
     * @return array  A list of version information hashes.
     * @throws Whups_Exception
     */
    public function getVersionInfoInternal($queue)
    {
        try {
            $info = $this->_db->selectAll(
                'SELECT version_id, version_name, version_description, '
                . 'version_active FROM whups_versions WHERE queue_id = ?'
                . ' ORDER BY version_id',
                array((int)$queue));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return $this->_fromBackend($info);
    }

    /**
     * Returns a version information hash.
     *
     * @param integer $versionId  A state ID.
     *
     * @return array  A version definition hash.
     * @throws Whups_Exception
     */
    public function getVersionInternal($versionId)
    {
        try {
            $version = $this->_db->selectOne(
                'SELECT version_name, version_description, version_active '
                    . 'FROM whups_versions WHERE version_id = ?',
                array((int)$versionId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        $version = $this->_fromBackend($version);

        return array('id'          => $versionId,
                     'name'        => $version['version_name'],
                     'description' => $version['version_description'],
                     'active'      => !empty($version['version_active']));
    }

    /**
     * Updates a version.
     *
     * @param integer $versionId   A version ID.
     * @param string $name         A version name.
     * @param string $description  A version description.
     * @param boolean $active      Whether the version is still active.
     *
     * @throws Whups_Exception
     */
    public function updateVersion($versionId, $name, $description, $active)
    {
        try {
            $this->_db->update(
                'UPDATE whups_versions SET version_name = ?, '
                    . 'version_description = ?, version_active = ? '
                    . 'WHERE version_id = ?',
                array($this->_toBackend($name),
                      $this->_toBackend($description),
                      (int)$active,
                      (int)$versionId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Deletes a version.
     *
     * @param integer $versionId  A version ID.
     *
     * @throws Whups_Exception
     */
    public function deleteVersion($versionId)
    {
        try {
            $this->_db->delete(
                'DELETE FROM whups_versions WHERE version_id = ?',
                array((int)$versionId));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Returns all available form replies for a ticket type.
     *
     * @param integer $type  A type ID.
     *
     * @return array  A hash of reply ID => reply information hash.
     * @throws Whups_Exception
     */
    public function getReplies($type)
    {
        try {
            $rows = $this->_db->selectAll(
                'SELECT reply_id, reply_name, reply_text '
                    . 'FROM whups_replies WHERE type_id = ? ORDER BY reply_name',
                array((int)$type));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        $info = array();
        foreach ($rows as $row) {
            $info[$row['reply_id']] = $this->_fromBackend($row);
        }

        return $info;
    }

    /**
     * Returns a form reply.
     *
     * @param integer $reply_id  A form reply ID.
     *
     * @return array  A hash with all form reply information.
     * @throws Whups_Exception
     */
    public function getReply($reply_id)
    {
        try {
            $reply = $this->_db->selectOne(
                'SELECT reply_name, reply_text, type_id '
                    . 'FROM whups_replies WHERE reply_id = ?',
                array((int)$reply_id));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return $this->_fromBackend($reply);
    }

    /**
     * Updates a form reply.
     *
     * @param integer $reply  A reply ID.
     * @param string $name    A reply name.
     * @param string $text    A reply text.
     *
     * @throws Whups_Exception
     */
    public function updateReply($reply, $name, $text)
    {
        try {
            $this->_db->update(
                'UPDATE whups_replies SET reply_name = ?, reply_text = ? '
                    . 'WHERE reply_id = ?',
                array($this->_toBackend($name),
                      $this->_toBackend($text),
                      (int)$reply));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Deletes a form reply.
     *
     * @param integer $reply  A reply ID.
     *
     * @throws Whups_Exception
     */
    public function deleteReply($reply)
    {
        try {
            $this->_db->delete('DELETE FROM whups_replies WHERE reply_id = ?',
                               array((int)$reply));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        parent::deleteReply($reply);
    }

    /**
     * Adds a ticket listener.
     *
     * @param integer $ticket  A ticket ID.
     * @param string $user     An email address.
     *
     * @throws Whups_Exception
     */
    public function addListener($ticket, $user)
    {
        try {
            $this->_db->insert(
                'INSERT INTO whups_ticket_listeners (ticket_id, user_uid)'
                    . ' VALUES (?, ?)',
                array((int)$ticket, $user));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Deletes a ticket listener.
     *
     * @param integer $ticket  A ticket ID.
     * @param string $user     An email address.
     *
     * @throws Whups_Exception
     */
    public function deleteListener($ticket, $user)
    {
        try {
            $this->_db->delete(
                'DELETE FROM whups_ticket_listeners WHERE ticket_id = ?'
                    . ' AND user_uid = ?',
                array((int)$ticket, $user));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Returns all ticket listeners.
     *
     * @param integer $ticket           A ticket ID.
     * @param boolean $withowners       Include ticket owners?
     * @param boolean $withrequester    Include ticket creators?
     * @param boolean $withresponsible  Include users responsible for the ticket
     *                                  queue?
     *
     * @return array  A list of all ticket listeners.
     * @throws Whups_Exception
     */
    public function getListeners($ticket, $withowners = true,
                                 $withrequester = true,
                                 $withresponsible = false)
    {
        try {
            $listeners = $this->_db->selectValues(
                'SELECT DISTINCT l.user_uid FROM whups_ticket_listeners l, '
                    . 'whups_tickets t WHERE (l.ticket_id = ?)',
                array((int)$ticket));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
        $users = array();
        foreach ($listeners as $user) {
            $users[$user] = 'listener';
        }

        $tinfo = $this->getTicketDetails($ticket);
        if ($withresponsible) {
            foreach ($this->getQueueUsers($tinfo['queue']) as $user) {
                $users[$user] = 'queue';
            }
        }

        // Tricky - handle case where owner = requester.
        $requester = $tinfo['user_id_requester'];
        $owner_is_requester = false;
        if (isset($tinfo['owners'])) {
            foreach ($tinfo['owners'] as $owner) {
                $owner = str_replace('user:', '', $owner);
                if ($owner == $requester) {
                    $owner_is_requester = true;
                }
                if ($withowners) {
                    $users[$owner] = 'owner';
                } else {
                    if (isset($users[$owner])) {
                        unset($users[$owner]);
                    }
                }
            }
        }

        if (!$withrequester) {
            if (isset($users[$requester]) &&
                (!$withowners || $owner_is_requester)) {
                unset($users[$requester]);
            }
        } elseif (!empty($requester) && !isset($users[$requester])) {
            $users[$requester] = 'requester';
        }

        return $users;
    }

    /**
     * Adds an attribute to a ticket type.
     *
     * @todo Make sure we're not adding a duplicate here (can be done in the db
     *       schema).
     * @todo This assumes that $type_id is a valid type id.
     *
     * @param integer $type_id   A ticket type ID.
     * @param string $name       An attribute name.
     * @param string $desc       An attribute description.
     * @param string $type       A form field type.
     * @param array $params      Additional parameters for the field type.
     * @param boolean $required  Whether the attribute is mandatory.
     *
     * @return integer  The new attribute ID.
     * @throws Whups_Exception
     */
    public function addAttributeDesc($type_id, $name, $desc, $type, $params,
                                     $required)
    {
        try {
            return $this->_db->insert(
                'INSERT INTO whups_attributes_desc '
                    . '(type_id, attribute_name, attribute_description, '
                    . 'attribute_type, attribute_params, attribute_required)'
                    . ' VALUES (?, ?, ?, ?, ?, ?)',
                array((int)$type_id,
                      $this->_toBackend($name),
                      $this->_toBackend($desc),
                      $type,
                      serialize($this->_toBackend($params)),
                      (int)($required == 'on')));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Updates an attribute for a ticket type.
     *
     * @param integer $attribute_id  An attribute ID.
     * @param string $newname        An attribute name.
     * @param string $newdesc        An attribute description.
     * @param string $newtype        A form field type.
     * @param array $newparams       Additional parameters for the field type.
     * @param boolean $newrequired   Whether the attribute is mandatory.
     *
     * @throws Whups_Exception
     */
    public function updateAttributeDesc($attribute_id, $newname, $newdesc,
                                        $newtype, $newparams, $newrequired)
    {
        try {
            $this->_db->update(
                'UPDATE whups_attributes_desc '
                    . 'SET attribute_name = ?, attribute_description = ?, '
                    . 'attribute_type = ?, attribute_params = ?, '
                    . 'attribute_required = ? WHERE attribute_id = ?',
                array($this->_toBackend($newname),
                      $this->_toBackend($newdesc),
                      $newtype,
                      serialize($this->_toBackend($newparams)),
                      (int)($newrequired == 'on'),
                      (int)$attribute_id));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
    }

    /**
     * Deletes an attribute for a ticket type.
     *
     * @param integer $attribute_id  An attribute ID.
     *
     * @throws Whups_Exception
     */
    public function deleteAttributeDesc($attribute_id)
    {
        $this->_db->beginDbTransaction();
        try {
            $this->_db->delete(
                'DELETE FROM whups_attributes_desc WHERE attribute_id = ?',
                array((int)$attribute_id));
            $this->_db->delete(
                'DELETE FROM whups_attributes WHERE attribute_id = ?',
                array((int)$attribute_id));
            $this->_db->commitDbTransaction();
        } catch (Horde_Db_Exception $e) {
            $this->_db->rollbackDbTransaction();
            throw new Whups_Exception($e);
        }
    }

    /**
     * Returns all attributes.
     *
     * @return array  A list of attributes hashes.
     * @throws Whups_Exception
     */
    public function getAllAttributes()
    {
        try {
            $attributes = $this->_db->selectAll(
                'SELECT attribute_id, attribute_name, attribute_description, '
                    . 'type_id FROM whups_attributes_desc');
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
        return $this->_fromBackend($attributes);
    }

    /**
     * Returns an attribute information hash.
     *
     * @param integer $attribute_id  An attribute ID.
     *
     * @return array  The attribute hash.
     * @throws Whups_Exception
     */
    public function getAttributeDesc($attribute_id)
    {
        try {
            $attribute = $this->_db->selectOne(
                'SELECT attribute_name, attribute_description, '
                    . 'attribute_type, attribute_params, attribute_required '
                    . 'FROM whups_attributes_desc WHERE attribute_id = ?',
                array((int)$attribute_id));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return array(
            'id' => $attribute_id,
            'name' => $this->_fromBackend($attribute['attribute_name']),
            'description' => $this->_fromBackend($attribute['attribute_description']),
            'type' => empty($attribute['attribute_type'])
                ? 'text'
                : $attribute['attribute_type'],
            'params' => $this->_fromBackend(@unserialize($attribute['attribute_params'])),
            'required' => (bool)$attribute['attribute_required']);
    }

    /**
     * Returns an attribute name.
     *
     * @param integer $attribute_id  An attribute ID.
     *
     * @return string  The attribute name.
     * @throws Whups_Exception
     */
    public function getAttributeName($attribute_id)
    {
        try {
            $name = $this->_db->selectValue(
                'SELECT attribute_name FROM whups_attributes_desc '
                    . 'WHERE attribute_id = ?',
                array((int)$attribute_id));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
        return $this->_fromBackend($name);
    }

    /**
     * Returns the attributes for a ticket type.
     *
     * @params integer $type  A ticket type ID.
     *
     * @return array  A list of attribute ID => attribute information hashes.
     */
    protected function _getAttributesForType($type = null)
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
            $attributes = $this->_db->selectAll($query);
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }
        $results = array();
        foreach ($attributes as $attribute) {
            $id = $attribute['attribute_id'];
            $results[$id] = $attribute;
            if (empty($type)) {
                $results[$id]['attribute_name'] =
                    $attribute['attribute_name']
                    . ' (' . $attribute['type_name'] . ')';
            }
            $results[$id]['attribute_name']        =
                $this->_fromBackend($attribute['attribute_name']);
            $results[$id]['attribute_description'] =
                $this->_fromBackend($attribute['attribute_description']);
            $results[$id]['attribute_type']        =
                empty($attribute['attribute_type'])
                    ? 'text'
                    : $attribute['attribute_type'];
            $results[$id]['attribute_params']      =
                $this->_fromBackend(@unserialize($attribute['attribute_params']));
            $results[$id]['attribute_required']    =
                (bool)$attribute['attribute_required'];
        }

        return $results;
    }

    /**
     * Returns available attribute names for a ticket type.
     *
     * @param integer $type_id  A ticket type ID.
     *
     * @return array  A list of attribute names.
     * @throws Whups_Exception
     */
    public function getAttributeNamesForType($type_id)
    {
        try {
            $names = $this->_db->selectAll(
                'SELECT attribute_name FROM whups_attributes_desc '
                    . 'WHERE type_id = ? ORDER BY attribute_name',
                array((int)$type_id));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return $this->_fromBackend($names);
    }

    /**
     * Returns available attributes for a ticket type.
     *
     * @param integer $type_id  A ticket type ID.
     *
     * @return array  A list of attribute information hashes.
     * @throws Whups_Exception
     */
    public function getAttributeInfoForType($type_id)
    {
        try {
            $info = $this->_db->selectAll(
                'SELECT attribute_id, attribute_name, attribute_description '
                    . 'FROM whups_attributes_desc WHERE type_id = ? '
                    . 'ORDER BY attribute_id',
                array((int)$type_id));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        return $this->_fromBackend($info);
    }

    /**
     * Save an attribute value.
     *
     * @param integer $ticket_id       A ticket ID.
     * @param integer $attribute_id    An attribute ID.
     * @param string $attribute_value  An attribute value.
     *
     * @throws Whups_Exception
     */
    protected function _setAttributeValue($ticket_id, $attribute_id,
                                          $attribute_value)
    {
        $db_attribute_value = $this->_toBackend($attribute_value);

        $this->_db->beginDbTransaction();
        try {
            $this->_db->delete(
                'DELETE FROM whups_attributes WHERE ticket_id = ? '
                    . 'AND attribute_id = ?',
                 array($ticket_id, $attribute_id));

            if (strlen($attribute_value)) {
                $this->_db->insert(
                    'INSERT INTO whups_attributes (ticket_id, attribute_id, attribute_value) VALUES (?, ?, ?)',
                    array($ticket_id, $attribute_id, $db_attribute_value));
            }
            $this->_db->commitDbTransaction();
        } catch (Horde_Db_Exception $e) {
            $this->_db->rollbackDbTransaction();
            throw new Whups_Exception($e);
        }
    }

    /**
     * Returns the attribute values of a ticket.
     *
     * @param integer|array $ticket_id  One or more ticket IDs.
     *
     * @return array  If requesting a single ticket, an attribute ID =>
     *                attribute value hash. If requesting multiple tickets, a
     *                list of hashes with ticket ID, attribute ID and attribute
     *                value.
     * @throws Whups_Exception
     */
    public function getTicketAttributes($ticket_id)
    {
        if (is_array($ticket_id)) {
            // No need to run a query for an empty array, and it would result
            // in an invalid SQL query anyway.
            if (!count($ticket_id)) {
                return array();
            }

            try {
                $attributes = $this->_db->selectAll(
                    'SELECT ticket_id AS id, attribute_id, attribute_value '
                        . 'FROM whups_attributes WHERE ticket_id IN ('
                        . str_repeat('?, ', count($ticket_id) - 1) . '?)',
                    $ticket_id);
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        } else {
            try {
                $attributes = $this->_db->selectAssoc(
                    'SELECT attribute_id, attribute_value'
                        . ' FROM whups_attributes WHERE ticket_id = ?',
                    array((int)$ticket_id));
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }

        $attributes = $this->_fromBackend($attributes);
        foreach ($attributes as &$attribute) {
            try {
                $attribute = Horde_Serialize::unserialize(
                    $attribute,
                    Horde_Serialize::JSON);
            } catch (Horde_Serialize_Exception $e) {
            }
        }

        return $attributes;
    }

    /**
     * Returns the attribute values and names of a ticket.
     *
     * @param integer|array $ticket_id  One or more ticket IDs.
     *
     * @return array  If requesting a single ticket, an attribute name =>
     *                attribute value hash. If requesting multiple tickets, a
     *                list of hashes with ticket ID, attribute ID, attribute
     *                name, and attribute value.
     * @throws Whups_Exception
     */
    public function getTicketAttributesWithNames($ticket_id)
    {
        if (is_array($ticket_id)) {
            // No need to run a query for an empty array, and it would result
            // in an invalid SQL query anyway.
            if (!count($ticket_id)) {
                return array();
            }

            try {
                $attributes = $this->_db->selectAll(
                    'SELECT ticket_id AS id, d.attribute_name, '
                        . 'a.attribute_id, a.attribute_value '
                        . 'FROM whups_attributes a INNER JOIN '
                        . 'whups_attributes_desc d '
                        . 'ON (d.attribute_id = a.attribute_id)'
                        . 'WHERE a.ticket_id IN ('
                        . str_repeat('?, ', count($ticket_id) - 1) . '?)',
                    $ticket_id);
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        } else {
            try {
                $attributes = $this->_db->selectAssoc(
                    'SELECT d.attribute_name, a.attribute_value '
                        . 'FROM whups_attributes a INNER JOIN '
                        . 'whups_attributes_desc d '
                        . 'ON (d.attribute_id = a.attribute_id)'
                        . 'WHERE a.ticket_id = ? ORDER BY d.attribute_name',
                    array((int)$ticket_id));
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }

        $attributes = $this->_fromBackend($attributes);
        foreach ($attributes as &$attribute) {
            try {
                $attribute = Horde_Serialize::unserialize(
                    $attribute,
                    Horde_Serialize::JSON);
            } catch (Horde_Serialize_Exception $e) {
            }
        }

        return $attributes;
    }

    /**
     * Returns attribute values and information of a ticket.
     *
     * @param integer $ticket_id  A ticket IDs.
     *
     * @return array  A list of hashes with attribute information and attribute
     *                value.
     * @throws Whups_Exception
     */
    protected function _getAllTicketAttributesWithNames($ticket_id)
    {
        try {
            $attributes = $this->_db->selectAll(
                'SELECT d.attribute_id, d.attribute_name, '
                    . 'd.attribute_description, d.attribute_type, '
                    . 'd.attribute_params, d.attribute_required, '
                    . 'a.attribute_value FROM whups_attributes_desc d '
                    . 'LEFT JOIN whups_tickets t ON (t.ticket_id = ?) '
                    . 'LEFT OUTER JOIN whups_attributes a '
                    . 'ON (d.attribute_id = a.attribute_id AND a.ticket_id = ?) '
                    . 'WHERE d.type_id = t.type_id ORDER BY d.attribute_name',
                array($ticket_id, $ticket_id));
        } catch (Horde_Db_Exception $e) {
            throw new Whups_Exception($e);
        }

        foreach ($attributes as &$attribute) {
            $attribute['attribute_name'] =
                $this->_fromBackend($attribute['attribute_name']);
            $attribute['attribute_description'] =
                $this->_fromBackend($attribute['attribute_description']);
            $attribute['attribute_type'] =
                empty($attribute['attribute_type'])
                    ? 'text'
                    : $attribute['attribute_type'];
            $attribute['attribute_params'] =
                $this->_fromBackend(@unserialize($attribute['attribute_params']));
            $attribute['attribute_required'] =
                (bool)$attribute['attribute_required'];
            try {
                $attribute['attribute_value'] = Horde_Serialize::unserialize(
                    $attribute['attribute_value'],
                    Horde_Serialize::JSON);
            } catch (Horde_Serialize_Exception $e) {
            }
        }

        return $attributes;
    }

    /**
     * Returns the owners of a ticket.
     *
     * @param mixed integer|array $ticketId  One or more ticket IDs.
     *
     * @return array  An hash of ticket ID => owner IDs
     * @throws Whups_Exception
     */
    public function getOwners($ticketId)
    {
        if (is_array($ticketId)) {
            if (!count($ticketId)) {
                return array();
            }

            try {
                $owners = $this->_db->selectAll(
                    'SELECT ticket_id AS id, ticket_owner AS owner '
                        . 'FROM whups_ticket_owners WHERE ticket_id IN '
                        . '(' . str_repeat('?, ', count($ticketId) - 1) . '?)',
                    $ticketId);
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        } else {
            try {
                $owners = $this->_db->selectAll(
                    'SELECT ticket_id as id, ticket_owner as owner '
                        . 'FROM whups_ticket_owners WHERE ticket_id = ?',
                    array((int)$ticketId));
            } catch (Horde_Db_Exception $e) {
                throw new Whups_Exception($e);
            }
        }

        $results = array();
        foreach ($owners as $owner) {
           $results[$owner['id']][] = $owner['owner'];
        }

        return $results;
    }

    /**
     * Adds a new log entry
     *
     * @param integer $ticket_id      A ticket ID.
     * @param string $user            A user updating the ticket.
     * @param array $changes          A list of changes.
     * @param integer $transactionId  A transaction ID to use.
     *
     * @return integer  A transaction ID.
     * @throws Whups_Exception
     */
    public function updateLog($ticket_id, $user, array $changes = array(),
                              $transactionId = null)
    {
        if (is_null($transactionId)) {
            $transactionId = $this->newTransaction($user);
        }

        foreach ($changes as $type => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }
            foreach ($values as $value) {
                try {
                    $this->_db->insert(
                        'INSERT INTO whups_logs (transaction_id, '
                            . 'ticket_id, log_type, log_value, '
                            . 'log_value_num) VALUES (?, ?, ?, ?, ?)',
                        array((int)$transactionId,
                              (int)$ticket_id,
                              $type,
                              $this->_toBackend((string)$value),
                              (int)$value));
                } catch (Horde_Db_Exception $e) {
                    throw new Whups_Exception($e);
                }
            }
        }

        return $transactionId;
    }

    /**
     * Create a new transaction ID.
     *
     * @param string $creator        A transaction creator.
     * @param string $creator_email  The transaction creator's email address.
     *
     * @return integer  A transaction ID.
     * @throws Whups_Exception
     */
    public function newTransaction($creator, $creator_email = null)
    {
        $insert = 'INSERT INTO whups_transactions '
            . '(transaction_timestamp, transaction_user_id) VALUES(?, ?)';

        $this->_db->beginDbTransaction();
        try {
            if ((empty($creator) || $creator < 0) && !empty($creator_email)) {
                // Need to insert dummy value first so we can get the
                // transaction ID.
                $transactionId = $this->_db->insert($insert, array(time(), 'x'));
                $creator = '-' . $transactionId . '_transaction';
                $this->_db->insert(
                    'INSERT INTO whups_guests (guest_id, guest_email) '
                        . 'VALUES (?, ?)',
                    array((string)$creator, $creator_email));
                $this->_db->update(
                    'UPDATE whups_transactions SET transaction_user_id = ? '
                        . 'WHERE transaction_id = ?',
                    array($creator, $transactionId));
            } else {
                $transactionId = $this->_db->insert($insert, array(time(), $creator));
            }
        } catch (Horde_Db_Exception $e) {
            $this->_db->rollbackDbTransaction();
            throw new Whups_Exception($e);
        }
        $this->_db->commitDbTransaction();

        return $transactionId;
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

    protected function _toBackend($value)
    {
        return Horde_String::convertCharset($value, 'UTF-8', $this->_db->getOption('charset'));
    }

    protected function _fromBackend($value)
    {
        return Horde_String::convertCharset($value, $this->_db->getOption('charset'), 'UTF-8');
    }
}
