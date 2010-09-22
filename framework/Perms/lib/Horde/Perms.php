<?php
/**
 * The Horde_Perms:: class provides the Horde permissions system.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Horde_Perms
 */
class Horde_Perms
{
    /* Existence of object is known - object is shown to user. */
    const SHOW = 2;
    /* Contents of the object can be read. */
    const READ = 4;
    /* Contents of the object can be edited. */
    const EDIT = 8;
    /* The object can be deleted. */
    const DELETE = 16;

    /* A bitmask of all possible permission values. Useful for
     * removeXxxPermission(), unsetPerm(), etc.
     * 30 = SHOW | READ | EDIT | DELETE */
    const ALL = 30;

    /* The root permission. */
    const ROOT = -1;

    /**
     * Cache object.
     *
     * @var Horde_Cache
     */
    protected $_cache;

    /**
     * Logger.
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Caches information about application permissions.
     *
     * @var array
     */
    protected $_appPerms;

    /**
     * Cache for integerToArray().
     *
     * @var array
     */
    static protected $_itaCache = array();

    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @param string $driver  The type of the concrete subclass to return.
     *                        The class name is based on the perms driver
     *                        ($driver).  The code is dynamically included.
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return Horde_Perms  The newly created concrete instance.
     * @throws Horde_Perms_Exception
     */
    static public function factory($driver = null, $params = null)
    {
        $class = is_null($driver)
            ? __CLASS__
            : __CLASS__ . '_' . ucfirst(basename($driver));

        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Perms_Exception('Unknown driver: ' . $driver);
    }

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     * <pre>
     * 'cache' - (Horde_Cache) The object to use to cache perms.
     * 'logger' - (Horde_Log_Logger) A logger object.
     * </pre>
     *
     * @throws Horde_Perms_Exception
     */
    public function __construct($params = array())
    {
        if (isset($params['cache'])) {
            $this->_cache = $params['cache'];
        }

        if (isset($params['logger'])) {
            $this->_logger = $params['logger'];
        }
    }

    /**
     * Returns the available permissions for a given level.
     *
     * @param string $name  The permission's name.
     *
     * @return array  An array of available permissions and their titles or
     *                false if not sub permissions exist for this level.
     * @throws Horde_Perms_Exception
     */
    public function getAvailable($name)
    {
        if ($name == self::ROOT) {
            $name = '';
        }

        if (empty($name)) {
            /* No name passed, so top level permissions are requested. These
             * can only be applications. */
            $apps = $GLOBALS['registry']->listApps(array('notoolbar', 'active', 'hidden'), true);
            foreach (array_keys($apps) as $app) {
                $apps[$app] = $GLOBALS['registry']->get('name', $app) . ' (' . $app . ')';
            }
            asort($apps);

            return $apps;
        }

        /* Name has been passed, explode the name to get all the levels in
         * permission being requisted, with the app as the first level. */
        $levels = explode(':', $name);

        /* First level is always app. */
        $app = $levels[0];

        /* Return empty if no app defined API method for providing
         * permission information. */
        if (!$GLOBALS['registry']->hasAppMethod($app, 'perms')) {
            return false;
        }

        /* Call the app's permission function to return the permissions
         * specific to this app. */
        $perms = $this->getApplicationPermissions($app);

        /* Get the part of the app's permissions based on the permission
         * name requested. */
        $children = Horde_Array::getElement($perms['tree'], $levels);
        if (($children === false) ||
            !is_array($children) ||
            !count($children)) {
            /* No array of children available for this permission name. */
            return false;
        }

        $perms_list = array();
        foreach ($children as $perm_key => $perm_val) {
            $perms_list[$perm_key] = $perms['title'][$name . ':' . $perm_key];
        }

        return $perms_list;
    }

    /**
     * Returns the short name of an object, the last portion of the full name.
     *
     * @param string $name  The name of the object.
     *
     * @return string  The object's short name.
     */
    static public function getShortName($name)
    {
        /* If there are several components to the name, explode and
         * get the last one, otherwise just return the name. */
        if (strpos($name, ':') !== false) {
            $tmp = explode(':', $name);
            return array_pop($tmp);
        }

        return $name;
    }

    /**
     * Given a permission name, returns the title for that permission by
     * looking it up in the applications's permission api.
     *
     * @param string $name  The permissions's name.
     *
     * @return string  The title for the permission.
     */
    public function getTitle($name)
    {
        if ($name === self::ROOT) {
            return _("All Permissions");
        }

        $levels = explode(':', $name);
        if (count($levels) == 1) {
            return $GLOBALS['registry']->get('name', $name) . ' (' . $name . ')';
        }
        $perm = array_pop($levels);

        /* First level is always app. */
        $app = $levels[0];

        /* Return empty if no app defined API method for providing permission
         * information. */
        if (!$GLOBALS['registry']->hasAppMethod($app, 'perms')) {
            return $this->getShortName($name);
        }

        $app_perms = $this->getApplicationPermissions($app);

        return isset($app_perms['title'][$name])
            ? $app_perms['title'][$name] . ' (' . $this->getShortName($name) . ')'
            : $this->getShortName($name);
    }

