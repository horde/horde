<?php
/**
 * A class that stores notifications in the session, using Horde_Session.
 *
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 * @package  Core
 */

/**
 * A class that stores notifications in the session, using Horde_Session.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 * @package  Core
 */
class Horde_Core_Notification_Storage_Session
implements Horde_Notification_Storage_Interface
{
    /**
     */
    public function get($key)
    {
        return $GLOBALS['session']['horde:notify/' . $key];
    }

    /**
     */
    public function set($key, $value)
    {
        $GLOBALS['session']['horde:notify/' . $key] = $value;
    }

    /**
     */
    public function exists($key)
    {
        return isset($GLOBALS['session']['horde:notify/' . $key]);
    }

    /**
     */
    public function clear($key)
    {
        unset($GLOBALS['session']['horde:notify/' . $key]);
    }

    /**
     */
    public function push($listener, Horde_Notification_Event $event)
    {
        global $session;

        $events = $session['horde:notify/' . $listener . ';array'];
        $events[] = $event;
        $session['horde:notify/' . $listener . ';object'] = $events;
    }

}
