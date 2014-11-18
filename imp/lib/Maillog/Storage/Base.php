<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Abstract storage driver for the IMP_Maillog class.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
abstract class IMP_Maillog_Storage_Base
{
    /**
     * Store a log entry.
     *
     * @param IMP_Maillog_Message $msg   Message object.
     * @param IMP_Maillog_Log_Base $log  Log entry.
     *
     * @return boolean  True if log entry was saved.
     */
    abstract public function saveLog(
        IMP_Maillog_Message $msg, IMP_Maillog_Log_Base $log
    );

    /**
     * Retrieve history for a message.
     *
     * @param IMP_Maillog_Message $msg  A message object.
     * @param array $types              Return only these log types
     *                                  (IMP_Maillog_Log_Base class names). If
     *                                  empty, returns all types.
     *
     * @return array  Array of IMP_Maillog_Log_Base objects.
     */
    abstract public function getLog(
        IMP_Maillog_Message $msg, array $types = array()
    );

    /**
     * Delete log entries.
     *
     * @param array $msgs  Message objects (IMP_Maillog_Message objects).
     */
    abstract public function deleteLogs(array $msgs);

    /**
     * Retrieve changes to the maillog since the provided timestamp.
     *
     * @param integer $ts  Timestamp.
     *
     * @return array  An array of messages (IMP_Maillog_Message objects)
     *                changed since the provided timestamp.
     */
    abstract public function getChanges($ts);

}
