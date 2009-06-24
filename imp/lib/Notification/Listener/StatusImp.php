<?php
/**
 * The IMP_Notification_Listener_StatusImp:: class extends the
 * Notification_Listener_status:: class to display the messages for
 * IMP's special message types 'imp.forward' and 'imp.reply'.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Notification
 */
class IMP_Notification_Listener_StatusImp extends Horde_Notification_Listener_Status
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_handles['imp.reply'] = true;
        $this->_handles['imp.forward'] = true;
        $this->_handles['imp.redirect'] = true;
    }

    /**
     * Outputs one message if it's an IMP message or calls the parent
     * method otherwise.
     *
     * @param array $message  One message hash from the stack.
     */
    public function getMessage($message)
    {
        $event = $this->getEvent($message);
        switch ($message['type']) {
        case 'imp.reply':
            return '<p class="notice">' . Horde::img('mail_answered.png') . '&nbsp;&nbsp;' . $event->getMessage() . '</p>';

        case 'imp.forward':
        case 'imp.redirect':
            return '<p class="notice">' . Horde::img('mail_forwarded.png') . '&nbsp;&nbsp;' . $event->getMessage() . '</p>';
        }

        return parent::getMessage($message);
    }

}
