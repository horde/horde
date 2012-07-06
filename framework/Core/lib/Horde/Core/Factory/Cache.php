<?php
/**
 * A Horde_Injector:: based Horde_Cache:: factory.
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
 * A Horde_Injector:: based Horde_Cache:: factory.
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
class Horde_Core_Factory_Cache extends Horde_Core_Factory_Injector
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

        $lc_driver = Horde_String::lower($driver);

        if (Horde_Cli::runningFromCLI() && $lc_driver == 'xcache') {
            $driver = 'Null';
            $lc_driver = 'null';
        }

        $params = Horde::getDriverConfig('cache', $driver);
        if (isset($GLOBALS['conf']['cache']['default_lifetime'])) {
            $params['lifetime'] = $GLOBALS['conf']['cache']['default_lifetime'];
        }
        $params['compress'] = !empty($GLOBALS['conf']['cache']['compress']);
        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

        switch ($lc_driver) {
        case 'memcache':
            $params['memcache'] = $injector->getInstance('Horde_Memcache');
            break;

        case 'sql':
            $params['db'] = $injector->getInstance('Horde_Db_Adapter');
            break;
        }

        $storage = $this->_getStorage($driver, $params);

        if (!empty($GLOBALS['conf']['cache']['use_memorycache']) &&
            in_array($lc_driver, array('file', 'sql'))) {
            if (strcasecmp($GLOBALS['conf']['cache']['use_memorycache'], 'Memcache') === 0) {
                $params['memcache'] = $injector->getInstance('Horde_Memcache');
            }

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
     * Create the Cache storage backend.
     *
     * @param string $driver  The storage driver name.
     * @param array  $params  The storage backend parameters.
     *
     * @return Horde_Cache_Storage_Base A cache storage backend.
     */
    protected function _getStorage($driver, $params)
    {
        try {
            $class = $this->_getDriverName($driver, 'Horde_Cache_Storage');
        } catch (Horde_Exception $e) {
            $class = 'Horde_Cache_Storage_Null';
        }

        return new $class($params);
    }

}
