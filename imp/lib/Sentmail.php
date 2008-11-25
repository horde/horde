<?php
/**
 * The IMP_Sentmail:: class contains all functions related to handling
 * logging of sent mail and retrieving sent mail statistics.
 *
 * Copyright 2005-2008 The Horde Project (http://www.horde.org/)
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

        $driver = basename($driver);
        $class = 'IMP_Sentmail_' . $driver;
        if (!class_exists($class)) {
            @include dirname(__FILE__) . '/Sentmail/' . $driver . '.php';
        }

        if (class_exists($class)) {
            $sentmail = new $class($params);
            $result = $sentmail->initialize();
            if (!is_a($result, 'PEAR_Error')) {
                return $sentmail;
            }
        }

        return new IMP_Sentmail();
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

        $this->gc();
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
     */
    public function numberOfRecipients($hours, $user = false)
    {
        return 0;
    }

    /**
     * Garbage collect log entries with a probability of 1%.
     */
    public function gc()
    {
        /* A 1% chance we will run garbage collection during a call. */
        if (rand(0, 99) == 0) {
            $this->_deleteOldEntries(time() - $this->_params['threshold'] * 86400);
        }
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
