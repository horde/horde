<?php
/**
 * IMP_Sentmail implementation for PHP's PEAR database abstraction layer.
 *
 * The table structure can be created by the scripts/sql/imp_sentmail.sql
 * script.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  IMP
 */
class IMP_Sentmail_Sql extends IMP_Sentmail_Driver
{
    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    protected $_db = '';

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $_db if a separate write database is not required.
     *
     * @var DB
     */
    protected $_write_db;

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     * 'db' - (DB) [REQUIRED] The DB instance.
     * 'table' - (string) The name of the sentmail table.
     *           DEFAULT: 'imp_sentmail'
     * 'write_db' - (DB) The write DB instance.
     * </pre>
     *
     * @throws IMP_Exception
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['db'])) {
            throw new IMP_Exception('Missing db parameter.');
        }
        $this->_db = $params['db'];

        $this->_write_db = isset($params['write_db'])
            ? $params['write_db']
            : $this->_db;

        unset($params['db'], $params['write_db']);

        $params = array_merge(array(
            'table' => 'imp_sentmail'
        ), $params);

        parent::__construct($params);
    }

    /**
     * Logs an attempt to send a message per recipient.
     *
     * @param string $action      Why the message was sent, i.e. "new",
     *                            "reply", "forward", etc.
     * @param string $message_id  The Message-ID.
     * @param string $recipients  A message recipient.
     * @param boolean $success    Whether the attempt was successful.
     */
    protected function _log($action, $message_id, $recipient, $success)
    {
        /* Build the SQL query. */
        $query = sprintf('INSERT INTO %s (sentmail_id, sentmail_who, sentmail_ts, sentmail_messageid, sentmail_action, sentmail_recipient, sentmail_success) VALUES (?, ?, ?, ?, ?, ?, ?)',
                         $this->_params['table']);
        $values = array($this->_db->nextId($this->_params['table']),
                        Horde_Auth::getAuth(),
                        time(),
                        $message_id,
                        $action,
                        $recipient,
                        intval($success));

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('IMP_Sentmail_Sql::_log(): %s', $query), 'DEBUG');

        /* Execute the query. */
        $result = $this->_write_db->query($query, $values);

        /* Log errors. */
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
        }
    }

    /**
     * Returns the most favourite recipients.
     *
     * @param integer $limit  Return this number of recipients.
     * @param array $filter   A list of messages types that should be returned.
     *                        A value of null returns all message types.
     *
     * @return array  A list with the $limit most favourite recipients.
     * @throws IMP_Exception
     */
    public function favouriteRecipients($limit,
                                        $filter = array('new', 'forward', 'reply', 'redirect'))
    {
        /* Build the SQL query. */
        $where = '';
        if (!empty($filter)) {
            $filter = array_map(array($this->_db, 'quote'), $filter);
            $where = sprintf(' AND sentmail_action in (%s)',
                             implode(', ', $filter));
        }
        $query = sprintf('SELECT sentmail_recipient, count(*) AS sentmail_count FROM %s WHERE sentmail_who = %s AND sentmail_success = 1%s GROUP BY sentmail_recipient ORDER BY sentmail_count DESC LIMIT %d',
                         $this->_params['table'],
                         $this->_db->quote(Horde_Auth::getAuth()),
                         $where,
                         $limit);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('IMP_Sentmail_Sql::favouriteRecipients(): %s', $query), 'DEBUG');

        /* Execute the query. */
        $recipients = $this->_db->getAll($query);
        if ($recipients instanceof PEAR_Error) {
            Horde::logMessage($recipients, 'ERR');
            throw new IMP_Exception($recipients);
        }

        /* Extract email addresses. */
        $favourites = array();
        foreach ($recipients as $recipient) {
            $favourites[] = reset($recipient);
        }

        return $favourites;
    }

    /**
     * Returns the number of recipients within a certain time period.
     *
     * @param integer $hours  Time period in hours.
     * @param boolean $user   Return the number of recipients for the current
     *                        user?
     *
     * @return integer  The number of recipients in the given time period.
     * @throws IMP_Exception
     */
    public function numberOfRecipients($hours, $user = false)
    {
        /* Build the SQL query. */
        $query = sprintf('SELECT COUNT(*) FROM %s WHERE sentmail_ts > ?',
                         $this->_params['table']);
        if ($user) {
            $query .= sprintf(' AND sentmail_who = %s', $this->_db->quote(Horde_Auth::getAuth()));
        }

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('IMP_Sentmail_Sql::numberOfRecipients(): %s', $query), 'DEBUG');

        /* Execute the query. */
        $recipients = $this->_db->getOne($query, array(time() - $hours * 3600));
        if ($recipients instanceof PEAR_Error) {
            Horde::logMessage($recipients, 'ERR');
            throw new IMP_Exception($recipients);
        }

        return $recipients;
    }

    /**
     * Deletes all log entries older than a certain date.
     *
     * @param integer $before  Unix timestamp before that all log entries
     *                         should be deleted.
     */
    protected function _deleteOldEntries($before)
    {
        /* Build the SQL query. */
        $query = sprintf('DELETE FROM %s WHERE sentmail_ts < ?',
                         $this->_params['table']);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('IMP_Sentmail_Sql::_deleteOldEntries(): %s', $query), 'DEBUG');

        /* Execute the query. */
        $result = $this->_write_db->query($query, array($before));
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
        }
    }

}
