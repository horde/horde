<?php
/**
 * The IMP_Notification_Listener_StatusImp:: class extends the
 * Notification_Listener_status:: class to display the messages for
 * IMP's special message types.
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

        $image_dir = $GLOBALS['registry']->getImageDir();

        $this->_handles['imp.reply'] = array($image_dir . '/mail_answered.png', _("Reply"));
        $this->_handles['imp.forward'] = array($image_dir . '/mail_forwarded.png', _("Reply"));
        $this->_handles['imp.redirect'] = array($image_dir . '/mail_forwarded.png', _("Redirect"));
    }

}