    /**
     * Returns information about permissions implemented by an application.
     *
     * @param string $app  An application name.
     *
     * @return array  Hash with permissions information.
     */
    public function getApplicationPermissions($app)
    {
        if (!isset($this->_appPerms[$app])) {
            try {
                $perms = array(
                    'title' => array(),
                    'tree' => array(
                        $app => array()
                    ),
                    'type' => array()
                );

                foreach ($GLOBALS['registry']->callAppMethod($app, 'perms') as $key => $val) {
                    $ptr = &$perms['tree'][$app];
                    foreach (explode(':', $key) as $kval) {
                        $ptr[$kval] = false;
                        $ptr = &$perms['tree'][$app];
                    }
                    if (isset($val['title'])) {
                        $perms['title'][$app . ':' . $key] = $val['title'];
                    }
                    if (isset($val['type'])) {
                        $perms['type'][$app . ':' . $key] = $val['type'];
                    }
                }
            } catch (Horde_Exception $e) {
                $perms = array();
            }

            $this->_appPerms[$app] = $perms;
        }

        return $this->_appPerms[$app];
    }

    /**
     * Returns a new permissions object.
     *
     * @param string $name  The permission's name.
     *
     * @return Horde_Perms_Permission  A new permissions object.
     * @throws Horde_Perms_Exception
     */
    public function newPermission($name)
    {
        throw new Horde_Perms_Exception('The administrator needs to configure a permanent permissions backend.');
    }

    /**
     * Returns an object corresponding to the named permission, with the users
     * and other data retrieved appropriately.
     *
     * @param string $name  The name of the permission to retrieve.
     *
     * @return Horde_Perms_Permission  A permissions object.
     * @throws Horde_Perms_Exception
     */
    public function getPermission($name)
    {
        throw new Horde_Perms_Exception('The administrator needs to configure a permanent permissions backend.');
    }

    /**
     * Returns an object corresponding to the given unique ID, with the users
     * and other data retrieved appropriately.
     *
     * @param integer $cid  The unique ID of the permission to retrieve.
     *
     * @return Horde_Perms_Permission  A permissions object.
     * @throws Horde_Perms_Exception
     */
    public function getPermissionById($cid)
    {
        throw new Horde_Perms_Exception('The administrator needs to configure a permanent permissions backend.');
    }

    /**
     * Adds a permission to the permissions system. The permission must first
     * be created with newPermission(), and have any initial users added to
     * it, before this function is called.
     *
     * @param Horde_Perms_Permission $perm  The permissions object.
     *
     * @throws Horde_Perms_Exception
     */
    public function addPermission($perm)
    {
        throw new Horde_Perms_Exception('The administrator needs to configure a permanent permissions backend.');
    }

    /**
     * Removes a permission from the permissions system permanently.
     *
     * @param Horde_Perms_Permission $perm  The permission to remove.
     * @param boolean $force                Force to remove every child.
     *
     * @throws Horde_Perms_Exception
     */
    public function removePermission($perm, $force = false)
    {
        throw new Horde_Perms_Exception('The administrator needs to configure a permanent permissions backend.');
    }

    /**
     * Finds out what rights the given user has to this object.
     *
     * @param mixed $permission  The full permission name of the object to
     *                           check the permissions of, or the
     *                           Horde_Permissions object.
     * @param string $user       The user to check for. Defaults to the current
     *                           user.
     * @param string $creator    The user who created the event.
     *
     * @return mixed  A bitmask of permissions the user has, false if there
     *                are none.
     */
    public function getPermissions($permission, $user = null, $creator = null)
    {
        if (is_string($permission)) {
            try {
                $permission = $this->getPermission($permission);
            } catch (Horde_Perms_Exception $e) {
                if ($this->_logger) {
                    $this->_logger->log($e, 'DEBUG');
                }
                return false;
            }
        }

        if (is_null($user)) {
            $user = $GLOBALS['registry']->getAuth();
        }

        // If this is a guest user, only check guest permissions.
        if (empty($user)) {
            return $permission->getGuestPermissions();
        }

        // If $creator was specified, check creator permissions.
        // If the user is the creator of the event see if there are creator
        // permissions.
        if (!is_null($creator) &&
            strlen($user) &&
            ($user === $creator) &&
            (($perms = $permission->getCreatorPermissions()) !== null)) {
            return $perms;
        }

        // Check user-level permissions.
        $userperms = $permission->getUserPermissions();
        if (isset($userperms[$user])) {
            return $userperms[$user];
        }

        // If no user permissions are found, try group permissions.
        if (isset($permission->data['groups']) &&
            is_array($permission->data['groups']) &&
            count($permission->data['groups'])) {
            $groups = $GLOBALS['injector']->getInstance('Horde_Group');

            $composite_perm = null;
            $type = $permission->get('type');
            foreach ($permission->data['groups'] as $group => $perm) {
                if ($groups->userIsInGroup($user, $group)) {
                    if (is_null($composite_perm)) {
                        $composite_perm = ($type == 'matrix') ? 0 : array();
                    }

                    if ($type == 'matrix') {
                        $composite_perm |= $perm;
                    } else {
                        $composite_perm[] = $perm;
                    }
                }
            }

            if (!is_null($composite_perm)) {
                return $composite_perm;
            }
        }

        // If there are default permissions, return them.
        if (($perms = $permission->getDefaultPermissions()) !== null) {
            return $perms;
        }

        // Otherwise, deny all permissions to the object.
        return false;
    }

