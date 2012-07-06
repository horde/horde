<?php
/**
 * A Horde_Injector:: based Horde_Prefs:: factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based Horde_Prefs:: factory.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_Prefs extends Horde_Core_Factory_Base
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return the Horde_Prefs:: instance.
     *
     * @param string $scope  The scope for this set of preferences.
     * @param array $opts    See Horde_Prefs::__construct().  Additional
     *                       parameters:
     *   - driver: (boolean) Use this driver instead of the value in the Horde
     *             config.
     *   - driver_params: (array) Use these driver parameters instead of the
     *                    values in the Horde config.
     *
     * @return Horde_Prefs  The singleton instance.
     */
    public function create($scope = 'horde', array $opts = array())
    {
        if (array_key_exists('driver', $opts)) {
            $driver = $opts['driver'];
            $params = array();
        } elseif (empty($GLOBALS['conf']['prefs']['driver'])) {
            $driver = null;
            $params = array();
        } else {
            $driver = 'Horde_Prefs_Storage_' . ucfirst($GLOBALS['conf']['prefs']['driver']);
            $params = Horde::getDriverConfig('prefs', $driver);
        }

        if (array_key_exists('driver_params', $opts)) {
            $params = $opts['driver_params'];
        }

        $opts = array_merge(array(
            'cache' => true,
            'logger' => $this->_injector->getInstance('Horde_Log_Logger'),
            'password' => '',
            'sizecallback' => ((isset($GLOBALS['conf']['prefs']['maxsize'])) ? array($this, 'sizeCallback') : null),
            'user' => ''
        ), $opts);

        /* If $params['user_hook'] is defined, use it to retrieve the value to
         * use for the username. */
        if (!empty($params['user_hook']) &&
            function_exists($params['user_hook'])) {
            $opts['user'] = call_user_func($params['user_hook'], $opts['user']);
        }

        /* To determine signature, don't serialize the logger or size
         * callback, since they may contain unserializable components. */
        $sig_opts = array_merge($opts, array(
            'logger' => get_class($opts['logger']),
            'sizecallback' => !is_null($opts['sizecallback'])
        ));
        ksort($sig_opts);
        $sig = hash('md5', serialize($sig_opts));

        if (isset($this->_instances[$sig])) {
            $this->_instances[$sig]->retrieve($scope);
            return $this->_instances[$sig];
        }

        try {
            switch ($driver) {
            case 'Horde_Prefs_Storage_Ldap':
                $params['ldap'] = $this->_injector
                    ->getInstance('Horde_Core_Factory_Ldap')
                    ->create('horde', 'ldap');
                break;

            case 'Horde_Prefs_Storage_Session':
                $driver = 'Horde_Prefs_Storage_Null';
                break;

            case 'Horde_Prefs_Storage_Sql':
                $params['db'] = $this->_injector
                    ->getInstance('Horde_Db_Adapter');
                break;
            case 'Horde_Prefs_Storage_KolabImap':
                if ($GLOBALS['registry']->isAdmin()) {
                    throw new Horde_Exception('The IMAP based Kolab preferences backend is unavailable for system administrators.');
                }
                $params['kolab'] = $this->_injector
                    ->getInstance('Horde_Kolab_Storage');
                $params['logger'] = $opts['logger'];
                break;
            case 'Horde_Prefs_Storage_Imsp':
                $imspParams = $GLOBALS['conf']['imsp'];
                $imspParams['username'] = $GLOBALS['registry']->getAuth('bare');
                $imspParams['password'] = $GLOBALS['registry']->getAuthCredential('password');
                $params['imsp'] = $this->_injector
                    ->getInstance('Horde_Core_Factory_Imsp')->create('Options', $imspParams);
            }
            $driverOb = new $driver($opts['user'], $params);
        } catch (Horde_Exception $e) {
            $this->_notifyError($e);
            $driver = 'Horde_Prefs_Storage_Null';
            $driverOb = new $driver($opts['user'], $params);
            $opts['cache'] = false;
        }

        $config_driver = new Horde_Core_Prefs_Storage_Configuration($opts['user']);
        $hooks_driver = new Horde_Core_Prefs_Storage_Hooks($opts['user'], array('conf_ob' => $config_driver));

        $drivers = $driver
            ? array($config_driver, $driverOb, $hooks_driver)
            : array($config_driver, $hooks_driver);

        if ($driver && $opts['cache']) {
            $opts['cache'] = new Horde_Core_Prefs_Cache_Session($opts['user']);
        } else {
            unset($opts['cache']);
        }

        try {
            $this->_instances[$sig] = new Horde_Prefs($scope, $drivers, $opts);
        } catch (Horde_Prefs_Exception $e) {
            $this->_notifyError($e);

            /* Store data in the cached session object. */
            $opts['cache'] = new Horde_Core_Prefs_Cache_Session($opts['user']);
            $this->_instances[$sig] = new Horde_Prefs($scope, array($config_driver, $hooks_driver), $opts);
        }

        return $this->_instances[$sig];
    }

    /**
     * Notifies (once) if one of the preference backends is not available and
     * logs details for the administrator.
     *
     * @param mixed $e  Error to log.
     */
    protected function _notifyError($e)
    {
        if (!$GLOBALS['session']->get('horde', 'no_prefs')) {
            $GLOBALS['session']->set('horde', 'no_prefs', true);
            if (isset($GLOBALS['notification'])) {
                $GLOBALS['notification']->push(Horde_Core_Translation::t("The preferences backend is currently unavailable and your preferences have not been loaded. You may continue to use the system with default preferences."));
                Horde::logMessage($e);
            }
        }
    }

    /**
     * Clear the instances cache.
     */
    public function clearCache()
    {
        $this->_instances = array();
    }

    /**
     * Max size callback.
     *
     * @param string $pref   Preference name.
     * @param integer $size  Size (in bytes).
     *
     * @return boolean  True if oversized.
     */
    public function sizeCallback($pref, $size)
    {
        if ($size <= $GLOBALS['conf']['prefs']['maxsize']) {
            return false;
        }

        $GLOBALS['notification']->push(sprintf(Horde_Core_Translation::t("The preference \"%s\" could not be saved because its data exceeds the maximum allowable size"), $pref), 'horde.error');
        return true;
    }

}
