<?php
/**
 * The IMP_Sentmail_Base:: class is the abstract class that all driver
 * implementations inherit from.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  IMP
 */
abstract class IMP_Sentmail_Base
{
    /**
     * Hash containing configuration parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Constructor.
     *
     * @throws IMP_Exception
     */
    public function __construct($params = array())
    {
        $this->_params = array_merge($this->_params, $params);
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
            $addresses = Horde_Mime_Address::bareAddress($addresses, $GLOBALS['session']->get('imp', 'maildomain'), true);
            foreach ($addresses as $recipient) {
                $this->_log($action, $message_id, $recipient, $success);
            }
        }
    }

    /**
     * Garbage collect log entries.
     */
    public function gc()
    {
        $this->_deleteOldEntries(time() - ((isset($this->_params['threshold']) ? $this->_params['threshold'] : 0) * 86400));
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
    abstract protected function _log($action, $message_id, $recipient,
                                     $success);

    /**
     * Returns the favourite recipients.
     *
     * @param integer $limit  Return this number of recipients.
     * @param array $filter   A list of messages types that should be returned.
     *                        A value of null returns all message types.
     *
     * @return array  A list with the $limit most favourite recipients.
     * @throws IMP_Exception
     */
    abstract public function favouriteRecipients($limit,
                                                 $filter = array('new', 'forward', 'reply', 'redirect'));

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
    abstract public function numberOfRecipients($hours, $user = false);

    /**
     * Deletes all log entries older than a certain date.
     *
     * @param integer $before  Unix timestamp before that all log entries
     *                         should be deleted.
     */
    abstract protected function _deleteOldEntries($before);

}