    /**
     * Returns the unique identifier of this permission.
     *
     * @param Horde_Perms_Permission $permission  The permission object to get
     *                                            the ID of.
     *
     * @return integer  The unique id.
     * @throws Horde_Perms_Exception
     */
    public function getPermissionId($permission)
    {
        throw new Horde_Perms_Exception('The administrator needs to configure a permanent permissions backend.');
    }

    /**
     * Finds out if the user has the specified rights to the given object.
     *
     * @param string $permission  The permission to check.
     * @param string $user        The user to check for.
     * @param integer $perm       The permission level that needs to be checked
     *                            for.
     * @param string $creator     The creator of the event
     *
     * @return boolean  Whether the user has the specified permissions.
     */
    public function hasPermission($permission, $user, $perm, $creator = null)
    {
        return ($this->getPermissions($permission, $user, $creator) & $perm);
    }

    /**
     * Finds out if the user has the specified rights to the given object,
     * specific to a certain application.
     *
     * @param string $permission  The permission to check.
     * @param array $opts         Additional options:
     * <pre>
     * 'app' - (string) The app to check.
     *         DEFAULT: The current pushed app.
     * 'opts' - (array) Additional options to pass to the app function.
     *          DEFAULT: None
     * </pre>
     *
     * @return boolean  Whether the user has the specified permissions.
     */
    public function hasAppPermission($permission, $opts = array())
    {
        $app = isset($opts['app'])
            ? $opts['app']
            : $GLOBALS['registry']->getApp();

        if ($this->exists($app . ':' . $permission)) {
            $perms = $this->getPermissions($app . ':' . $permission);
            if ($perms === false) {
                return false;
            }

            $args = array(
                $permission,
                $perms,
                isset($opts['opts']) ? $opts['opts'] : array()
            );

            try {
                return $GLOBALS['registry']->callAppMethod($app, 'hasPermission', array('args' => $args));
            } catch (Horde_Exception $e) {}
        }

        return true;
    }

    /**
     * Checks if a permission exists in the system.
     *
     * @param string $permission  The permission to check.
     *
     * @return boolean  True if the permission exists.
     */
    public function exists($permission)
    {
        return false;
    }

    /**
     * Returns a list of parent permissions.
     *
     * @param string $child  The name of the child to retrieve parents for.
     *
     * @return array  A hash with all parents in a tree format.
     * @throws Horde_Perms_Exception
     */
    public function getParents($child)
    {
        throw new Horde_Perms_Exception('The administrator needs to configure a permanent permissions backend.');
    }

    /**
     * Returns all permissions of the system in a tree format.
     *
     * @return array  A hash with all permissions in a tree format.
     */
    public function getTree()
    {
        return array();
    }

    /**
     * Returns an hash of the available permissions.
     *
     * @return array  The available permissions as a hash.
     */
    static public function getPermsArray()
    {
        return array(
            self::SHOW => _("Show"),
            self::READ => _("Read"),
            self::EDIT => _("Edit"),
            self::DELETE => _("Delete")
        );
    }

    /**
     * Given an integer value of permissions returns an array representation
     * of the integer.
     *
     * @param integer $int  The integer representation of permissions.
     *
     * @return TODO
     */
    static public function integerToArray($int)
    {
        if (isset(self::$_itaCache[$int])) {
            return self::$_itaCache[$int];
        }

        self::$_itaCache[$int] = array();

        /* Get the available perms array. */
        $perms = self::getPermsArray();

        /* Loop through each perm and check if its value is included in the
         * integer representation. */
        foreach ($perms as $val => $label) {
            if ($int & $val) {
                self::$_itaCache[$int][$val] = true;
            }
        }

        return self::$_itaCache[$int];
    }

}
