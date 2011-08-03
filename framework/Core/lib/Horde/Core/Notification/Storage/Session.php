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
     * Cached notifications if session is not active.
     *
     * @var array
     */
    protected $_cached = array();

    /**
     */
    public function get($key)
    {
        $this->_processCached();
        return $GLOBALS['session']->get('horde', 'notify/' . $key);
    }

    /**
     */
    public function set($key, $value)
    {
        if ($GLOBALS['session']->isActive()) {
            $this->_processCached();
            $GLOBALS['session']->set('horde', 'notify/' . $key, $value);
        } else {
            $this->_cached[] = array($key, $value);
        }
    }

    /**
     */
    public function exists($key)
    {
        $this->_processCached();
        return $GLOBALS['session']->exists('horde', 'notify/' . $key);
    }

    /**
     */
    public function clear($key)
    {
        $this->_cached = array();
        $GLOBALS['session']->remove('horde', 'notify/' . $key);
    }

    /**
     */
    public function push($listener, Horde_Notification_Event $event)
    {
        global $session;

        if ($session->isActive()) {
            $events = $session->get('horde', 'notify/' . $listener, Horde_Session::TYPE_ARRAY);
            $events[] = $event;
            $session->set('horde', 'notify/' . $listener, $events, Horde_Session::TYPE_OBJECT);
        } else {
            $this->_cached[] = array($listener, $event);
        }
    }

    /**
     */
    protected function _processCached()
    {
        if (!empty($this->_cached) && $GLOBALS['session']->isActive()) {
            $cached = $this->_cached;
            $this->_cached = array();

            foreach ($cached as $val) {
                if ($val[1] instanceof Horde_Notification_Event) {
                    $this->push($val[0], $val[1]);
                } else {
                    $this->set($val[0], $val[1]);
                }
            }
        }
    }

}
