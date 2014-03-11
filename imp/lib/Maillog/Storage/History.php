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
    public function saveLog(
        IMP_Maillog_Message $msg, IMP_Maillog_Log_Base $log
    )
    {
        $data = array(
            'action' => $log->action,
            'ts' => $log->timestamp
        );

        switch ($log->action) {
        case 'forward':
        case 'redirect':
            $data['recipients'] = $log->recipients;
            break;
        }

        try {
            $this->_history->log($this->_getUniqueHistoryId($msg), $data);
            return true;
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

        return false;
    }

    /**
     */
    public function getLog(IMP_Maillog_Message $msg, array $filter = array())
    {
        $out = array();

        try {
            $history = $this->_history->getHistory(
                $this->_getUniqueHistoryId($msg)
            );
        } catch (Exception $e) {
            return $out;
        }

        foreach ($history as $key => $val) {
            if (!in_array($val['action'], $filter)) {
                switch ($val['action']) {
                case 'forward':
                    $ob = new IMP_Maillog_Log_Forward($val['recipients']);
                    break;

                case 'mdn':
                    $ob = new IMP_Maillog_Log_Mdn();
                    break;

                case 'redirect':
                    $ob = new IMP_Maillog_Log_Redirect($val['recipients']);
                    break;

                case 'reply':
                    $ob = new IMP_Maillog_Log_Reply();
                    break;

                case 'reply_all':
                    $ob = new IMP_Maillog_Log_Replyall();
                    break;

                case 'reply_list':
                    $ob = new IMP_Maillog_Log_Replylist();
                    break;

                default:
                    continue 2;
                }

                $ob->timestamp = $val['ts'];

                $out[] = $ob;
            }
        }

        return $out;
    }

    /**
     */
    public function deleteLogs(array $msgs)
    {
        $this->_history->removeByNames(array_map(
            array($this, '_getUniqueHistoryId'),
            $msgs
        ));
    }

    /**
     */
    public function getChanges($ts)
    {
        $msgids = preg_replace(
            '/^([^:]*:){2}/',
            '',
            array_keys($this->_history->getByTimestamp(
                '>',
                $ts,
                array(),
                $this->_getUniqueHistoryId()
            ))
        );

        $out = array();
        foreach ($msgids as $val) {
            $out[] = new IMP_Maillog_Message($val);
        }

        return $out;
    }

    /**
     * Generate the unique log ID for an event.
     *
     * @param mixed $msg  An IMP_Maillog_Message object or, if null, return
     *                    the parent ID.
     *
     * @return string  The unique log ID.
     */
    protected function _getUniqueHistoryId($msg = null)
    {
        return implode(':', array_filter(array(
            'imp',
            str_replace('.', '*', $this->_user),
            $msg ? $msg->msgid : null
        )));
    }

}
