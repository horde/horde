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

        $params = Horde::getDriverConfig('cache', $driver);
        if (isset($GLOBALS['conf']['cache']['default_lifetime'])) {
            $params['lifetime'] = $GLOBALS['conf']['cache']['default_lifetime'];
        }
        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

        $lc_driver = Horde_String::lower($driver);
        switch ($lc_driver) {
        case 'Memcache':
            $params['memcache'] = $injector->getInstance('Horde_Memcache');
            break;

        case 'Sql':
            $params['db'] = $injector->getInstance('Horde_Db_Adapter');
            break;
        }

        $storage = $this->_getStorage($driver, $params);

        if (!empty($GLOBALS['conf']['cache']['use_memorycache']) &&
            in_array($lc_driver, array('File', 'Sql'))) {
            if (strcasecmp($GLOBALS['conf']['cache']['use_memorycache'], 'Memcache') === 0) {
                $params['memcache'] = $injector->getInstance('Horde_Memcache');
            }

            $cname = $this->_driverToClassname($GLOBALS['conf']['cache']['use_memorycache']);
            $storage = new Horde_Cache_Storage_Stack(array(
                'stack' => array(
                    $this->_getStorage($GLOBALS['conf']['cache']['use_memorycache'], $params),
                    $storage
                )
            ));
        }

        return new Horde_Cache($storage, $params);
    }

    /**
     */
    protected function _getStorage($driver, $params)
    {
        $driver = ucfirst(basename($driver));
        $classname = 'Horde_Cache_Storage_' . $driver;

        if (!class_exists($classname)) {
            $classname = 'Horde_Cache_Storage_Null';
        }

        return new $classname($params);
    }

}
