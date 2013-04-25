<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * The abstract class that all sentmail implementations inherit from.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @property-read integer $limit_period  If limiting recipients per time
 *                                       period, how many hours should this
 *                                       period last?
 * @property-read integer $threshold  How many days should old log entries be
 *                                    kept?
 */
abstract class IMP_Sentmail
{
    /* Action constants. */
    const NEWMSG = 'new';
    const REPLY = 'reply';
    const FORWARD = 'forward';
    const REDIRECT = 'redirect';
    const MDN = 'mdn';

    /**
     * Hash containing configuration parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters the driver needs.
     *
     * @throws IMP_Exception
     */
    public function __construct(array $params = array())
    {
        $this->_params = array_merge($this->_params, $params);
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'limit_period':
        case 'threshold':
            return isset($this->_params[$name])
                ? intval($this->_params[$name])
                : 0;
        }
    }

    /**
     * Logs an attempt to send a message.
     *
     * @param integer $action           Why the message was sent (IMP_Sentmail
     *                                  constant).
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
            foreach (IMP::parseAddressList($addresses) as $recipient) {
                $this->_log($action, $message_id, $recipient->bare_address, $success);
            }
        }
    }

    /**
     * Garbage collect log entries.
     */
    public function gc()
    {
        $this->_deleteOldEntries(time() - ($this->threshold * 86400));
    }

    /**
     * Logs an attempt to send a message per recipient.
     *
     * @param integer $action     Why the message was sent (IMP_Sentmail
     *                            constant).
     * @param string $message_id  The Message-ID.
     * @param string $recipient   A message recipient.
     * @param boolean $success    Whether the attempt was successful.
     */
    abstract protected function _log($action, $message_id, $recipient,
                                     $success);

    /**
     * Returns the favourite recipients.
     *
     * @param integer $limit  Return this number of recipients.
     * @param mixed $filter   A list of messages types that should be
     *                        returned. Null returns all message types.
     *
     * @return array  A list with the $limit most favourite recipients.
     * @throws IMP_Exception
     */
    abstract public function favouriteRecipients($limit, $filter = null);

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
