<?php
/**
 * A generic factory for the various Kolab_Storage classes.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * A generic factory for the various Kolab_Storage classes.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Factory
{
    /**
     * A set of parameters for this factory.
     *
     * @var array
     */
    private $_params;

    /**
     * Stores the driver once created.
     *
     * @todo Cleanup. Extract a driver factory to be placed in the driver
     * namespace and allow to inject the driver within the storage factory.
     *
     * @var Horde_Kolab_Storage_Driver
     */
    private $_driver;

    /**
     * Constructor.
     *
     * @param array $params A set of parameters.
     * <pre>
     *  - driver : The type of backend driver. One of "mock", "php", "pear",
     *             "horde", "horde-socket", and "roundcube".
     *  - params : Backend specific connection parameters.
     *  - logger : An optional log handler.
     *  - timelog : An optional time keeping log handler.
     *  - format : Array
     *     - factory: Name of the format parser factory class.
     * </pre>
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Create the storage handler.
     *
     * @return Horde_Kolab_Storage The storage handler.
     */
    public function create()
    {
        if (isset($this->_params['storage'])) {
            $sparams = $this->_params['storage'];
        } else {
            $sparams = array();
        }
        if (isset($this->_params['queries'])) {
            $sparams['queries'] = $this->_params['queries'];
        }
        if (isset($this->_params['queryset'])) {
            $queryset = $this->_params['queryset'];
            $sparams['queryset'] = $this->_params['queryset'];
        } else {
            $queryset = array();
        }
        $cache = $this->createCache();
        if (!empty($this->_params['cache'])) {
            $storage = new Horde_Kolab_Storage_Cached(
                $this->createDriver(),
                new Horde_Kolab_Storage_QuerySet_Cached($this, $queryset, $cache),
                $this,
                $cache,
                $this->_params['logger'],
                $sparams
            );
        } else {
            $storage = new Horde_Kolab_Storage_Uncached(
                $this->createDriver(),
                new Horde_Kolab_Storage_QuerySet_Uncached($this, $queryset),
                $this,
                $cache,
                $this->_params['logger'],
                $sparams
            );
        }
        $storage = new Horde_Kolab_Storage_Decorator_Synchronization(
            $storage, new Horde_Kolab_Storage_Synchronization()
        );
        return $storage;
    }

    /**
     * Create the storage backend driver.
     *
     * @param array $params Any parameters that should overwrite the default
     *                      parameters provided in the factory constructor.
     *
     * @return Horde_Kolab_Storage_Driver The storage handler.
     */
    public function createDriver($params = array())
    {
        $params = array_merge($this->_params, $params);
        if (!isset($params['driver'])) {
            throw new Horde_Kolab_Storage_Exception(
                Horde_Kolab_Storage_Translation::t(
                    'Missing "driver" parameter!'
                )
            );
        }
        if (isset($params['params'])) {
            $config = (array)$params['params'];
        } else {
            $config = array();
        }
        if (empty($config['host'])) {
            $config['host'] = 'localhost';
        }
        if (empty($config['port'])) {
            $config['port'] = 143;
        }
        if (isset($this->_params['log'])
            && (in_array('debug', $this->_params['log'])
                || in_array('driver_time', $this->_params['log']))) {
            $timer = new Horde_Support_Timer();
            $timer->push();
        }
        switch ($params['driver']) {
        case 'mock':
            if (!isset($config['data'])) {
                $config['data'] = array('user/test' => array());
            }
            $driver = new Horde_Kolab_Storage_Driver_Mock($this, $config);
            break;
        case 'horde':
        case 'horde-php':
            $driver = new Horde_Kolab_Storage_Driver_Imap($this, $config);
            break;
        case 'php':
            $driver = new Horde_Kolab_Storage_Driver_Cclient($this, $config);
            break;
        case 'pear':
            $driver = new Horde_Kolab_Storage_Driver_Pear($this, $config);
            break;
        case 'roundcube':
            $driver = new Horde_Kolab_Storage_Driver_Rcube($this, $config);
            break;
        default:
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        'Invalid "driver" parameter "%s". Please use one of "mock", "php", "pear", "horde", "horde-php", and "roundcube"!'
                    ),
                    $params['driver']
                )
            );
        }

        if (isset($this->_params['log'])
            && (in_array('debug', $this->_params['log'])
                || in_array('driver', $this->_params['log']))) {
            $driver = new Horde_Kolab_Storage_Driver_Decorator_Log(
                $driver, $params['logger']
            );
            //$parser->setLogger($params['logger']);
        }
        /* if (!empty($params['ignore_parse_errors'])) { */
        /*     $parser->setLogger(false); */
        /* } */
        if (isset($this->_params['log'])
            && (in_array('debug', $this->_params['log'])
                || in_array('driver_time', $this->_params['log']))) {
            $driver = new Horde_Kolab_Storage_Driver_Decorator_Timer(
                $driver, $timer, $params['logger']
            );
        }
        $this->_driver = $driver;
        return $driver;
    }

    /**
     * Create a namespace handler.
     *
     * @param string $type   The namespace type.
     * @param string $user   The current user.
     * @param array  $params The parameters for the namespace. 
     *
     * @return Horde_Kolab_Storage_Folder_Namespace The namespace handler.
     */
    public function createNamespace($type, $user, array $params = array())
    {
        $class = 'Horde_Kolab_Storage_Folder_Namespace_' . ucfirst($type);
        if (!class_exists($class)) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        'Invalid "namespace" type "%s"!'
                    ),
                    $type
                )
            );
        }
        return new $class($user, $params);
    }

    /**
     * Create the cache handler.
     *
     * @return Horde_Kolab_Storage_Cache The cache handler.
     */
    public function createCache()
    {
        if (isset($this->_params['cache'])) {
            $params = $this->_params['cache'];
        } else {
            $params = array();
        }
        if ($params instanceOf Horde_Cache) {
            return new Horde_Kolab_Storage_Cache($params);
        } else {
            $cache = new Horde_Cache(
                new Horde_Cache_Storage_File($params),
                array('lifetime' => 0)
            );
        }
        return new Horde_Kolab_Storage_Cache(
            $cache
        );
    }

    /**
     * Create the history handler.
     *
     * @param string $user The current user.
     *
     * @return Horde_History The history handler.
     */
    public function createHistory($user)
    {
        if (isset($this->_params['history']) &&
            $this->_params['history'] instanceOf Horde_History) {
            return $this->_params['history'];
        }
        return new Horde_History_Mock($user);
    }

    public function getDriver()
    {
        return $this->_driver;
    }
}