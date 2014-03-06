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
     * @param string $msg_id  A Message-ID.
     * @param array $data    Log entry.
     */
    abstract public function saveLog($msg_id, $data);

    /**
     * Retrieve any history for the given Message-ID.
     *
     * @param string $msg_id  The Message-ID of the message.
     *
     * @return Horde_History_Log  The object containing the log information.
     */
    abstract public function getLog($msg_id);

    /**
     * Delete log entries for the given Message-IDs.
     *
     * @param array $msg_ids  Message-IDs of the messages to delete.
     */
    abstract public function deleteLogs($msg_ids);

    /**
     * Retrieve changes to the maillog since the provided timestamp.
     *
     * @param integer $ts  Timestamp.
     *
     * @return array  An array of Message-IDs changed since the provided
     *                timestamp.
     */
    abstract public function getChanges($ts);

}
