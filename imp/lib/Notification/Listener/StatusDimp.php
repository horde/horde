<?php
/**
 * The IMP_Notification_Listener_StatusDimp:: class extends the
 * IMP_Notification_Listener_StatusImp:: class to return all messages instead
 * of printing them.
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Notification
 */
class IMP_Notification_Listener_StatusDimp extends IMP_Notification_Listener_StatusImp
{
    /**
     * The notified message stack.
     *
     * @var array
     */
    protected $_messageStack = array();

    /**
     * Returns all status message if there are any on the 'status' message
     * stack.
     *
     * @param array &$messageStack  The stack of messages.
     * @param array $options        An array of options.
     */
    public function notify(&$messageStack, $options = array())
    {
        while ($message = array_shift($messageStack)) {
            $event = @unserialize($message['event']);
            $this->_messageStack[] = array('type' => $message['type'],
                                           'flags' => $message['flags'],
                                           'message' => is_object($event)
                                               ? $event->getMessage()
                                               : null);
        }
    }

    /**
     * Handle every message of type dimp.*; otherwise delegate back to
     * the parent.
     *
     * @param string $type  The message type in question.
     *
     * @return boolean  Whether this listener handles the type.
     */
    public function handles($type)
    {
        return (substr($type, 0, 5) == 'dimp.') || parent::handles($type);
    }

    /**
     * Returns the message stack.
     * To return something useful, notify() needs to be called first.
     *
     * @param boolean $encode  Encode HTML entities?
     *
     * @return array  List of message hashes.
     */
    public function getStack($encode = false)
    {
        $msgs = $this->_messageStack;
        if (!$encode) {
            return $msgs;
        }

        for ($i = 0, $mcount = count($msgs); $i < $mcount; ++$i) {
            if (!in_array('content.raw', $this->getFlags($msgs[$i]))) {
                $msgs[$i]['message'] = htmlspecialchars($msgs[$i]['message'], ENT_COMPAT, NLS::getCharset());
            }
        }

        return $msgs;
    }

}
