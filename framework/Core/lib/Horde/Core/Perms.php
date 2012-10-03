<?php
/**
 * The Horde_Core_Perms class provides information about internal Horde
 * elements that can be managed through the Horde_Perms system.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Core_Perms
{
    /**
     * A registry instance.
     *
     * @var Horde_Registry
     */
    protected $_registry;

    /**
     * A permissions instance.
     *
     * @var Horde_Perms_Base
     */
    protected $_perms;

    /**
     * Caches information about application permissions.
     *
     * @var array
     */
    protected $_appPerms;

    /**
     * Constructor.
     *
     * @params Horde_Registry $registry
     * @params Horde_Perms_Base $perms
     */
    public function __construct(Horde_Registry $registry,
                                Horde_Perms_Base $perms)
    {
        $this->_registry = $registry;
        $this->_perms = $perms;
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
        if ($name == Horde_Perms::ROOT) {
            $name = '';
        }

        if (empty($name)) {
            /* No name passed, so top level permissions are requested. These
             * can only be applications. */
            $apps = $this->_registry->listApps(array('notoolbar', 'active', 'hidden'), true);
            foreach (array_keys($apps) as $app) {
                $apps[$app] = $this->_registry->get('name', $app) . ' (' . $app . ')';
            }
            asort($apps);
            return $apps;
        }

        /* Name has been passed, explode the name to get all the levels in
         * permission being requisted, with the app as the first level. */
        $levels = explode(':', $name);

        /* First level is always app. */
        $app = $levels[0];

        /* Call the app's permission function to return the permissions
         * specific to this app. */
        $perms = $this->getApplicationPermissions($app);
        if (!count($perms)) {
            return false;
        }

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
        foreach (array_keys($children) as $perm_key) {
            $perms_list[$perm_key] = $perms['title'][$name . ':' . $perm_key];
        }

        return $perms_list;
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
        if ($name === Horde_Perms::ROOT) {
            return Horde_Core_Translation::t("All Permissions");
        }

        $levels = explode(':', $name);
        if (count($levels) == 1) {
            return $this->_registry->get('name', $name) . ' (' . $name . ')';
        }
        array_pop($levels); // This is the permission name

        /* First level is always app. */
        $app = $levels[0];

        $app_perms = $this->getApplicationPermissions($app);

        return isset($app_perms['title'][$name])
            ? $app_perms['title'][$name] . ' (' . $this->_perms->getShortName($name) . ')'
            : $this->_perms->getShortName($name);
    }

    /**
     * Given a permission name, returns the type for that permission.
     *
     * @param string $name  The permissions's name.
     *
     * @return string  The type for the permission.
     */
    public function getType($name)
    {
        $type = 'matrix';
        if ($pos = strpos($name, ':')) {
            try {
                $info = $this->getApplicationPermissions(substr($name, 0, $pos));
                if (isset($info['type']) && isset($info['type'][$name])) {
                    $type = $info['type'][$name];
                }
            } catch (Horde_Perms_Exception $e) {}
        }
        return $type;
    }

    /**
     * Given a permission name, returns the parameters for that permission.
     *
     * @param string $name  The permissions's name.
     *
     * @return array  The paramters for the permission.
     */
    public function getParams($name)
    {
        $params = null;
        if ($pos = strpos($name, ':')) {
            try {
                $info = $this->getApplicationPermissions(substr($name, 0, $pos));
                if (isset($info['params']) && isset($info['params'][$name])) {
                    $params = $info['params'][$name];
                }
            } catch (Horde_Perms_Exception $e) {}
        }
        return $params;
    }

    /**
     * Returns a new permissions object.
     *
     * This must be used instead of Horde_Perms_Base::newPermission() because
     * it works with application-specific permissions.
     *
     * @param string $name   The permission's name.
     *
     * @return Horde_Perms_Permission  A new permissions object.
     */
    public function newPermission($name)
    {
        return $this->_perms->newPermission($name,
                                            $this->getType($name),
                                            $this->getParams($name));
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
                $app_perms = $this->_registry->callAppMethod($app, 'perms');
            } catch (Horde_Exception $e) {
                $app_perms = array();
            }

            if (empty($app_perms)) {
                $perms = array();
            } else {
                $perms = array(
                    'title' => array(),
                    'tree' => array(
                        $app => array()
                    ),
                    'type' => array()
                );

                foreach ($app_perms as $key => $val) {
                    $ptr = &$perms['tree'][$app];

                    foreach (explode(':', $key) as $kval) {
                        if (!isset($ptr[$kval])) {
                            $ptr[$kval] = false;
                        }
                        $ptr = &$ptr[$kval];
                    }
                    if (isset($val['title'])) {
                        $perms['title'][$app . ':' . $key] = $val['title'];
                    }
                    if (isset($val['type'])) {
                        $perms['type'][$app . ':' . $key] = $val['type'];
                    }
                    if (isset($val['params'])) {
                        $perms['params'][$app . ':' . $key] = $val['params'];
                    }
                }
            }

            $this->_appPerms[$app] = $perms;
        }

        return $this->_appPerms[$app];
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
     * @return mixed  The specified permissions.
     */
    public function hasAppPermission($permission, $opts = array())
    {
        $app = isset($opts['app'])
            ? $opts['app']
            : $this->_registry->getApp();

        if ($this->_perms->exists($app . ':' . $permission)) {
            $perms = $this->_perms->getPermissions($app . ':' . $permission, $this->_registry->getAuth());
            if ($perms === false) {
                return false;
            }

            $args = array(
                $permission,
                $perms,
                isset($opts['opts']) ? $opts['opts'] : array()
            );

            try {
                return $this->_registry->callAppMethod($app, 'hasPermission', array('args' => $args));
            } catch (Horde_Exception $e) {}
        }

        return true;
    }
}
