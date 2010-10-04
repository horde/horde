<?php
/**
 * A Horde_Injector:: based Horde_Cache:: factory.
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
 * A Horde_Injector:: based Horde_Cache:: factory.
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
class Horde_Core_Factory_Cache
{
    /**
     * Return the Horde_Cache:: instance.
     *
     * @return Horde_Cache
     * @throws Horde_Cache_Exception
     */
    public function create(Horde_Injector $injector)
    {
        $driver = empty($GLOBALS['conf']['cache']['driver'])
            ? 'Null'
            : $GLOBALS['conf']['cache']['driver'];
        if (strcasecmp($driver, 'None') === 0) {
            $driver = 'Null';
        }

        return $this->_create($driver, $injector);
    }

    /**
     * Return the Horde_Cache_Session:: instance.
     *
     * @return Horde_Cache_Session
     * @throws Horde_Cache_Exception
     */
    public function createSession(Horde_Injector $injector)
    {
        return $this->_create('Session', $injector);
    }

    /**
     * @see create()
     */
    private function _create($driver, $injector)
    {
        $params = Horde::getDriverConfig('cache', $driver);
        if (isset($GLOBALS['conf']['cache']['default_lifetime'])) {
            $params['lifetime'] = $GLOBALS['conf']['cache']['default_lifetime'];
        }

        $logger = $injector->getInstance('Horde_Log_Logger');
        $params['logger'] = $logger;

        $base_params = $params;

        if (strcasecmp($driver, 'Memcache') === 0) {
            $params['memcache'] = $injector->getInstance('Horde_Memcache');
        } elseif (strcasecmp($driver, 'Sql') === 0) {
            $params['db'] = $injector->getInstance('Horde_Db_Adapter');
        }

        if (!empty($GLOBALS['conf']['cache']['use_memorycache']) &&
            ((strcasecmp($driver, 'Sql') === 0) ||
             (strcasecmp($driver, 'File') === 0))) {
            if (strcasecmp($GLOBALS['conf']['cache']['use_memorycache'], 'Memcache') === 0) {
                $base_params['memcache'] = $injector->getInstance('Horde_Memcache');
            }

            $class1 = $this->_driverToClassname($GLOBALS['conf']['cache']['use_memorycache']);
            $class2 = $this->_driverToClassname($driver);
            $params = array(
                'stack' => array(
                    new $class1($base_params),
                    new $class2($params),
                )
            );
            $driver = 'Stack';
        }

        $classname = $this->_driverToClassname($driver);
        return new $classname($params);
    }

    /**
     */
    protected function _driverToClassname($driver)
    {
        $driver = ucfirst(basename($driver));
        $classname = 'Horde_Cache_' . $driver;
        if (!class_exists($classname)) {
            $classname = 'Horde_Cache_Null';
        }

        return $classname;
    }

}
