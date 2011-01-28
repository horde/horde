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
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
        return $GLOBALS['session']->get('horde', 'notify/' . $key);
    }

    /**
     */
    public function set($key, $value)
    {
        $GLOBALS['session']->set('horde', 'notify/' . $key, $value);
    }

    /**
     */
    public function exists($key)
    {
        return $GLOBALS['session']->exists('horde', 'notify/' . $key);
    }

    /**
     */
    public function clear($key)
    {
        $GLOBALS['session']->remove('horde', 'notify/' . $key);
    }

    /**
     */
    public function push($listener, Horde_Notification_Event $event)
    {
        global $session;

        $events = $session->get('horde', 'notify/' . $listener, Horde_Session::TYPE_ARRAY);
        $events[] = $event;
        $session->set('horde', 'notify/' . $listener, $events, Horde_Session::TYPE_OBJECT);
    }

}
