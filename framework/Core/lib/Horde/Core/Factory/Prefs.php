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
     * @param array $opts    See Horde_Prefs::factory(). Additional options:
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
            $driver = 'Session';
            $params = array();
            unset($opts['session']);
        } else {
            $driver = ucfirst($GLOBALS['conf']['prefs']['driver']);
            $params = Horde::getDriverConfig('prefs', $driver);
        }

        $opts = array_merge(array(
            'cache' => true,
            'charset' => $GLOBALS['registry']->getCharset(),
            'logger' => $this->_injector->getInstance('Horde_Log_Logger'),
            'password' => '',
            'sizecallback' => ((isset($GLOBALS['conf']['prefs']['maxsize'])) ? array($this, 'sizeCallback') : null),
            'uicharset' => $GLOBALS['registry']->getCharset(),
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
            case 'Ldap':
                //$params['ldap'] = $this->_injector->getInstance('Horde_Ldap')->getLdap('horde', 'ldap');
                break;

            case 'Sql':
                $params['db'] = $this->_injector->getInstance('Horde_Db_Adapter');
                // @todo All DB's use UTF-8(?) Does not seem to be a way to
                // get this information from Horde_Db_Adapter.
                $opts['charset'] = 'UTF-8';
                break;
            }

            try {
                $this->_instances[$sig] = Horde_Prefs::factory($driver, $scope, $opts, $params);
            } catch (Horde_Prefs_Exception $e) {
                if (empty($_SESSION['prefs_cache']['unavailable'])) {
                    $_SESSION['prefs_cache']['unavailable'] = true;
                    if (isset($GLOBALS['notification'])) {
                        $GLOBALS['notification']->push(_("The preferences backend is currently unavailable and your preferences have not been loaded. You may continue to use the system with default preferences."));
                    }
                }
                $this->_instances[$sig] = Horde_Prefs::factory('Session', $scope);
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

        $GLOBALS['notification']->push(sprintf(_("The preference \"%s\" could not be saved because its data exceeds the maximum allowable size"), $pref), 'horde.error');
        return true;
    }

}
