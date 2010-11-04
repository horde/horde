<?php
/**
 * A Horde_Injector:: based Horde_Prefs:: factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based Horde_Prefs:: factory.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_Prefs
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector  The injector to use.
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Return the Horde_Prefs:: instance.
     *
     * @param string $scope  The scope for this set of preferences.
     * @param array $opts    See Horde_Prefs::__construct(). Additional
     *                       options:
     * <pre>
     * 'session' - (boolean) Use the session driver.
     *             DEFAULT: false
     * </pre>
     *
     * @return Horde_Prefs  The singleton instance.
     */
    public function create($scope = 'horde', array $opts = array())
    {
        if (empty($GLOBALS['conf']['prefs']['driver']) ||
            !empty($opts['session'])) {
            $driver = 'Horde_Core_Prefs_Storage_Session';
            $params = array();
        } else {
            $driver = 'Horde_Prefs_Storage_' . ucfirst($GLOBALS['conf']['prefs']['driver']);
            $params = Horde::getDriverConfig('prefs', $driver);
        }

        $opts = array_merge(array(
            'cache' => true,
            'charset' => 'UTF-8',
            'logger' => $this->_injector->getInstance('Horde_Log_Logger'),
            'password' => '',
            'sizecallback' => ((isset($GLOBALS['conf']['prefs']['maxsize'])) ? array($this, 'sizeCallback') : null),
            'user' => ''
        ), $opts);
        ksort($opts);

        /* If $params['user_hook'] is defined, use it to retrieve the value to
         * use for the username. */
        if (!empty($params['user_hook']) &&
            function_exists($params['user_hook'])) {
            $opts['user'] = call_user_func($params['user_hook'], $opts['user']);
        }

        $sig = hash('md5', serialize($opts));

        if (isset($this->_instances[$sig])) {
            $this->_instances[$sig]->retrieve($scope);
        } else {
            switch ($driver) {
            case 'Horde_Prefs_Storage_Ldap':
                $params['ldap'] = $this->_injector->getInstance('Horde_Core_Factory_Ldap')->getLdap('horde', 'ldap');
                break;

            case 'Horde_Prefs_Storage_Session':
                $opts['cache'] = false;
                break;

            case 'Horde_Prefs_Storage_Sql':
                $params['db'] = $this->_injector->getInstance('Horde_Db_Adapter');
                $opts['charset'] = $params['db']->getOption('charset');
                break;
            }

            $drivers = array(
                new $driver($opts['user'], $params)
            );

            if ($opts['cache']) {
                $opts['cache'] = new Horde_Core_Prefs_Storage_Session($opts['user']);
            } else {
                unset($opts['cache']);
            }

            try {
                $this->_instances[$sig] = new Horde_Core_Prefs($scope, $drivers, $opts);
            } catch (Horde_Prefs_Exception $e) {
                if (!$GLOBALS['session']->get('horde', 'no_prefs')) {
                    $GLOBALS['session']->set('horde', 'no_prefs', true);
                    if (isset($GLOBALS['notification'])) {
                        $GLOBALS['notification']->push(Horde_Core_Translation::t("The preferences backend is currently unavailable and your preferences have not been loaded. You may continue to use the system with default preferences."));
                    }
                }
                unset($opts['cache']);
                $this->_instances[$sig] = new Horde_Core_Prefs($scope, new Horde_Core_Prefs_Storage_Session($opts['user']), $opts);
            }
        }

        return $this->_instances[$sig];
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
