<?php
/**
 * Whups_Driver_sql class - implements a Whups backend for the
 * PEAR::DB abstraction layer.
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Robert E. Coyle <robertecoyle@hotmail.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */
class Whups_Driver_sql extends Whups_Driver {

    /**
     * The database connection object.
     *
     * @var DB
     */
    var $_db;

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $_db if a separate write database is not required.
     *
     * @var DB
     */
    var $_write_db;

    /**
     * A mapping of attributes from generic Whups names to DB backend fields.
     *
     * @var array
     */
    var $_map = array('id' => 'ticket_id',
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
                      'date_resolved' => 'date_resolved',
                      );

    /**
     * Adds a new queue to the backend.
     *
     * @params string $name         The queue name.
     * @params string $description  The queue description.
     * @params string $slug         The queue slug.
     * @params string $email        The queue email address.
     *
     * @return mixed  The new queue_id || PEAR_Error
     */
    function addQueue($name, $description, $slug = '', $email = '')
    {
        // Get a new unique id.
        $new_id = $this->_write_db->nextId('whups_queues');
        if (is_a($new_id, 'PEAR_Error')) {
            Horde::logMessage($new_id, 'ERR');
            return $new_id;
        }

        // Check for slug uniqueness
        if (!empty($slug)) {
            $query = 'SELECT count(queue_slug) FROM whups_queues '
                . 'WHERE queue_slug = ?';
            $result = $this->_db->getOne($query, $slug);
            if ($result > 0) {
                return PEAR::raiseError(_("That queue slug is already taken. Please select another."));
            }
        }
        $query = 'INSERT INTO whups_queues '
            . '(queue_id, queue_name, queue_description, queue_slug, queue_email) '
            . 'VALUES (?, ?, ?, ?, ?)';
        $values = array(
            $new_id,
            Horde_String::convertCharset($name, $GLOBALS['registry']->getCharset(),
                                   $this->_params['charset']),
            Horde_String::convertCharset($description, $GLOBALS['registry']->getCharset(),
                                   $this->_params['charset']),
            $slug,
            $email);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::addQueue(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return $new_id;
    }

    function addType($name, $description)
    {
        // Get a new unique id.
        $new_id = $this->_write_db->nextId('whups_types');
        if (is_a($new_id, 'PEAR_Error')) {
            Horde::logMessage($new_id, 'ERR');
            return $new_id;
        }

        $query = 'INSERT INTO whups_types' .
                 ' (type_id, type_name, type_description) VALUES (?, ?, ?)';
        $values = array($new_id,
                        Horde_String::convertCharset($name, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        Horde_String::convertCharset($description, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']));
        Horde::logMessage(
            sprintf('Whups_Driver_sql::addType(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return $new_id;
    }

    function addState($typeId, $name, $description, $category)
    {
        // Get a new state id.
        $new_id = $this->_write_db->nextId('whups_states');
        if (is_a($new_id, 'PEAR_Error')) {
            Horde::logMessage($new_id, 'ERR');
            return $new_id;
        }

        $query = 'INSERT INTO whups_states (state_id, type_id, state_name, '
            . 'state_description, state_category) VALUES (?, ?, ?, ?, ?)';
        $values = array($new_id,
                        $typeId,
                        Horde_String::convertCharset($name, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        Horde_String::convertCharset($description, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        Horde_String::convertCharset($category, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']));
        Horde::logMessage(
            sprintf('Whups_Driver_sql::addState(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return $new_id;
    }

    function addPriority($typeId, $name, $description)
    {
        // Get a new priority id.
        $new_id = $this->_write_db->nextId('whups_priorities');
        if (is_a($new_id, 'PEAR_Error')) {
            Horde::logMessage($new_id, 'ERR');
            return $new_id;
        }

        $query = 'INSERT INTO whups_priorities (priority_id, type_id, '
            . 'priority_name, priority_description) VALUES (?, ?, ?, ?)';
        $values = array($new_id,
                        $typeId,
                        Horde_String::convertCharset($name, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        Horde_String::convertCharset($description, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']));
        Horde::logMessage(
            sprintf('Whups_Driver_sql::addPriority(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return $new_id;
    }

    /**
     * Adds a new version to the specified queue.
     *
     * @param integer $queueId     The queueId to add the version to.
     * @param string $name         The name of the new version.
     * @param string $description  The descriptive text for the new version.
     * @param boolean $active      Whether the version is still active.
     *
     * @return mixed  The new version id || PEAR_Error
     */
    function addVersion($queueId, $name, $description, $active)
    {
        // Get a new version id.
        $new_id = $this->_write_db->nextId('whups_versions');
        if (is_a($new_id, 'PEAR_Error')) {
            Horde::logMessage($new_id, 'ERR');
            return $new_id;
        }

        $query = 'INSERT INTO whups_versions (version_id, queue_id, '
            . 'version_name, version_description, version_active) VALUES (?, ?, ?, ?, ?)';
        $values = array((int)$new_id,
                        (int)$queueId,
                        Horde_String::convertCharset($name, $GLOBALS['registry']->getCharset(),
                                                     $this->_params['charset']),
                        Horde_String::convertCharset($description, $GLOBALS['registry']->getCharset(),
                                                     $this->_params['charset']),
                        (int)$active);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::addVersion(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return $new_id;
    }

    /**
     * Adds a form reply to the backend.
     *
     * @param integer $type  The ticket type id for which to add the new reply.
     * @param string $name   The reply name.
     * @param string $text   The reply text.
     *
     * @return integer  The id of the new form reply.
     */
    function addReply($type, $name, $text)
    {
        // Get a new reply id.
        $new_id = $this->_write_db->nextId('whups_replies');
        if (is_a($new_id, 'PEAR_Error')) {
            Horde::logMessage($new_id, 'ERR');
            return $new_id;
        }

        $query = 'INSERT INTO whups_replies (type_id, reply_id, '
            . 'reply_name, reply_text) VALUES (?, ?, ?, ?)';
        $values = array($type,
                        $new_id,
                        Horde_String::convertCharset($name, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        Horde_String::convertCharset($text, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']));
        Horde::logMessage(
            sprintf('Whups_Driver_sql::addReply(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return $new_id;
    }

    function addTicket(&$info, $requester)
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

        // Get the new unique ids for this ticket and the initial comment.
        $ticketId = $this->_write_db->nextId('whups_tickets');
        if (is_a($ticketId, 'PEAR_Error')) {
            Horde::logMessage($ticketId, 'ERR');
            return $ticketId;
        }

        if (!empty($info['user_email'])) {
            $requester = $ticketId * -1;
        }

        // Create the ticket.
        $query = 'INSERT INTO whups_tickets (ticket_id, ticket_summary, '
            . 'user_id_requester, type_id, state_id, priority_id, queue_id, '
            . 'ticket_timestamp, ticket_due, version_id)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $values = array($ticketId,
                        Horde_String::convertCharset($summary, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        $requester,
                        $type,
                        $state,
                        $priority,
                        $queue,
                        time(),
                        $due,
                        $version);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::addTicket(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        if ($requester < 0) {
            $query = 'INSERT INTO whups_guests (guest_id, guest_email) '
                . 'VALUES (?, ?)';
            $values = array((string)$requester, $info['user_email']);
            Horde::logMessage(
                sprintf('Whups_Driver_sql::addTicket(): query="%s"; values="%s"',
                        $query, implode(',', $values)), 'DEBUG');
            $result = $this->_write_db->query($query, $values);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }
        }

        $commentId = $this->addComment(
            $ticketId, $comment, $requester,
            isset($info['user_email']) ? $info['user_email'] : null);
        if (is_a($commentId, 'PEAR_Error')) {
            Horde::logMessage($commentId, 'ERR');
            return $commentId;
        }

        $transaction = $this->updateLog($ticketId,
                                        $requester,
                                        array('state' => $state,
                                              'priority' => $priority,
                                              'type' => $type,
                                              'summary' => $summary,
                                              'due' => $due,
                                              'comment' => $commentId,
                                              'queue' => $queue));
        if (is_a($transaction, 'PEAR_Error')) {
            Horde::logMessage($transaction, 'ERR');
            return $transaction;
        }

        // Store the last-transaction id in the ticket's info for later use if
        // needed.
        $info['last-transaction'] = $transaction;

        // Assign the ticket, if requested.
        $owners = array_merge(
            isset($info['owners']) ? $info['owners'] : array(),
            isset($info['group_owners']) ? $info['group_owners'] : array());
        foreach ($owners as $owner) {
            $this->addTicketOwner($ticketId, $owner);
            $result = $this->updateLog($ticketId, $requester,
                                       array('assign' => $owner),
                                       $transaction);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }
        }

        // Add any supplied attributes for this ticket.
        foreach ($attributes as $attribute_id => $attribute_value) {
            $result = $this->_setAttributeValue($ticketId,
                                                $attribute_id,
                                                $attribute_value);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }
            $this->updateLog(
                $ticketId, $requester,
                array('attribute' => $attribute_id . ':' . $attribute_value,
                      'attribute_' . $attribute_id => $attribute_value),
                $transaction);
        }

        return $ticketId;
    }

    function addComment($ticket_id, $comment, $creator, $creator_email = null)
    {
        $id = $this->_write_db->nextId('whups_comments');
        if (is_a($id, 'PEAR_Error')) {
            Horde::logMessage($id, 'ERR');
            return $id;
        }

        if (empty($creator) || $creator < 0) {
            $creator = '-' . $id . '_comment';
        }

        // Add the row.
        $result = $this->_write_db->query('INSERT INTO whups_comments (comment_id, ticket_id, user_id_creator, comment_text, comment_timestamp)' .
                                    ' VALUES (?, ?, ?, ?, ?)',
                                    array((int)$id,
                                          (int)$ticket_id,
                                          $creator,
                                          Horde_String::convertCharset($comment, $GLOBALS['registry']->getCharset(), $this->_params['charset']),
                                          time()));
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        if ($creator < 0 && !empty($creator_email)) {
            $query = 'INSERT INTO whups_guests (guest_id, guest_email)'
                . ' VALUES (?, ?)';
            $values = array((string)$creator, $creator_email);
            Horde::logMessage(
                sprintf('Whups_Driver_sql::addComment(): query="%s"; values="%s"',
                        $query, implode(',', $values)), 'DEBUG');
            $result = $this->_write_db->query($query, $values);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                return $result;
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
     *
     * @return boolean|PEAR_Error  True or an error object.
     */
    function updateTicket($ticketId, $attributes)
    {
        if (!count($attributes)) {
            return true;
        }

        $query = '';
        $values = array();
        foreach ($attributes as $field => $value) {
            if (empty($this->_map[$field])) {
                continue;
            }

            $query .= $this->_map[$field] . ' = ?, ';
            $values[] = Horde_String::convertCharset($value, $GLOBALS['registry']->getCharset(), $this->_params['charset']);
        }

        /* Don't try to execute an empty query (if we didn't find any updates
         * to make). */
        if (empty($query)) {
            return;
        }

        $query = 'UPDATE whups_tickets SET ' . substr($query, 0, -2) . ' WHERE ticket_id = ?';
        $values[] = (int)$ticketId;

        Horde::logMessage(
            sprintf('Whups_Driver_sql::updateTicket(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        return $this->_write_db->query($query, $values);
    }

    function addTicketOwner($ticketId, $owner)
    {
        return $this->_write_db->query('INSERT INTO whups_ticket_owners (ticket_id, ticket_owner) VALUES (?, ?)',
                                 array($ticketId, $owner));
    }

    function deleteTicketOwner($ticketId, $owner)
    {
        return $this->_write_db->query('DELETE FROM whups_ticket_owners WHERE ticket_owner = ? AND ticket_id = ?',
                                 array($owner, $ticketId));
    }

    function deleteTicket($info)
    {
        $id = (int)$info['id'];

        $tables = array('whups_ticket_listeners',
                        'whups_logs',
                        'whups_comments',
                        'whups_tickets',
                        'whups_attributes');

        if (!empty($GLOBALS['conf']['vfs']['type'])) {
            try {
                $vfs = $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs();
            } catch (VFS_Exception $e) {
                return PEAR::raiseError($e->getMessage());
            }

            if ($vfs->isFolder(WHUPS_VFS_ATTACH_PATH, $id)) {
                try {
                    $vfs->deleteFolder(WHUPS_VFS_ATTACH_PATH, $id, true);
                } catch (VFS_Exception $e) {
                    return PEAR::raiseError($e->getMessage());
                }
            }
        }

        // Attempt to clean up everything.
        foreach ($tables as $table) {
            $query = 'DELETE FROM ' . $table . ' WHERE ticket_id = ?';
            $values = array($id);
            Horde::logMessage(
                sprintf('Whups_Driver_sql::deleteTicket(): query="%s"; values="%s"',
                        $query, implode(',', $values)), 'DEBUG');
            $result = $this->_write_db->query($query, $values);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }
        }

        return true;
    }

    function executeQuery($query, $vars, $get_details = true, $munge = true)
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

        $sql = "SELECT whups_tickets.ticket_id, 1 FROM whups_tickets $joins "
            . "WHERE $where";

        Horde::logMessage(
                sprintf('Whups_Driver_sql::executeQuery(): query="%s"', $sql), 'DEBUG');
        $ids = $this->_db->getAssoc($sql);
        if (is_a($ids, 'PEAR_Error')) {
            Horde::logMessage($ids, 'ERR');
            $GLOBALS['notification']->push($ids, 'horde.error');
            return array();
        }
        $ids = array_keys($this->_db->getAssoc($sql));

        if (!count($ids)) {
            return array();
        }

        if ($get_details) {
            $ids = $this->getTicketsByProperties(array('id' => $ids), $munge);
        }

        return $ids;
    }

    function _clauseFromQuery($args, $type, $criterion, $cvalue, $operator, $value)
    {
        switch ($type) {
        case QUERY_TYPE_AND:
            return $this->_concatClauses($args, 'AND');

        case QUERY_TYPE_OR:
            return $this->_concatClauses($args, 'OR');

        case QUERY_TYPE_NOT:
            return $this->_notClause($args);

        case QUERY_TYPE_CRITERION:
            return $this->_criterionClause($criterion, $cvalue, $operator, $value);
        }
    }

    function _concatClauses($args, $conjunction)
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

    function _notClause($args)
    {
        if (count($args) == 0) {
            return '';
        }

        // TODO: put in a sanity check: count($args) should be 1
        // always.
        return 'NOT (' . $args[0] . ')';
    }

    function _criterionClause($criterion, $cvalue, $operator, $value)
    {
        $func    = '';
        $funcend = '';

        switch ($operator) {
        case OPERATOR_GREATER: $op = '>'; break;
        case OPERATOR_LESS:    $op = '<'; break;
        case OPERATOR_EQUAL:   $op = '='; break;
        case OPERATOR_PATTERN: $op = 'LIKE'; break;

        case OPERATOR_CI_SUBSTRING:
            $value = '%' . str_replace(array('%', '_'), array('\%', '\_'), $value) . '%';
            if ($this->_db->phptype == 'pgsql') {
                $op = 'ILIKE';
            } else {
                $op = 'LIKE';
                $func = 'LOWER(';
                $funcend = ')';
            }
            break;

        case OPERATOR_CS_SUBSTRING:
            // FIXME: Does not work in Postgres.
            $func    = 'LOCATE(' . $this->_write_db->quote($value) . ', ';
            $funcend = ')';
            $op      = '>';
            $value   = 0;
            break;

        case OPERATOR_WORD:
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

        $qvalue = $this->_write_db->quote($value);
        $done = false;
        $text = '';

        switch ($criterion) {
        case CRITERION_ID:
            $text = "{$func}whups_tickets.ticket_id{$funcend}";
            break;

        case CRITERION_QUEUE:
            $text = "{$func}whups_tickets.queue_id{$funcend}";
            break;

        case CRITERION_VERSION:
            $text = "{$func}whups_tickets.version_id{$funcend}";
            break;

        case CRITERION_TYPE:
            $text = "{$func}whups_tickets.type_id{$funcend}";
            break;

        case CRITERION_STATE:
            $text = "{$func}whups_tickets.state_id{$funcend}";
            break;

        case CRITERION_PRIORITY:
            $text = "{$func}whups_tickets.priority_id{$funcend}";
            break;

        case CRITERION_SUMMARY:
            $text = "{$func}whups_tickets.ticket_summary{$funcend}";
            break;

        case CRITERION_TIMESTAMP:
            $text = "{$func}whups_tickets.ticket_timestamp{$funcend}";
            break;

        case CRITERION_UPDATED:
            $text = "{$func}whups_tickets.date_updated{$funcend}";
            break;

        case CRITERION_RESOLVED:
            $text = "{$func}whups_tickets.date_resolved{$funcend}";
            break;

        case CRITERION_ASSIGNED:
            $text = "{$func}whups_tickets.date_assigned{$funcend}";
            break;

        case CRITERION_DUE:
            $text = "{$func}whups_tickets.ticket_due{$funcend}";
            break;

        case CRITERION_ATTRIBUTE:
            $cvalue = (int)$cvalue;

            if (!isset($this->jtables['whups_attributes'])) {
                $this->jtables['whups_attributes'] = 1;
            }
            $v = $this->jtables['whups_attributes']++;

            $this->joins[] = "LEFT JOIN whups_attributes wa$v ON (whups_tickets.ticket_id = wa$v.ticket_id AND wa$v.attribute_id = $cvalue)";
            $text = "{$func}wa$v.attribute_value{$funcend} $op $qvalue";
            $done = true;
            break;

        case CRITERION_OWNERS:
            if (!isset($this->jtables['whups_ticket_owners'])) {
                $this->jtables['whups_ticket_owners'] = 1;
            }
            $v = $this->jtables['whups_ticket_owners']++;

            $this->joins[] = "LEFT JOIN whups_ticket_owners wto$v ON whups_tickets.ticket_id = wto$v.ticket_id";
            $qvalue = $this->_write_db->quote('user:' . $value);
            $text = "{$func}wto$v.ticket_owner{$funcend} $op $qvalue";
            $done = true;
            break;

        case CRITERION_REQUESTER:
            if (!isset($this->jtables['whups_guests'])) {
                $this->jtables['whups_guests'] = 1;
            }
            $v = $this->jtables['whups_guests']++;

            $this->joins[] = "LEFT JOIN whups_guests wg$v ON whups_tickets.user_id_requester = wg$v.guest_id";
            $text = "{$func}whups_tickets.user_id_requester{$funcend} $op $qvalue OR {$func}wg$v.guest_email{$funcend} $op $qvalue";
            $done = true;
            break;

        case CRITERION_GROUPS:
            if (!isset($this->jtables['whups_ticket_owners'])) {
                $this->jtables['whups_ticket_owners'] = 1;
            }
            $v = $this->jtables['whups_ticket_owners']++;

            $this->joins[] = "LEFT JOIN whups_ticket_owners wto$v ON whups_tickets.ticket_id = wto$v.ticket_id";
            $qvalue = $this->_write_db->quote('group:' . $value);
            $text = "{$func}wto$v.ticket_owner{$funcend} $op $qvalue";
            $done = true;
            break;

        case CRITERION_ADDED_COMMENT:
            if (!isset($this->jtables['whups_comments'])) {
                $this->jtables['whups_comments'] = 1;
            }
            $v = $this->jtables['whups_comments']++;

            $this->joins[] = "LEFT JOIN whups_comments wc$v ON (whups_tickets.ticket_id = wc$v.ticket_id)";
            $text = "{$func}wc$v.user_id_creator{$funcend} $op $qvalue";
            $done = true;
            break;

        case CRITERION_COMMENT:
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

    function getTicketsByProperties($info, $munge = true, $perowner = false)
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
                . $this->_write_db->quote('%' . Horde_String::lower($info['summary']) . '%'));
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

        $fields = array('ticket_id AS id',
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
                        . $this->_write_db->quote($category);
                }
                $cat = ' AND (' . $cat . ')';
            } else {
                $cat = isset($info['category'])
                    ? ' AND whups_states.state_category = '
                        . $this->_write_db->quote($info['category'])
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
                        . $this->_write_db->quote($type);
                }
                $t = ' AND (' . implode(' OR ', $t) . ')';
            } else {
                $t = isset($info['type_id'])
                    ? ' AND whups_tickets.type_id = '
                        . $this->_write_db->quote($info['type_id'])
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
                        . $this->_write_db->quote($owner);
                }
                $join .= '(' . implode(' OR ', $clauses) . ')';
            } else {
                $join .= 'whups_ticket_owners.ticket_owner = '
                    . $this->_write_db->quote($info['owner']);
            }
        }
        if (isset($info['notowner'])) {
            if ($info['notowner'] === true) {
                // Filter for tickets with no owner.
                $join .= ' LEFT JOIN whups_ticket_owners ON whups_tickets.ticket_id = whups_ticket_owners.ticket_id AND whups_ticket_owners.ticket_owner IS NOT NULL';
            } else {
                $join .= ' LEFT JOIN whups_ticket_owners ON whups_tickets.ticket_id = whups_ticket_owners.ticket_id AND whups_ticket_owners.ticket_owner = ' . $this->_write_db->quote($info['notowner']);
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
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getTicketsByProperties(): query="%s"',
                    $query), 'DEBUG');

        $info = $this->_db->getAll($query, null, DB_FETCHMODE_ASSOC);
        if (is_a($info, 'PEAR_Error')) {
            Horde::logMessage($info, 'ERR');
            return $info;
        }

        if (!count($info)) {
            return array();
        }

        $info = Horde_String::convertCharset($info, $this->_params['charset']);

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
        if (is_a($owners, 'PEAR_Error')) {
            return $owners;
        }
        foreach ($owners as $row) {
            if (empty($tickets[$row['id']]['owners'])) {
                $tickets[$row['id']]['owners'] = array();
            }
            $tickets[$row['id']]['owners'][] = $row['owner'];
        }

        $attributes = $this->getTicketAttributesWithNames(array_keys($tickets));
        foreach ($attributes as $row) {
            $attribute_id = 'attribute_' . $row['attribute_id'];
            $tickets[$row['id']][$attribute_id] = $row['attribute_value'];
            $tickets[$row['id']][$attribute_id . '_name'] = $row['attribute_name'];
        }
        return array_values($tickets);
    }

    function getTicketDetails($ticket, $checkPerms = true)
    {
        $result = $this->getTicketsByProperties(array('id' => $ticket));
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        } elseif (!isset($result[0])) {
            return PEAR::raiseError(sprintf(_("Ticket %s was not found."),
                                            $ticket));
        } else {
            $queues = Whups::permissionsFilter(
                $this->getQueues(), 'queue', Horde_Perms::READ, $GLOBALS['registry']->getAuth(),
                $result[0]['user_id_requester']);
            if ($checkPerms &&
                  !in_array($result[0]['queue'], array_flip($queues))) {
                return PEAR::raiseError(
                    sprintf(_("You do not have permission to access this ticket (%s)."),
                            $ticket),
                    0);
            }
        }

        return $result[0];
    }

    function getTicketState($ticket_id)
    {
        $query = 'SELECT whups_tickets.state_id, whups_states.state_category '
            . 'FROM whups_tickets INNER JOIN whups_states '
            . 'ON whups_tickets.state_id = whups_states.state_id '
            . 'WHERE ticket_id = ?';
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getTicketState(): query="%s"; values="%s"',
                    $query, $ticket_id), 'DEBUG');
        $state = $this->_db->getRow($query, array($ticket_id),
                                    DB_FETCHMODE_ASSOC);
        if (is_a($state, 'PEAR_Error')) {
            Horde::logMessage($state, 'ERR');
        }
        return $state;
    }

    function getGuestEmail($guest_id)
    {
        static $guestCache;

        if (!isset($guestCache[$guest_id])) {
            $query = 'SELECT guest_email FROM whups_guests WHERE guest_id = ?';
            $values = array($guest_id);
            Horde::logMessage(
                sprintf('Whups_Driver_sql::getGuestEmail(): query="%s"; values="%s"',
                        $query, implode(',', $values)), 'DEBUG');
            $result = $this->_db->getOne($query, $values);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
            $guestCache[$guest_id] = Horde_String::convertCharset(
                $result, $this->_params['charset']);
        }
        return $guestCache[$guest_id];
    }

    function _getHistory($ticket_id)
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
                    AND whups_logs.log_value_num = whups_attributes_desc.attribute_id';

        $fields = $this->_prefixTableToColumns('whups_comments',
                                               array('comment_text'))
            . ', whups_logs.log_timestamp AS timestamp, whups_logs.ticket_id'
            . ', whups_logs.log_type, whups_logs.log_value'
            . ', whups_logs.log_value_num, whups_logs.log_id'
            . ', whups_logs.transaction_id, whups_logs.user_id'
            . ', whups_priorities.priority_name, whups_states.state_name, whups_versions.version_name'
            . ', whups_types.type_name, whups_attributes_desc.attribute_name';

        $query = "SELECT $fields FROM whups_logs $join WHERE $where "
            . "ORDER BY whups_logs.transaction_id";
        Horde::logMessage(sprintf('Whups_Driver_sql::_getHistory(): query="%s"',
                                  $query), 'DEBUG');

        $history = $this->_db->getAll($query, null, DB_FETCHMODE_ASSOC);
        if (is_a($history, 'PEAR_Error')) {
            Horde::logMessage($history, 'ERR');
            return $history;
        }

        $history = Horde_String::convertCharset($history, $this->_params['charset']);
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
     */
    function deleteHistory($transaction)
    {
        $transaction = (int)$transaction;

        /* Deleting comments. */
        $query = 'SELECT log_value FROM whups_logs WHERE log_type = ? AND transaction_id = ?';
        $values = array('comment', $transaction);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::deleteTransaction(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $comments = $this->_db->getCol($query, 'log_value', $values);
        if (is_a($comments, 'PEAR_Error')) {
            Horde::logMessage($comments, 'ERR');
            return $comments;
        }

        if ($comments) {
            $query = sprintf('DELETE FROM whups_comments WHERE comment_id IN (%s)',
                             implode(',', $comments));
            Horde::logMessage(
                sprintf('Whups_Driver_sql::deleteTransaction(): query="%s"', $query), 'DEBUG');
            $result = $this->_write_db->query($query);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }
        }

        /* Deleting attachments. */
        if (isset($GLOBALS['conf']['vfs']['type'])) {
            $query = 'SELECT ticket_id, log_value FROM whups_logs WHERE log_type = ? AND transaction_id = ?';
            $values = array('attachment', $transaction);
            Horde::logMessage(
                sprintf('Whups_Driver_sql::deleteTransaction(): query="%s"; values="%s"',
                        $query, implode(',', $values)), 'DEBUG');
            $attachments = $this->_db->query($query, $values);
            if (is_a($attachments, 'PEAR_Error')) {
                Horde::logMessage($attachments, 'ERR');
                return $attachments;
            }
            require_once 'VFS.php';
            $vfs = &VFS::singleton($GLOBALS['conf']['vfs']['type'],
                                   Horde::getDriverConfig('vfs'));
            if (is_a($vfs, 'PEAR_Error')) {
                return $vfs;
            }

            while ($attachment = $attachments->fetchRow(DB_FETCHMODE_ASSOC)) {
                $dir = WHUPS_VFS_ATTACH_PATH . '/' . $attachment['ticket_id'];
                if ($vfs->exists($dir, $attachment['log_value'])) {
                    try {
                        $result = $vfs->deleteFile($dir, $attachment['log_value']);
                    } catch (VFS_Exception $e) {
                        return PEAR::raiseError($e->getMessage());
                    }
                } else {
                    Horde::logMessage(sprintf(_("Attachment %s not found."),
                                              $attachment['log_value']),
                                      'WARN');
                }
            }
        }

        $query = 'DELETE FROM whups_logs WHERE transaction_id = ?';
        Horde::logMessage(
            sprintf('Whups_Driver_sql::deleteTransaction(): query="%s"; values="%s"',
                    $query, implode(',', $values)),
           'DEBUG');
        $result = $this->_write_db->query($query, array($transaction));
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
        }
        return $result;
    }

    /**
     * Return a list of queues with open tickets, and the number of
     * open tickets in each.
     *
     * @param array $queues Array of queue ids to summarize.
     */
    function getQueueSummary($queue_ids)
    {
        $qstring = (int)array_shift($queue_ids);
        while ($queue_ids) {
            $qstring .= ', ' . (int)array_shift($queue_ids);
        }

        $sql = 'SELECT q.queue_id AS id, q.queue_slug AS slug, '
            . 'q.queue_name AS name, q.queue_description AS description, '
            . 'COUNT(t.ticket_id) AS open_tickets '
            . 'FROM whups_queues q LEFT JOIN whups_tickets t '
            . 'ON q.queue_id = t.queue_id '
            . 'INNER JOIN whups_states s '
            . 'ON (t.state_id = s.state_id AND s.state_category != \'resolved\') '
            . 'WHERE q.queue_id IN (' . $qstring . ') '
            . 'GROUP BY q.queue_id, q.queue_slug, q.queue_name, '
            . 'q.queue_description ORDER BY q.queue_name';
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getQueueSummary(): query="%s"', $sql), 'DEBUG');
        $queues = $this->_db->getAll($sql, null, DB_FETCHMODE_ASSOC);
        if (is_a($queues, 'PEAR_Error')) {
            Horde::logMessage($queues, 'ERR');
            return $queues;
        }

        return Horde_String::convertCharset($queues, $this->_params['charset']);
    }

    function getQueueInternal($queueId)
    {
        static $queues;

        if (isset($queues[$queueId])) {
            return $queues[$queueId];
        }

        $query = 'SELECT queue_id, queue_name, queue_description, '
            . 'queue_versioned, queue_slug, queue_email '
            . 'FROM whups_queues WHERE queue_id = ?';
        $values = array((int)$queueId);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getQueueInternal(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $queue = $this->_db->getRow($query, $values, DB_FETCHMODE_ASSOC);
        if (is_a($queue, 'PEAR_Error')) {
            Horde::logMessage($queue, 'ERR');
            return $queue;
        } elseif (!$queue) {
            return false;
        }

        $queue = Horde_String::convertCharset($queue, $this->_params['charset']);
        $queues[$queueId] = array('id' => (int)$queue['queue_id'],
                                  'name' => $queue['queue_name'],
                                  'description' => $queue['queue_description'],
                                  'versioned' => (bool)$queue['queue_versioned'],
                                  'slug' => $queue['queue_slug'],
                                  'email' => $queue['queue_email'],
                                  'readonly' => false);

        return $queues[$queueId];
    }

    function getQueueBySlugInternal($slug)
    {
        $query = 'SELECT queue_id, queue_name, queue_description, '
            . 'queue_versioned, queue_slug FROM whups_queues WHERE '
            . 'queue_slug = ?';
        $values = array((string)$slug);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getQueueInternal(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $queue = $this->_db->getAll($query, $values);
        if (is_a($queue, 'PEAR_Error')) {
            Horde::logMessage($queue, 'ERR');
            return $queue;
        } elseif (!count($queue)) {
            return $queue;
        }

        $queue = Horde_String::convertCharset($queue, $this->_params['charset']);
        $queue = $queue[0];
        return array('id' => $queue[0],
                     'name' => $queue[1],
                     'description' => $queue[2],
                     'versioned' => $queue[3],
                     'slug' => $queue[4],
                     'readonly' => false);
    }

    function getQueuesInternal()
    {
        static $internals;

        if ($internals) {
            return $internals;
        }

        $query = 'SELECT queue_id, queue_name FROM whups_queues '
            . 'ORDER BY queue_name';
        Horde::logMessage(sprintf('Whups_Driver_sql::getQueues(): query="%s"',
                                  $query), 'DEBUG');
        $queues = $this->_db->getAssoc($query);
        if (is_a($queues, 'PEAR_Error')) {
            Horde::logMessage($queues, 'ERR');
            return array();
        }

        $internals = Horde_String::convertCharset($queues, $this->_params['charset']);
        return $internals;
    }

    function updateQueue($queueId, $name, $description, $types, $versioned,
                         $slug = '', $email = '', $default = null)
    {
        global $registry;

        if ($registry->hasMethod('tickets/listQueues') == $registry->getApp()) {
            // Is slug unique?
            $query = 'SELECT count(queue_slug) FROM whups_queues WHERE queue_slug = ? AND queue_id <> ?';
            $result = $this->_db->getOne($query, array($slug, $queueId));
            if ($result > 0) {
                return PEAR::raiseError(_("That queue slug is already taken. Please select another."));
            }

            // First update the queue entry itself.
            $query = 'UPDATE whups_queues SET queue_name = ?, '
                     . 'queue_description = ?, queue_versioned = ?, '
                     . 'queue_slug = ?, queue_email = ? WHERE queue_id = ?';
            $values = array(Horde_String::convertCharset($name,
                                                   $GLOBALS['registry']->getCharset(),
                                                   $this->_params['charset']),
                            Horde_String::convertCharset($description,
                                                   $GLOBALS['registry']->getCharset(),
                                                   $this->_params['charset']),
                            (empty($versioned) ? 0 : 1),
                            $slug,
                            $email,
                            $queueId);
            Horde::logMessage(
                sprintf('Whups_Driver_sql::updateQueue(): query="%s"; values="%s"',
                        $query, implode(',', $values)), 'DEBUG');
            $result = $this->_write_db->query($query, $values);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }
        }

        // Clear all previous type-queue associations.
        $query = 'DELETE FROM whups_types_queues WHERE queue_id = ?';
        $values = array($queueId);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::updateQueue(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        // Add the new associations.
        if (is_array($types)) {
            foreach ($types as $typeId) {
                $query = 'INSERT INTO whups_types_queues '
                    . '(queue_id, type_id, type_default) VALUES (?, ?, ?)';
                $values = array($queueId, $typeId, $default == $typeId ? 1 : 0);
                Horde::logMessage(
                    sprintf('Whups_Driver_sql::updateQueue(): query="%s"; values="%s"',
                            $query, implode(',', $values)), 'DEBUG');
                $result = $this->_write_db->query($query, $values);
                if (is_a($result, 'PEAR_Error')) {
                    Horde::logMessage($result, 'ERR');
                    return $result;
                }
            }
        }

        return true;
    }

    function getDefaultType($queue)
    {
        $query = 'SELECT type_id FROM whups_types_queues '
            . 'WHERE type_default = 1 AND queue_id = ?';
        Horde::logMessage(
            sprintf('Whups_Driver_sql::setDefaultType(): query="%s"', $query), 'DEBUG');
        $type = $this->_db->getOne($query, array($queue));
        if (is_a($type, 'PEAR_Error')) {
            Horde::logMessage($type, 'ERR');
            return null;
        }
        return $type;
    }

    /**
     */
    function deleteQueue($queueId)
    {
        $tables = array('whups_queues_users',
                        'whups_types_queues',
                        'whups_versions',
                        'whups_queues');
        foreach ($tables as $table) {
            $query = 'DELETE FROM ' . $table . ' WHERE queue_id = ?';
            $values = array($queueId);
            Horde::logMessage(
                sprintf('Whups_Driver_sql::deleteQueue(): query="%s"; values="%s"',
                        $query, implode(',', $values)), 'DEBUG');
            $result = $this->_write_db->query($query, $values);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }
        }

        return parent::deleteQueue($queueId);
    }

    /**
     */
    function updateTypesQueues($tmPairs)
    {
        // Do this as a transaction.
        $this->_write_db->autoCommit(false);

        // Delete existing associations.
        $query = 'DELETE FROM whups_types_queues';
        Horde::logMessage(
            sprintf('Whups_Driver_sql::updateTypesQueues(): query="%s"', $query), 'DEBUG');
        $result = $this->_write_db->query($query);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            $this->_write_db->rollback();
            $this->_write_db->autoCommit(true);
            return $result;
        }

        // Insert new associations.
        foreach ($tmPairs as $pair) {
            $query = 'INSERT INTO whups_types_queues (queue_id, type_id) '
                . 'VALUES (?, ?)';
            $values = array((int)$pair[0], (int)$pair[1]);
            Horde::logMessage(
                sprintf('Whups_Driver_sql::updateTypesQueues(): query="%s"; values="%s"',
                        $query, implode(',', $values)), 'DEBUG');
            $result = $this->_write_db->query($query, $values);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                $this->_write_db->rollback();
                $this->_write_db->autoCommit(true);
                return $result;
            }
        }

        $result = $this->_write_db->commit();
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            $this->_write_db->rollback();
            $this->_write_db->autoCommit(true);
            return $result;
        }

        $this->_write_db->autoCommit(true);
    }

    function getQueueUsers($queueId)
    {
        $query = 'SELECT user_uid AS u1, user_uid AS u2 FROM whups_queues_users'
            . ' WHERE queue_id = ? ORDER BY u1';
        $values = array($queueId);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getQueueUsers(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $users = $this->_db->getAssoc($query, false, $values);
        if (is_a($users, 'PEAR_Error')) {
            Horde::logMessage($users, 'ERR');
            return array();
        }

        return $users;
    }

    function addQueueUser($queueId, $userId)
    {
        if (!is_array($userId)) {
            $userId = array($userId);
        }
        foreach ($userId as $user) {
            $query = 'INSERT INTO whups_queues_users (queue_id, user_uid) '
                . 'VALUES (?, ?)';
            $values = array($queueId, $user);
            Horde::logMessage(
            sprintf('Whups_Driver_sql::addQueueUser(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
            $result = $this->_write_db->query($query, $values);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }
        }
        return true;
    }

    function removeQueueUser($queueId, $userId)
    {
        $query = 'DELETE FROM whups_queues_users' .
                 ' WHERE queue_id = ? AND user_uid = ?';
        $values = array($queueId, $userId);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::removeQueueUser(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        return $this->_write_db->query($query, $values);
    }

    function getType($typeId)
    {
        if (empty($typeId)) {
            return false;
        }
        $query = 'SELECT type_id, type_name, type_description '
            . 'FROM whups_types WHERE type_id = ?';
        $values = array($typeId);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getType(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');

        $type = $this->_db->getAssoc($query, false, $values);
        if (is_a($type, 'PEAR_Error')) {
            Horde::logMessage($type, 'ERR');
            return $type;
        }

        $type = Horde_String::convertCharset($type, $this->_params['charset']);
        return array('id' => $typeId,
                     'name' => isset($type[$typeId][0]) ? $type[$typeId][0] : '',
                     'description' => isset($type[$typeId][1]) ? $type[$typeId][1] : '');
    }

    function getTypes($queueId)
    {
        $query = 'SELECT t.type_id, t.type_name '
            . 'FROM whups_types t, whups_types_queues tm '
            . 'WHERE tm.queue_id = ? AND tm.type_id = t.type_id '
            . 'ORDER BY t.type_name';
        $values = array($queueId);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getTypes(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $types = $this->_db->getAssoc($query, false, $values);
        if (is_a($types, 'PEAR_Error')) {
            Horde::logMessage($types, 'ERR');
            return array();
        }

        return Horde_String::convertCharset($types, $this->_params['charset']);
    }

    function getTypeIds($queueId)
    {
        $query = 'SELECT type_id FROM whups_types_queues '
            . 'WHERE queue_id = ? ORDER BY type_id';
        $values = array($queueId);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getTypeIds(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        return $this->_db->getAll($query, $values, DB_FETCHMODE_ASSOC);
    }

    function getAllTypes()
    {
        $query = 'SELECT type_id, type_name FROM whups_types ORDER BY type_name';
        Horde::logMessage(sprintf('Whups_Driver_sql::getAllTypes(): query="%s"',
                                  $query), 'DEBUG');
        $types = $this->_db->getAssoc($query);
        if (is_a($types, 'PEAR_Error')) {
            Horde::logMessage($types, 'ERR');
            return array();
        }

        return Horde_String::convertCharset($types, $this->_params['charset']);
    }

    function getAllTypeInfo()
    {
        $query = 'SELECT type_id, type_name, type_description '
            . 'FROM whups_types ORDER BY type_id';
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getAllTypeInfo(): query="%s"', $query), 'DEBUG');

        $info = $this->_db->getAll($query, null, DB_FETCHMODE_ASSOC);
        if (is_a($info, 'PEAR_Error')) {
            Horde::logMessage($info, 'ERR');
            return $info;
        }

        return Horde_String::convertCharset($info, $this->_params['charset']);
    }

    function getTypeName($type)
    {
        $query = 'SELECT type_name FROM whups_types WHERE type_id = ?';
        $values = array($type);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getTypeName(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');

        $name = $this->_db->getOne($query, $values);
        if (is_a($name, 'PEAR_Error')) {
            Horde::logMessage($name, 'ERR');
            return $name;
        }

        return Horde_String::convertCharset($name, $this->_params['charset']);
    }

    function updateType($typeId, $name, $description)
    {
        $query = 'UPDATE whups_types' .
                 ' SET type_name = ?, type_description = ? WHERE type_id = ?';
        $values = array(Horde_String::convertCharset($name, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        Horde_String::convertCharset($description, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        $typeId);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::updateType(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        return $this->_write_db->query($query, $values);
    }

    function deleteType($typeId)
    {
        $values = array((int)$typeId);

        $query = 'DELETE FROM whups_states WHERE type_id = ?';
        Horde::logMessage(
            sprintf('Whups_Driver_sql::deleteType(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $this->_write_db->query($query, $values);

        $query = 'DELETE FROM whups_priorities WHERE type_id = ?';
        Horde::logMessage(
            sprintf('Whups_Driver_sql::deleteType(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $this->_write_db->query($query, $values);

        $query = 'DELETE FROM whups_attributes_desc WHERE type_id = ?';
        Horde::logMessage(
            sprintf('Whups_Driver_sql::deleteType(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $this->_write_db->query($query, $values);

        $query = 'DELETE FROM whups_types WHERE type_id = ?';
        Horde::logMessage(
            sprintf('Whups_Driver_sql::deleteType(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        return $this->_write_db->query($query, $values);
    }

    function getStates($type = null, $category = '', $notcategory = '')
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
            $where = $this->_addWhere($where, $category, 'state_category = ' . $this->_write_db->quote($category));
        } else {
            $clauses = array();
            foreach ($category as $cat) {
                $clauses[] = 'state_category = ' . $this->_write_db->quote($cat);
            }
            if (count($clauses))
                $where = $this->_addWhere($where, $cat, implode(' OR ', $clauses));
        }

        if (!is_array($notcategory)) {
            $where = $this->_addWhere($where, $notcategory, 'state_category <> ' . $this->_write_db->quote($notcategory));
        } else {
            $clauses = array();
            foreach ($notcategory as $notcat) {
                $clauses[] = 'state_category <> ' . $this->_write_db->quote($notcat);
            }
            if (count($clauses)) {
                $where = $this->_addWhere($where, $notcat, implode(' OR ', $clauses));
            }
        }
        if (!empty($where)) {
            $where = ' WHERE ' . $where;
        }

        $query = "SELECT $fields FROM $from$where ORDER BY $order";
        Horde::logMessage(sprintf('Whups_Driver_sql::getStates(): query="%s"',
                                  $query), 'DEBUG');

        $states = $this->_db->getAssoc($query);
        if (is_a($states, 'PEAR_Error')) {
            Horde::logMessage($states, 'ERR');
            return $states;
        }

        if (empty($type)) {
            foreach ($states as $id => $state) {
                $states[$id] = $state[0] . ' (' . $state[2] . ')';
            }
        }

        return Horde_String::convertCharset($states, $this->_params['charset']);
    }

    function getState($stateId)
    {
        if (empty($stateId)) {
            return false;
        }
        $query = 'SELECT state_id, state_name, state_description, '
            . 'state_category, type_id FROM whups_states WHERE state_id = ?';
        $values = array($stateId);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getState(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $state = $this->_db->getAssoc($query, false, $values);
        if (is_a($state, 'PEAR_Error')) {
            Horde::logMessage($state, 'ERR');
            return $state;
        }

        $state = Horde_String::convertCharset($state, $this->_params['charset']);
        return array(
            'id' => $stateId,
            'name' => isset($state[$stateId][0]) ? $state[$stateId][0] : '',
            'description' => isset($state[$stateId][1]) ? $state[$stateId][1] : '',
            'category' => isset($state[$stateId][2]) ? $state[$stateId][2] : '',
            'type' => isset($state[$stateId][3]) ? $state[$stateId][3] : '');
    }

    function getAllStateInfo($type)
    {
        $query = 'SELECT state_id, state_name, state_description, '
            . 'state_category FROM whups_states WHERE type_id = ? '
            . 'ORDER BY state_id';
        $values = array($type);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getAllStateInfo(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');

        $info = $this->_db->getAll($query, $values, DB_FETCHMODE_ASSOC);
        if (is_a($info, 'PEAR_Error')) {
            Horde::logMessage($info, 'ERR');
            return $info;
        }

        return Horde_String::convertCharset($info, $this->_params['charset']);
    }

    function updateState($stateId, $name, $description, $category)
    {
        $query = 'UPDATE whups_states SET state_name = ?, '
            . 'state_description = ?, state_category = ? WHERE state_id = ?';
        $values = array(Horde_String::convertCharset($name, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        Horde_String::convertCharset($description, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        Horde_String::convertCharset($category, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        $stateId);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::updateState(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        return $this->_write_db->query($query, $values);
    }

    function getDefaultState($type)
    {
        $query = 'SELECT state_id FROM whups_states '
            . 'WHERE state_default = 1 AND type_id = ?';
        $values = array($type);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getDefaultState(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        return $this->_db->getOne($query, $values);
    }

    function setDefaultState($type, $state)
    {
        $query = 'UPDATE whups_states SET state_default = 0 WHERE type_id = ?';
        $values = array($type);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::setDefaultState(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }
        $query = 'UPDATE whups_states SET state_default = 1 WHERE state_id = ?';
        $values = array($state);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::setDefaultState(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        return $this->_write_db->query($query, $values);
    }

    function deleteState($state_id)
    {
        $query = 'DELETE FROM whups_states WHERE state_id = ?';
        $values = array((int)$state_id);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::deleteState(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        return $this->_write_db->query($query, $values);
    }

    /**
     * Retrieve query details.
     *
     * @param integer $queryId
     *
     * @return array
     */
    function getQuery($queryId)
    {
        if (empty($queryId)) {
            return false;
        }
        $query = 'SELECT query_parameters, query_object FROM whups_queries '
            . 'WHERE query_id = ?';
        $values = array((int)$queryId);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getQuery(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $query = $this->_db->getRow($query, $values, DB_FETCHMODE_ASSOC);
        if (is_a($query, 'PEAR_Error')) {
            Horde::logMessage($query, 'ERR');
            return $query;
        }

        return Horde_String::convertCharset($query, $this->_params['charset']);
    }

    /**
     * Save query details, inserting a new query row if necessary.
     *
     * @param Whups_Query $query
     */
    function saveQuery($query)
    {
        $exists = $this->_db->getOne('SELECT 1 FROM whups_queries '
                                     . 'WHERE query_id = ' . (int)$query->id);
        if (is_a($exists, 'PEAR_Error')) {
            return $exists;
        }

        if ($exists) {
            $q = 'UPDATE whups_queries SET query_parameters = ?, '
                . 'query_object = ? WHERE query_id = ?';
            $values = array(serialize($query->parameters),
                            serialize($query->query),
                            $query->id);
        } else {

            $q = 'INSERT INTO whups_queries (query_id, query_parameters, '
                . 'query_object) VALUES (?, ?, ?)';
            $values = array($query->id, serialize($query->parameters),
                            serialize($query->query));
        }
        $values = Horde_String::convertCharset($values, $GLOBALS['registry']->getCharset(),
                                         $this->_params['charset']);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::saveQuery(): query="%s"; values="%s"',
                    $q, implode(',', $values)), 'DEBUG');
        return $this->_write_db->query($q, $values);
    }

    /**
     * Delete query details.
     *
     * @param integer $queryId
     */
    function deleteQuery($queryId)
    {
        $query = 'DELETE FROM whups_queries WHERE query_id = ?';
        $values = array((int)$queryId);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::deleteQuery(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        return $this->_write_db->query($query, $values);
    }

    function isCategory($category, $state_id)
    {
        $query = 'SELECT 1 FROM whups_states '
            . 'WHERE state_id = ? AND state_category = ?';
        $values = array((int)$state_id, $category);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::isCategory(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        return $this->_db->getOne($query, $values);
    }

    function getAllPriorityInfo($type)
    {
        $query = 'SELECT priority_id, priority_name, priority_description '
            . 'FROM whups_priorities WHERE type_id = ? ORDER BY priority_id';
        $values = array((int)$type);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getAllPriorityInfo(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');

        $info = $this->_db->getAll($query, $values, DB_FETCHMODE_ASSOC);
        if (is_a($info, 'PEAR_Error')) {
            Horde::logMessage($info, 'ERR');
            return $info;
        }

        return Horde_String::convertCharset($info, $this->_params['charset']);
    }

    function getPriorities($type = null)
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
        Horde::logMessage(
            sprintf('SQL Query by Whups_Driver_sql::getPriorities(): query="%s"',
                    $query), 'DEBUG');

        $priorities = $this->_db->getAssoc($query);
        if (is_a($priorities, 'PEAR_Error')) {
            Horde::logMessage($priorities, 'ERR');
            return $priorities;
        }

        if (empty($type)) {
            foreach ($priorities as $id => $priority) {
                $priorities[$id] = $priority[0] . ' (' . $priority[2] . ')';
            }
        }

        return Horde_String::convertCharset($priorities, $this->_params['charset']);
    }

    function getPriority($priorityId)
    {
        if (empty($priorityId)) {
            return false;
        }
        $query = 'SELECT priority_id, priority_name, priority_description, '
            . 'type_id FROM whups_priorities WHERE priority_id = ?';
        $values = array((int)$priorityId);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getPriority(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $priority = $this->_db->getAssoc($query, false, $values);
        if (is_a($priority, 'PEAR_Error')) {
            Horde::logMessage($priority, 'ERR');
            return $priority;
        }

        $priority = Horde_String::convertCharset($priority, $this->_params['charset']);
        return array('id' => $priorityId,
                     'name' => isset($priority[$priorityId][0])
                         ? $priority[$priorityId][0] : '',
                     'description' => isset($priority[$priorityId][1])
                         ? $priority[$priorityId][1] : '',
                     'type' => isset($priority[$priorityId][2])
                         ? $priority[$priorityId][2] : '');
    }

    function updatePriority($priorityId, $name, $description)
    {
        $query = 'UPDATE whups_priorities' .
                 ' SET priority_name = ?, priority_description = ?' .
                 ' WHERE priority_id = ?';
        $values = array(Horde_String::convertCharset($name, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        Horde_String::convertCharset($description, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        $priorityId);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::updatePriority(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        return $this->_write_db->query($query, $values);
    }

    function getDefaultPriority($type)
    {
        $query = 'SELECT priority_id FROM whups_priorities '
            . 'WHERE priority_default = 1 AND type_id = ?';
        $values = array($type);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getDefaultPriority(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        return $this->_db->getOne($query, $values);
    }

    function setDefaultPriority($type, $priority)
    {
        $query = 'UPDATE whups_priorities SET priority_default = 0 '
            . 'WHERE type_id = ?';
        $values = array($type);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::setDefaultPriority(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }
        $query = 'UPDATE whups_priorities SET priority_default = 1 '
            . 'WHERE priority_id = ?';
        $values = array($priority);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::setDefaultPriority(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        return $this->_write_db->query($query, $values);
    }

    function deletePriority($priorityId)
    {
        $query = 'DELETE FROM whups_priorities WHERE priority_id = ?';
        $values = array($priorityId);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::deletePriority(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        return $this->_write_db->query($query, $values);
    }

    function getVersionInfoInternal($queue)
    {
        $query = 'SELECT version_id, version_name, version_description, version_active '
            . 'FROM whups_versions WHERE queue_id = ?'
            . ' ORDER BY version_id';
        $values = array($queue);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getVersionInfoInternal(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');

        $info = $this->_db->getAll($query, $values, DB_FETCHMODE_ASSOC);
        if (is_a($info, 'PEAR_Error')) {
            Horde::logMessage($info, 'ERR');
            return $info;
        }

        return Horde_String::convertCharset($info, $this->_params['charset']);
    }

    function getVersionInternal($versionId)
    {
        if (empty($versionId)) {
            return false;
        }
        $query = 'SELECT version_id, version_name, version_description, version_active'.
                 ' FROM whups_versions WHERE version_id = ?';
        $values = array($versionId);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getVersionInternal(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $version = $this->_db->getAssoc($query, false, $values);
        if (is_a($version, 'PEAR_Error')) {
            Horde::logMessage($version, 'ERR');
            return $version;
        }

        $version = Horde_String::convertCharset($version, $this->_params['charset']);
        return array('id' => $versionId,
                     'name' => isset($version[$versionId][0])
                         ? $version[$versionId][0] : '',
                     'description' => isset($version[$versionId][1])
                         ? $version[$versionId][1] : '',
                     'active' => !empty($version[$versionId][2]));
    }

    function updateVersion($versionId, $name, $description, $active)
    {
        $query = 'UPDATE whups_versions SET version_name = ?, '
            . 'version_description = ?, version_active = ? '
            . 'WHERE version_id = ?';
        $values = array(Horde_String::convertCharset($name, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        Horde_String::convertCharset($description, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        (int)$active,
                        (int)$versionId);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::updateVersion(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        return $this->_write_db->query($query, $values);
    }

    function deleteVersion($versionId)
    {
        $query = 'DELETE FROM whups_versions WHERE version_id = ?';
        $values = array($versionId);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::deleteVersion(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        return $this->_write_db->query($query, $values);
    }

    /**
     * Returns all available form replies for a ticket type.
     *
     * @param integer $type  A type id.
     *
     * @return array  A hash with reply ids as keys and reply hashes as values.
     */
    function getReplies($type)
    {
        $query = 'SELECT reply_id, reply_name, reply_text '
            . 'FROM whups_replies WHERE type_id = ? ORDER BY reply_name';
        $values = array((int)$type);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getReplies(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');

        $info = $this->_db->getAssoc($query, false, $values, DB_FETCHMODE_ASSOC);
        if (is_a($info, 'PEAR_Error')) {
            Horde::logMessage($info, 'ERR');
            return $info;
        }

        return Horde_String::convertCharset($info, $this->_params['charset']);
    }

    /**
     * Returns a form reply information hash.
     *
     * @param integer $reply_id  A form reply id.
     *
     * @return array  A hash with all form reply information.
     */
    function getReply($reply_id)
    {
        $query = 'SELECT reply_name, reply_text, type_id '
            . 'FROM whups_replies WHERE reply_id = ?';
        $values = array((int)$reply_id);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getReply(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $reply = $this->_db->getRow($query, $values, DB_FETCHMODE_ASSOC);
        if (is_a($reply, 'PEAR_Error')) {
            Horde::logMessage($reply, 'ERR');
            return $reply;
        }

        return Horde_String::convertCharset($reply, $this->_params['charset']);
    }

    /**
     * Updates a form reply in the backend.
     *
     * @param integer $reply  A reply id.
     * @param string $name    The new reply name.
     * @param string $text    The new reply text.
     */
    function updateReply($reply, $name, $text)
    {
        $query = 'UPDATE whups_replies SET reply_name = ?, '
            . 'reply_text = ? WHERE reply_id = ?';
        $values = array(Horde_String::convertCharset($name, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        Horde_String::convertCharset($text, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        $reply);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::updateReply(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
        }
        return $result;
    }

    /**
     * Deletes a form reply from the backend.
     *
     * @param integer $reply  A reply id.
     */
    function deleteReply($reply)
    {
        $query = 'DELETE FROM whups_replies WHERE reply_id = ?';
        $values = array((int)$reply);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::deleteReply(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }
        return parent::deleteReply($reply);
    }

    function addListener($ticket, $user)
    {
        $query = 'INSERT INTO whups_ticket_listeners (ticket_id, user_uid)' .
            ' VALUES (?, ?)';
        $values = array($ticket, $user);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::addListener(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');

        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return true;
    }

    function deleteListener($ticket, $user)
    {
        $query = 'DELETE FROM whups_ticket_listeners WHERE ticket_id = ?' .
            ' AND user_uid = ?';
        $values = array($ticket, $user);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::deleteListener(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');

        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return true;
    }

    function getListeners($ticket, $withowners = true, $withrequester = true,
                          $withresponsible = false)
    {
        $query = 'SELECT DISTINCT l.user_uid' .
                 ' FROM whups_ticket_listeners l, whups_tickets t' .
                 ' WHERE (l.ticket_id = ?)';
        $values = array($ticket);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getListeners(): query="%s"; values="%s"',
                    $query, implode(',', $values)),' DEBUG');
        $users = $this->_db->getCol($query, 0, $values);
        if (is_a($users, 'PEAR_Error')) {
            Horde::logMessage($users, 'ERR');
            return array();
        }
        $tinfo = $this->getTicketDetails($ticket);
        if (is_a($tinfo, 'PEAR_Error')) {
            Horde::logMessage($tinfo, 'ERR');
            return array();
        }
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

    function addAttributeDesc($type_id, $name, $desc, $type, $params, $required)
    {
        // TODO: Make sure we're not adding a duplicate here (can be
        // done in the db schema).

        // FIXME: This assumes that $type_id is a valid type id.
        $new_id = $this->_write_db->nextId('whups_attributes_desc');
        if (is_a($new_id, 'PEAR_Error')) {
            Horde::logMessage($new_id, 'ERR');
            return $new_id;
        }

        $query = 'INSERT INTO whups_attributes_desc '
            . '(attribute_id, type_id, attribute_name, attribute_description, '
            . 'attribute_type, attribute_params, attribute_required)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?)';
        $values = array($new_id,
                        $type_id,
                        Horde_String::convertCharset($name, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        Horde_String::convertCharset($desc, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        $type,
                        serialize(
                            Horde_String::convertCharset($params, $GLOBALS['registry']->getCharset(),
                                                   $this->_params['charset'])),
                        (int)($required == 'on'));

        Horde::logMessage(
            sprintf('Whups_Driver_sql::addAttributeDesc(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return $new_id;
    }

    function updateAttributeDesc($attribute_id, $newname, $newdesc, $newtype,
                                 $newparams, $newrequired)
    {
        $query = 'UPDATE whups_attributes_desc '
            . 'SET attribute_name = ?, attribute_description = ?, '
            . 'attribute_type = ?, attribute_params = ?, '
            . 'attribute_required = ? WHERE attribute_id = ?';
        $values = array(Horde_String::convertCharset($newname, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        Horde_String::convertCharset($newdesc, $GLOBALS['registry']->getCharset(),
                                               $this->_params['charset']),
                        $newtype,
                        serialize(
                            Horde_String::convertCharset($newparams,
                                                   $GLOBALS['registry']->getCharset(),
                                                   $this->_params['charset'])),
                        (int)($newrequired == 'on'),
                        $attribute_id);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::updateAttributeDesc(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        return $this->_write_db->query($query, $values);
    }

    function deleteAttributeDesc($attribute_id)
    {
        // FIXME: Which one of these returns the error, or do we have to check
        // all of them for errors?
        $this->_write_db->autoCommit(false);
        $query = 'DELETE FROM whups_attributes_desc WHERE attribute_id = ?';
        $values = array($attribute_id);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::deleteAttributeDesc(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $this->_write_db->query($query, $values);
        $query = 'DELETE FROM whups_attributes WHERE attribute_id = ?';
        $values = array($attribute_id);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::deleteAttributeDesc(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $this->_write_db->query($query, $values);
        $this->_write_db->commit();
        $this->_write_db->autoCommit(true);

        return true;
    }

    function getAllAttributes()
    {
        $query = 'SELECT attribute_id, attribute_name, attribute_description, '
            . 'type_id FROM whups_attributes_desc';
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getAllAttributes(): query="%s"', $query), 'DEBUG');

        $attributes = $this->_db->getAssoc($query, false, array(),
                                           DB_FETCHMODE_ASSOC);
        if (is_a($attributes, 'PEAR_Error')) {
            Horde::logMessage($attributes, 'ERR');
            return $attributes;
        }

        return Horde_String::convertCharset($attributes, $this->_params['charset']);
    }

    function getAttributeDesc($attribute_id)
    {
        if (empty($attribute_id)) {
            return false;
        }

        $query = 'SELECT attribute_id, attribute_name, attribute_description, '
            . 'attribute_type, attribute_params, attribute_required '
            . 'FROM whups_attributes_desc WHERE attribute_id = ?';
        $values = array($attribute_id);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getAttributeDesc(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $attribute = $this->_db->getRow($query, $values, DB_FETCHMODE_ASSOC);
        if (is_a($attribute, 'PEAR_Error')) {
            Horde::logMessage($attribute, 'ERR');
            return $attribute;
        }

        return array(
            'id' => $attribute_id,
            'attribute_name' => Horde_String::convertCharset(
                $attribute['attribute_name'], $this->_params['charset']),
            'attribute_description' => Horde_String::convertCharset(
                $attribute['attribute_description'], $this->_params['charset']),
            'attribute_type' => empty($attribute['attribute_type'])
                ? 'text' : $attribute['attribute_type'],
            'attribute_params' => Horde_String::convertCharset(
                @unserialize($attribute['attribute_params']),
                $this->_params['charset']),
            'attribute_required' => (bool)$attribute['attribute_required']);
    }

    function getAttributeName($attribute_id)
    {
        $query = 'SELECT attribute_name FROM whups_attributes_desc '
            . 'WHERE attribute_id = ?';
        $values = array($attribute_id);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getAttributeName(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');

        $name = $this->_db->getOne($query, $values);
        if (is_a($name, 'PEAR_Error')) {
            Horde::logMessage($name, 'ERR');
            return $name;
        }

        return Horde_String::convertCharset($name, $this->_params['charset']);
    }

    function _getAttributesForType($type = null, $raw = false)
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
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getAttributesForType(): query="%s"',
                    $query), 'DEBUG');

        $attributes = $this->_db->getAssoc($query, false, null,
                                           DB_FETCHMODE_ASSOC);
        if (is_a($attributes, 'PEAR_Error')) {
            Horde::logMessage($attributes, 'ERR');
            return $attributes;
        }

        foreach ($attributes as $id => $attribute) {
            if (empty($type) && !$raw) {
                $attributes[$id]['attribute_name'] =
                    $attribute['attribute_name']
                    . ' (' . $attribute['type_name'] . ')';
            }
            $attributes[$id]['attribute_name'] = Horde_String::convertCharset(
                $attribute['attribute_name'], $this->_params['charset']);
            $attributes[$id]['attribute_description'] = Horde_String::convertCharset(
                $attribute['attribute_description'], $this->_params['charset']);
            $attributes[$id]['attribute_type'] =
                empty($attribute['attribute_type'])
                ? 'text' : $attribute['attribute_type'];
            $attributes[$id]['attribute_params'] = Horde_String::convertCharset(
                @unserialize($attribute['attribute_params']),
                $this->_params['charset']);
            $attributes[$id]['attribute_required'] =
                (bool)$attribute['attribute_required'];
        }

        return $attributes;
    }

    function getAttributeNamesForType($type_id)
    {
        if (empty($type_id)) {
            return array();
        }
        $query = 'SELECT attribute_name FROM whups_attributes_desc '
            .'WHERE type_id = ? ORDER BY attribute_name';
        $values = array((int)$type_id);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getAttributeNamesForType(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');

        $names = $this->_db->getAll($query, $values, DB_FETCHMODE_ASSOC);
        if (is_a($names, 'PEAR_Error')) {
            Horde::logMessage($names, 'ERR');
            return $names;
        }

        return Horde_String::convertCharset($names, $this->_params['charset']);
    }

    function getAttributeInfoForType($type_id)
    {
        $query = 'SELECT attribute_id, attribute_name, attribute_description '
            . 'FROM whups_attributes_desc WHERE type_id = ? '
            . 'ORDER BY attribute_id';
        $values = array((int)$type_id);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getAttributeNamesForType(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');

        $info = $this->_db->getAll($query, $values, DB_FETCHMODE_ASSOC);
        if (is_a($info, 'PEAR_Error')) {
            Horde::logMessage($info, 'ERR');
            return $info;
        }

        return Horde_String::convertCharset($info, $this->_params['charset']);
    }

    function _setAttributeValue($ticket_id, $attribute_id, $attribute_value)
    {
        $db_attribute_value = Horde_String::convertCharset((string)$attribute_value,
                                                     $GLOBALS['registry']->getCharset(),
                                                     $this->_params['charset']);

        $this->_write_db->autoCommit(false);
        $query = 'DELETE FROM whups_attributes '
            . 'WHERE ticket_id = ? AND attribute_id = ?';
        $values = array($ticket_id, $attribute_id);
        Horde::logMessage(
            sprintf('Whups_Driver_sql::_setAttributeValue(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');
        $this->_write_db->query($query, $values);

        if (!empty($attribute_value)) {
            $query = 'INSERT INTO whups_attributes'
                . '(ticket_id, attribute_id, attribute_value)'
                . ' VALUES (?, ?, ?)';
            $values = array($ticket_id, $attribute_id, $db_attribute_value);
            Horde::logMessage(
                sprintf('Whups_Driver_sql::_setAttributeValue(): query="%s"; values="%s"',
                        $query, implode(',', $values)), 'DEBUG');
            $inserted = $this->_write_db->query($query, $values);
            if (is_a($inserted, 'PEAR_Error')) {
                Horde::logMessage($inserted, 'ERR');
                $this->_write_db->rollback();
                $this->_write_db->autoCommit(true);
                return $inserted;
            }
        }

        $this->_write_db->commit();
        $this->_write_db->autoCommit(true);
    }

    function getTicketAttributes($ticket_id)
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
            Horde::logMessage(
                sprintf('Whups_Driver_sql::getAttributes(): query="%s"', $query), 'DEBUG');
            $attributes = $this->_db->getAll($query, $ticket_id,
                                             DB_FETCHMODE_ASSOC);
        } else {
            $query = 'SELECT attribute_id, attribute_value' .
                ' FROM whups_attributes WHERE ticket_id = ?';
            $values = array((int)$ticket_id);
            Horde::logMessage(
                sprintf('Whups_Driver_sql::getTicketAttributes(): query="%s"; values="%s"',
                        $query, implode(',', $values)), 'DEBUG');

            $attributes = $this->_db->getAssoc($query, false, $values);
        }

        if (is_a($attributes, 'PEAR_Error')) {
            Horde::logMessage($attributes, 'ERR');
            return $attributes;
        }

        return Horde_String::convertCharset($attributes, $this->_params['charset']);
    }

    function getTicketAttributesWithNames($ticket_id)
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
            Horde::logMessage(
                sprintf('SQL Query by Whups_Driver_sql::getAttributes(): query="%s"',
                        $query), 'DEBUG');
            $attributes = $this->_db->getAll($query, $ticket_id,
                                             DB_FETCHMODE_ASSOC);
        } else {
            $query = 'SELECT d.attribute_name, a.attribute_value '
                . 'FROM whups_attributes a INNER JOIN whups_attributes_desc d '
                . 'ON (d.attribute_id = a.attribute_id)'
                . 'WHERE a.ticket_id = ? ORDER BY d.attribute_name';
            $values = array((int)$ticket_id);
            Horde::logMessage(
                sprintf('Whups_Driver_sql::getTicketAttributesWithNames(): query="%s"; values="%s"',
                        $query, implode(',', $values)), 'DEBUG');

            $attributes = $this->_db->getAssoc($query, false, $values);
        }
        if (is_a($attributes, 'PEAR_Error')) {
            Horde::logMessage($attributes, 'ERR');
            return $attributes;
        }

        return Horde_String::convertCharset($attributes, $this->_params['charset']);
    }

    function _getAllTicketAttributesWithNames($ticket_id)
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
        Horde::logMessage(
            sprintf('Whups_Driver_sql::getAllTicketAttributesWithNames(): query="%s"; values="%s"',
                    $query, implode(',', $values)), 'DEBUG');

        $attributes = $this->_db->getAll($query, $values, DB_FETCHMODE_ASSOC);
        if (is_a($attributes, 'PEAR_Error')) {
            Horde::logMessage($attributes, 'ERR');
            return $attributes;
        }

        foreach ($attributes as $id => $attribute) {
            $attributes[$id]['attribute_name'] = Horde_String::convertCharset(
                $attribute['attribute_name'], $this->_params['charset']);
            $attributes[$id]['attribute_description'] = Horde_String::convertCharset(
                $attribute['attribute_description'], $this->_params['charset']);
            $attributes[$id]['attribute_type'] =
                empty($attribute['attribute_type'])
                ? 'text' : $attribute['attribute_type'];
            $attributes[$id]['attribute_params'] = Horde_String::convertCharset(
                @unserialize($attribute['attribute_params']),
                $this->_params['charset']);
            $attributes[$id]['attribute_required'] =
                (bool)$attribute['attribute_required'];
        }

        return $attributes;
    }

    function getOwners($ticketId)
    {
        if (is_array($ticketId)) {
            // No need to run a query for an empty array, and it would
            // result in an invalid SQL query anyway.
            if (!count($ticketId)) {
                return array();
            }

            $query = 'SELECT ticket_id AS id, ticket_owner AS owner '
                . 'FROM whups_ticket_owners WHERE ticket_id '
                . 'IN (' . str_repeat('?, ', count($ticketId) - 1) . '?)';
            $values = $ticketId;
            Horde::logMessage(
                sprintf('Whups_Driver_sql::getOwners(): query="%s"; values="%s"',
                        $query, implode(',', $values)), 'DEBUG');
            return $this->_db->getAll($query, $values, DB_FETCHMODE_ASSOC);
        } else {
            $query = 'SELECT ticket_owner, ticket_owner '
                . 'FROM whups_ticket_owners WHERE ticket_id = ?';
            $values = array((int)$ticketId);
            Horde::logMessage(
                sprintf('Whups_Driver_sql::getOwners(): query="%s"; values="%s"',
                        $query, implode(',', $values)), 'DEBUG');
            return $this->_db->getAssoc($query, false, $values);
        }
    }

    function updateLog($ticket_id, $user, $changes = array(),
                       $transactionId = null)
    {
        if (is_null($transactionId)) {
            $transactionId = $this->newTransaction($user);
            if (is_a($transactionId, 'PEAR_Error')) {
                return $transactionId;
            }
        }

        foreach ($changes as $type => $value) {
            $log_id = $this->_write_db->nextId('whups_logs');
            if (is_a($log_id, 'PEAR_Error')) {
                return $log_id;
            }

            $query = 'INSERT INTO whups_logs (log_id, transaction_id, '
                . 'ticket_id, log_timestamp, user_id, log_type, log_value, '
                . 'log_value_num) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
            $values = array(
                (int)$log_id,
                (int)$transactionId,
                (int)$ticket_id,
                time(),
                (string)$user,
                $type,
                Horde_String::convertCharset((string)$value, $GLOBALS['registry']->getCharset(),
                                       $this->_params['charset']),
                (int)$value);
            Horde::logMessage(
                sprintf('Whups_Driver_sql::updateLog(): query="%s"; values="%s"',
                        $query, implode(',', $values)), 'DEBUG');
            $result = $this->_write_db->query($query, $values);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }
        }

        return $transactionId;
    }

    /**
     * Create a new transaction id for associating related entries in
     * the whups_logs table.
     *
     * @return integer New transaction id.
     */
    function newTransaction($creator, $creator_email = null)
    {
        $transactionId = $this->_write_db->nextId('whups_transactions');
        if (is_a($transactionId, 'PEAR_Error')) {
            return $transactionId;
        }

        if ((empty($creator) || $creator < 0) && !empty($creator_email)) {
            $creator = '-' . $transactionId . '_transaction';
            $query = 'INSERT INTO whups_guests (guest_id, guest_email)'
                . ' VALUES (?, ?)';
            $values = array((string)$creator, $creator_email);
            Horde::logMessage(
                sprintf('Whups_Driver_sql::newTransaction(): query="%s"; values="%s"',
                        $query, implode(',', $values)), 'DEBUG');
            $result = $this->_write_db->query($query, $values);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, 'ERR');
                return $result;
            }
        }

        return $transactionId;
    }

    /**
     * Return the db object we're using.
     *
     * return DB Database object.
     */
    function getDb()
    {
        return $this->_db;
    }

    /**
     */
    function initialise()
    {
        Horde::assertDriverConfig($this->_params, 'tickets',
            array('phptype'));

        if (!isset($this->_params['database'])) {
            $this->_params['database'] = '';
        }
        if (!isset($this->_params['username'])) {
            $this->_params['username'] = '';
        }
        if (!isset($this->_params['hostspec'])) {
            $this->_params['hostspec'] = '';
        }

        /* Connect to the SQL server using the supplied parameters. */
        require_once 'DB.php';
        $this->_write_db = &DB::connect($this->_params,
                                        array('persistent' => !empty($this->_params['persistent'])));
        if (is_a($this->_write_db, 'PEAR_Error')) {
            Horde::fatal($this->_write_db, __FILE__, __LINE__);
        }

        // Set DB portability options.
        switch ($this->_write_db->phptype) {
        case 'mssql':
            $this->_write_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
            break;
        default:
            $this->_write_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
        }

        /* Check if we need to set up the read DB connection
         * seperately. */
        if (!empty($this->_params['splitread'])) {
            $params = array_merge($this->_params, $this->_params['read']);
            $this->_db = &DB::connect($params,
                                      array('persistent' => !empty($params['persistent'])));
            if (is_a($this->_db, 'PEAR_Error')) {
                Horde::fatal($this->_db, __FILE__, __LINE__);
            }

            // Set DB portability options.
            switch ($this->_db->phptype) {
            case 'mssql':
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
                break;
            default:
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
            }
        } else {
            /* Default to the same DB handle for reads. */
            $this->_db =& $this->_write_db;
        }

        return true;
    }

    function _generateWhere($table, $fields, &$info, $type)
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
                            $clauses[] = "$table.$field = " . $this->_write_db->quote($pprop);
                        }
                    }
                    if (count($clauses)) {
                        $where = $this->_addWhere($where, true, implode(' OR ', $clauses));
                    }
                } else {
                    $success = @settype($prop, $type);
                    $where = $this->_addWhere($where, !is_null($prop) && $success, "$table.$field = " . $this->_write_db->quote($prop));
                }
            }
        }

        foreach ($fields as $field) {
            if (isset($info["not$field"])) {
                $prop = $info["not$field"];

                if (strpos($prop, ',') === false) {
                    $success = @settype($prop, $type);
                    $where = $this->_addWhere($where, $prop && $success, "$table.$field <> " . $this->_write_db->quote($prop));
                } else {
                    $set = explode(',', $prop);

                    foreach ($set as $prop) {
                        $success = @settype($prop, $type);
                        $where = $this->_addWhere($where, $prop && $success, "$table.$field <> " . $this->_write_db->quote($prop));
                    }
                }
            }
        }

        return $where;
    }

    function _mapFields(&$info)
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

    function _addWhere($where, $condition, $clause, $conjunction = 'AND')
    {
        if (!empty($condition)) {
            if (!empty($where)) {
                $where .= " $conjunction ";
            }

            $where .= "($clause)";
        }

        return $where;
    }

    function _addDateWhere($where, $data, $type)
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

    function _prefixTableToColumns($table, $columns)
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
