<?php
/**
 * Copyright 2003-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2003-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Base class implementing logging of responses to e-mail messages.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2003-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Maillog
{
    /**
     * Storage driver.
     *
     * @var IMP_Maillog_Storage_Base
     */
    public $storage;

    /**
     * Constructor.
     *
     * @param IMP_Maillog_Storage_Base $storage  Storage driver.
     */
    public function __construct(IMP_Maillog_Storage_Base $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Create a log entry.
     *
     * @param mixed $msgs                An IMP_Maillog_Message object (or an
     *                                   array of objects).
     * @param IMP_Maillog_Log_Base $log  The log object.
     */
    public function log($msgs, IMP_Maillog_Log_Base $log)
    {
        foreach ((is_array($msgs) ? $msgs : array($msgs)) as $val) {
            $this->storage->saveLog($val, $log);
        }
    }

    /**
     * Retrieve history for a message.
     *
     * @param IMP_Maillog_Message $msg  A message object.
     * @param array $types              Return only these log types. If empty,
     *                                  returns all types.
     *
     * @return array  List of IMP_Maillog_Log_Base objects.
     */
    public function getLog(IMP_Maillog_Message $msg, array $types = array())
    {
        return $this->storage->getLog($msg, $types);
    }

    /**
     * Delete log entries.
     *
     * @param array $msgs  An array of message objects to delete.
     */
    public function deleteLog(array $msgs)
    {
        $this->storage->deleteLogs($msgs);
    }

    /**
     * Retrieve changes to the maillog since the provided timestamp.
     *
     * @param integer $ts  Timestamp.
     *
     * @return array  An array of messages (IMP_Maillog_Message objects)
     *                changed since the provided timestamp.
     */
    public function getChanges($ts)
    {
        return $this->storage->getChanges($ts);
    }

}
