<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Allows web notifications to be sent to the browser.
 *
 * See: https://dvcs.w3.org/hg/notifications/raw-file/tip/Overview.html
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */
class Horde_Core_Notification_Listener_Webnotification
extends Horde_Notification_Listener
{
    /**
     */
    public function __construct()
    {
        global $page_output;

        $this->_handles['webnotification'] = 'Horde_Core_Notification_Event_Webnotification';
        $this->_name = 'webnotification';

        $page_output->addScriptFile('webnotification.js', 'horde');
    }

    /**
     */
    public function notify($events, $options = array())
    {
        /* No support for basic view at this time. */
    }

}
