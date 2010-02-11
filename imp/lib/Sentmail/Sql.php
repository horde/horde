<?php
/**
 * IMP_Sentmail implementation for PHP's PEAR database abstraction layer.
 *
 * Required values for $params:
 * <pre>
 * 'phptype'       The database type (e.g. 'pgsql', 'mysql', etc.).
 * 'table'         The name of the foo table in 'database'.
 * </pre>
 *
 * Required by some database implementations:
 * <pre>
 * 'database'      The name of the database.
 * 'hostspec'      The hostname of the database server.
 * 'protocol'      The communication protocol ('tcp', 'unix', etc.).
 * 'username'      The username with which to connect to the database.
 * 'password'      The password associated with 'username'.
 * 'options'       Additional options to pass to the database.
 * 'tty'           The TTY on which to connect to the database.
 * 'port'          The port on which to connect to the database.
 * </pre>
 *
 * The table structure can be created by the scripts/sql/imp_sentmail.sql
 * script.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package IMP
 */
class IMP_Sentmail_Sql extends IMP_Sentmail
{
    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    protected $_db;

    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     *
     * @throws Horde_Exception
     */
    protected function __construct($params = array())
    {
        parent::__construct($params);

        Horde::assertDriverConfig($this->_params, 'storage', array('phptype', 'table'));

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
        $this->_db = DB::connect($this->_params,
                                 array('persistent' => !empty($this->_params['persistent']),
                                       'ssl' => !empty($this->_params['ssl'])));
        if ($this->_db instanceof PEAR_Error) {
            throw new Horde_Exception_Prior($this->_db);
        }

        /* Set DB portability options. */
        switch ($this->_db->phptype) {
        case 'mssql':
            $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
            break;

        default:
            $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
        }
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
                        (int)$success);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('IMP_Sentmail_Sql::_log(): %s', $query),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Execute the query. */
        $result = $this->_db->query($query, $values);

        /* Log errors. */
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
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
     * @throws Horde_Exception
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
        Horde::logMessage(sprintf('IMP_Sentmail_Sql::favouriteRecipients(): %s', $query),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Execute the query. */
        $recipients = $this->_db->getAll($query);
        if ($recipients instanceof PEAR_Error) {
            Horde::logMessage($recipients, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Exception_Prior($recipients);
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
     * @throws Horde_Exception
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
        Horde::logMessage(sprintf('IMP_Sentmail_Sql::numberOfRecipients(): %s', $query),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Execute the query. */
        $recipients = $this->_db->getOne($query, array(time() - $hours * 3600));
        if ($recipients instanceof PEAR_Error) {
            Horde::logMessage($recipients, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Exception_Prior($recipients);
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
        Horde::logMessage(sprintf('IMP_Sentmail_Sql::_deleteOldEntries(): %s', $query),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Execute the query. */
        $result = $this->_db->query($query, array($before));
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
        }
    }

}
