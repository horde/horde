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
 * Core Horde notification handler.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.18.0
 */
class Horde_Core_Notification_Handler
extends Horde_Notification_Handler
{
    const SESS_KEY = 'core_notification_handler';

    /**
     * List of applications that contain notification handlers. Array with
     * keys as app names and values as boolean values indicating if the
     * handler has been loaded yet. False indicates attaching is not active.
     */
    protected $_apps = false;

    /**
     */
    public function notify(array $options = array())
    {
        if ($this->_apps) {
            foreach ($this->_apps as $key => $val) {
                if (!$val) {
                    $this->addAppHandler($key);
                }
            }
            $this->_apps = false;
        }

        return parent::notify($options);
    }

    /**
     * Indicate that all application handlers are to be attached in this
     * access (if needed).
     */
    public function attachAllAppHandlers()
    {
        global $registry, $session;

        /* Cache notification handler application method existence. */
        $this->_apps = $session->get('horde', self::SESS_KEY);
        if (!is_null($this->_apps)) {
            return;
        }

        $changed = ($registry->getAuth() !== false);
        $this->_apps = array();

        try {
            $apps = $registry->listApps(null, false, Horde_Perms::READ);
        } catch (Horde_Exception $e) {
            $apps = array();
        }

        foreach ($apps as $app) {
            if ($registry->hasFeature('notificationHandler', $app)) {
                $this->_apps[] = false;
            }
        }

        $session->set('horde', self::SESS_KEY, $this->_apps);
    }

    /**
     * Explicitly add an application's notification handlers (if they exist)
     * to the base handler.
     *
     * @param string $app  Application name.
     */
    public function addAppHandler($app)
    {
        global $registry;

        if (!$this->_apps || !empty($this->_apps[$app])) {
            return;
        }

        $this->_apps[$app] = true;

        try {
            $registry->callAppMethod(
                $app,
                'setupNotification',
                array(
                    'args' => array($this),
                    'noperms' => true
                )
            );
        } catch (Exception $e) {}
    }

}
