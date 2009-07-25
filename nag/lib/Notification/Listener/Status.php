<?php
/**
 * The Nag_Notification_Listener_Status:: class extends the
 * Horde_Notification_Listener_Status:: class to display the messages for
 * Nag's special message type 'nag.alarm'.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Notification
 */
class Nag_Notification_Listener_Status extends Horde_Notification_Listener_Status
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->_handles['nag.alarm'] = array($GLOBALS['registry']->getImageDir() . '/alarm.png', _("Alarm"));
    }

}
