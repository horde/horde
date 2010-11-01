<?php
/**
 * Folks Notification Class.
 *
 * $Id: Driver.php 1400 2009-03-09 09:58:40Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Notification {

    /**
     * Instances
     */
    static private $instances = array();

    /**
     * Driver parameters
     */
    protected $_params;

    /**
     * Constructor
     *
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Notify user in all available drivers
     *
     * @param string $subject     Subject of message
     * @param string $body        Body of message
     * @param array  $attachments Attached files
     * @param mixed  $user        User or array of users to send notification to
     *
     * @return true on succes, PEAR_Error on failure
     */
    public function notifyAll($subject, $body, $attachments = array(), $user = null)
    {
        $result = false;

        if (empty($user)) {
            if ($GLOBALS['registry']->isAuthenticated()) {
                $user = $GLOBALS['registry']->getAuth();
            } else {
                return true;
            }
        }

        foreach ($GLOBALS['conf']['notification'] as $driver => $params) {
            if ($params['enabled'] && $params['users']) {
                $instance = $this->singleton($driver, $params);
                if ($instance instanceof PEAR_Error) {
                    return $instance;
                }
                if (!$instance->isAvailable('users')) {
                    continue;
                }
                $result = $instance->notify($user, $subject, $body, $attachments);
                if ($result instanceof PEAR_Error) {
                    return $result;
                }
            }
        }

        return $result;
    }

    /**
     * Notify user's friends in all available drivers
     *
     * @param mixed  $user        User or array of users to send notification to
     * @param string $subject     Subject of message
     * @param string $body        Body of message
     * @param array  $attachments Attached files
     * @param string $user        User to send notifications to
     *
     * @return true on succes, PEAR_Error on failure
     */
    public function notifyAllFriends($subject, $body, $attachments = array(), $user = null)
    {
        $result = false;

        if (empty($user)) {
            if ($GLOBALS['registry']->isAuthenticated()) {
                $user = $GLOBALS['registry']->getAuth();
            } else {
                return true;
            }
        }

        foreach ($GLOBALS['conf']['notification'] as $driver => $params) {
            if ($params['enabled'] && $params['friends']) {
                $instance = $this->singleton($driver, $params);
                if ($instance instanceof PEAR_Error) {
                    return $instance;
                }
                if (!$instance->isAvailable('friends')) {
                    continue;
                }
                $result = $instance->notifyFriends($user, $subject, $body, $attachments);
                if ($result instanceof PEAR_Error) {
                    return $result;
                }
            }
        }

        return $result;
    }

    /**
     * Notify user in all available drivers
     *
     * @param string $subject     Subject of message
     * @param string $body        Body of message
     * @param array  $attachments Attached files
     *
     * @return true on succes, PEAR_Error on failure
     */
    public function notifyAdmins($subject, $body, $attachments = array())
    {
        $result = false;

        $admins = $this->getAdmins();
        if (empty($admins)) {
            return true;
        }

        foreach ($GLOBALS['conf']['notification'] as $driver => $params) {
            if ($params['enabled'] && $params['admins']) {
                $instance = $this->singleton($driver, $params);
                if ($instance instanceof PEAR_Error) {
                    return $instance;
                }
                if (!$instance->isAvailable('admins')) {
                    continue;
                }
                $result = $instance->notify($admins, $subject, $body, $attachments);
                if ($result instanceof PEAR_Error) {
                    return $result;
                }
            }
        }

        return $result;
    }

    /**
     * Get current scope admins
     *
     * @return Array of user with delete permission in "scope:admin" permission
     */
    public function getAdmins()
    {
        $name = $GLOBALS['registry']->getApp() . ':admin';

        if ($GLOBALS['injector']->getInstance('Horde_Perms')->exists($name)) {
            $permission = $GLOBALS['injector']->getInstance('Horde_Perms')->getPermission($name);
            if ($permission instanceof PEAR_Error) {
                return $permission;
            } else {
                $admins = $permission->getUserPermissions(Horde_Perms::DELETE);
                if ($admins instanceof PEAR_Error) {
                    return $admins;
                }
                $admins = array_keys($admins);
            }
        }

        if (empty($admins)) {
            return $GLOBALS['conf']['auth']['admins'];
        } else {
            return $admins;
        }
    }

    /**
     * Returns all avaiable methods
     *
     * @param string $type Type of notification to check
     *
     * @return true on succes, PEAR_Error on failure
     */
    public function getMethods($type = 'user')
    {
        $methods = array();

        foreach ($GLOBALS['conf']['notification'] as $driver => $params) {
            if (empty($params['enabled'])) {
                continue;
            }
            $instance = $this->singleton($driver, $params);
            if ($instance instanceof PEAR_Error) {
                return $instance;
            }
            if (!$instance->isAvailable($type)) {
                continue;
            }
            $methods[$driver] = $instance->getName();
        }

        return $methods;
    }

    /**
     * Try to get read user from address
     *
     * @param string $user Username
     *
     * @return string User email
     */
    protected function _getUserFromAddr($user)
    {
        return $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($user)->getValue('from_addr');
    }

    /**
     * Attempts to return a concrete Folks_Notification instance based on $driver.
     *
     * @param string $driver  The type of the concrete Folks_Notification subclass
     *                        to return.  The class name is based on the
     *                        storage driver ($driver).  The code is
     *                        dynamically included.
     *
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return Folks_Notification  The newly created concrete Folks_Notification
     *                          instance, or false on an error.
     */
    static protected function factory($driver, $params = null)
    {
        include_once FOLKS_BASE . '/lib/Notification/' . $driver . '.php';

        if ($params === null) {
            $params = $GLOBALS['conf']['notification'][$driver];
        }

        $class = 'Folks_Notification_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        } else {
            return PEAR::raiseError(sprintf(_("Notification driver %s does not exists."), $driver));
        }
    }

    /**
     * Singleton for driver object
     *
     * @param string $driver  The type of the concrete Folks_Notification subclass
     *                        to return.  The class name is based on the
     *                        storage Notification ($driver).  The code is
     *                        dynamically included.
     *
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     */
    static public function singleton($driver, $params = null)
    {
        if (!array_key_exists($driver, self::$instances)) {
            self::$instances[$driver] = self::factory($driver, $params);
        }

        return self::$instances[$driver];
    }
}
