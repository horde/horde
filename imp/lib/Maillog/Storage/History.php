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
 * Horde_History storage driver for the IMP_Maillog class.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Maillog_Storage_History extends IMP_Maillog_Storage_Base
{
    /**
     * History object.
     *
     * @var Horde_History
     */
    protected $_history;

    /**
     * User name.
     *
     * @var string
     */
    protected $_user;

    /**
     * Constructor.
     *
     * @param Horde_History $history  History object.
     * @param string $user            User name.
     */
    public function __construct(Horde_History $history, $user)
    {
        $this->_history = $history;
        $this->_user = $user;
    }

    /**
     */
    public function saveLog($msg_id, $data)
    {
        try {
            $this->_history->log(
                $this->_getUniqueHistoryId($msg_id),
                $data
            );
        } catch (Exception $e) {
            /* On error, log the error message only since informing the user is
             * just a waste of time and a potential point of confusion,
             * especially since they most likely don't even know the message
             * is being logged. */
            Horde::log(
                sprintf(
                    'Could not log message details to Horde_History. Error returned: %s',
                    $e->getMessage()
                ),
                'ERR'
            );
        }
    }

    /**
     */
    public function getLog($msg_id)
    {
        try {
            return $this->_history->getHistory(
                $this->_getUniqueHistoryId($msg_id)
            );
        } catch (Exception $e) {
            return new Horde_History_Log($msg_id);
        }
    }

    /**
     */
    public function deleteLogs($msg_ids)
    {
        $this->_history->removeByNames(array_map(
            array($this, '_getUniqueHistoryId'),
            $msg_ids
        ));
    }

    /**
     */
    public function getChanges($ts)
    {
        return preg_replace(
            '/^([^:]*:){2}/',
            '',
            array_keys($this->_history->getByTimestamp(
                '>',
                $ts,
                array(),
                $this->_getUniqueHistoryId()
            ))
        );
    }

    /**
     * Generate the unique log ID for an event.
     *
     * @param string $msgid  The Message-ID of the original message. If null,
     *                       returns the parent ID.
     *
     * @return string  The unique log ID.
     */
    protected function _getUniqueHistoryId($msgid = null)
    {
        return implode(':', array_filter(array(
            'imp',
            str_replace('.', '*', $this->_user),
            $msgid
        )));
    }

}
