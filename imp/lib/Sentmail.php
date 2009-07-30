<?php
/**
 * The IMP_Sentmail:: class contains all functions related to handling
 * logging of sent mail and retrieving sent mail statistics.
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package IMP
 */
class IMP_Sentmail
{
    /**
     * Hash containing configuration parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Attempts to return a concrete IMP_Sentmail instance based on $driver.
     *
     * @param string $driver  The type of the concrete IMP_Sentmail subclass
     *                        to return.  The class name is based on the
     *                        storage driver ($driver).  The code is
     *                        dynamically included.
     *
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return IMP_Sentmail  The newly created concrete IMP_Sentmail instance.
     */
    static public function factory($driver = null, $params = null)
    {
        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['sentmail']['driver'];
        }

        if (is_null($params)) {
            $params = Horde::getDriverConfig('sentmail', $driver);
        }

        $class = 'IMP_Sentmail_' . ucfirst(basename($driver));

        if (class_exists($class)) {
            try {
                return new $class($params);
            } catch (Horde_Exception $e) {}
        }

        return new IMP_Sentmail($params);
    }

    /**
     * Constructor.
     *
     * @throws Horde_Exception
     */
    protected function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Logs an attempt to send a message.
     *
     * @param string $action            Why the message was sent, i.e. "new",
     *                                  "reply", "forward", etc.
     * @param string $message_id        The Message-ID.
     * @param string|array $recipients  The list of message recipients.
     * @param boolean $success          Whether the attempt was successful.
     */
    public function log($action, $message_id, $recipients, $success = true)
    {
        if (!is_array($recipients)) {
            $recipients = array($recipients);
        }

        foreach ($recipients as $addresses) {
            $addresses = Horde_Mime_Address::bareAddress($addresses, $_SESSION['imp']['maildomain'], true);
            foreach ($addresses as $recipient) {
                $this->_log($action, $message_id, $recipient, $success);
            }
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
    }

    /**
     * Returns the favourite recipients.
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
        return array();
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
        return 0;
    }

    /**
     * Garbage collect log entries.
     */
    public function gc()
    {
        $this->_deleteOldEntries(time() - ((isset($this->_params['threshold']) ? $this->_params['threshold'] : 0) * 86400));
    }

    /**
     * Deletes all log entries older than a certain date.
     *
     * @param integer $before  Unix timestamp before that all log entries
     *                         should be deleted.
     */
    protected function _deleteOldEntries($before)
    {
    }

}
