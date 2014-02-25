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
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
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
     * Contains the storage driver used by the Cache object.
     *
     * @since 2.5.0
     *
     * @var Horde_Cache_Storage
     */
    public $storage;

    /**
     * Return the Horde_Cache:: instance.
     *
     * @return Horde_Cache
     * @throws Horde_Cache_Exception
     */
    public function create(Horde_Injector $injector)
    {
        $driver = $this->getDriverName();

        $params = Horde::getDriverConfig('cache', $driver);
        if (isset($GLOBALS['conf']['cache']['default_lifetime'])) {
            $params['lifetime'] = $GLOBALS['conf']['cache']['default_lifetime'];
        }
        $params['compress'] = true;
        $params['logger'] = $injector->getInstance('Horde_Core_Log_Wrapper');

        switch ($driver) {
        case 'hashtable':
        // DEPRECATED
        case 'memcache':
            $params['hashtable'] = $injector->getInstance('Horde_Core_HashTable_Wrapper');
            $driver = 'hashtable';
            break;

        case 'nosql':
            $nosql = $injector->getInstance('Horde_Core_Factory_Nosql')->create('horde', 'cache');
            if ($nosql instanceof Horde_Mongo_Client) {
                $params['mongo_db'] = $nosql;
                $driver = 'Horde_Cache_Storage_Mongo';
            }
            break;

        case 'sql':
            $params['db'] = $injector->getInstance('Horde_Core_Factory_Db')->create('horde', 'cache');
            break;
        }

        $storage = $this->storage = $this->_getStorage($driver, $params);

        if (!empty($GLOBALS['conf']['cache']['use_memorycache']) &&
            in_array($driver, array('file', 'sql'))) {
            if ((strcasecmp($GLOBALS['conf']['cache']['use_memorycache'], 'Hashtable') === 0) ||
                (strcasecmp($GLOBALS['conf']['cache']['use_memorycache'], 'Memcache') === 0)) {
                $params['hashtable'] = $injector->getInstance('Horde_Core_HashTable_Wrapper');
            }

            $storage = new Horde_Cache_Storage_Stack(array(
                'stack' => array(
                    $this->_getStorage($GLOBALS['conf']['cache']['use_memorycache'], $params),
                    $this->storage
                )
            ));
        }

        return new Horde_Cache($storage, $params);
    }

    /**
     * Return the driver name.
     *
     * @since 2.5.0
     *
     * @return string  Lowercase driver name.
     */
    public function getDriverName()
    {
        global $conf;

        $driver = empty($conf['cache']['driver'])
            ? 'null'
            : Horde_String::lower($conf['cache']['driver']);

        switch ($driver) {
        case 'none':
            $driver = 'null';
            break;

        case 'xcache':
            if (Horde_Cli::runningFromCLI()) {
                $driver = 'null';
            }
            break;
        }

        return $driver;
    }

    /**
     * Create the Cache storage backend.
     *
     * @param string $driver  The storage driver name.
     * @param array  $params  The storage backend parameters.
     *
     * @return Horde_Cache_Storage_Base  A cache storage backend.
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
