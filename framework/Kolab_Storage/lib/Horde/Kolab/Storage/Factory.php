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
     * Folder type instances.
     *
     * @var array
     */
    private $_folder_types;

    /**
     * Format parser instances.
     *
     * @var array
     */
    private $_formats;

    /**
     * The format parser factory.
     *
     * @var Horde_Kolab_Format_Factory
     */
    private $_format_factory;

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
        if (isset($params['format']['factory'])) {
            $this->_format_factory = $params['format']['factory'];
        } else {
            $this->_format_factory = new Horde_Kolab_Format_Factory();
        }
    }

    /**
     * Create the storage handler.
     *
     * @return Horde_Kolab_Storage The storage handler.
     */
    public function create()
    {
        if (isset($this->_params['queryset'])) {
            $queryset = $this->_params['queryset'];
        } else {
            $queryset = array();
        }
        if (isset($this->_params['storage'])) {
            $sparams = $this->_params['storage'];
        } else {
            $sparams = array();
        }
        if (!empty($this->_params['logger'])) {
            $sparams['logger'] = $this->_params['logger'];
        }
        if (!empty($this->_params['cache'])) {
            $cache = $this->createCache();
            $storage = new Horde_Kolab_Storage_Cached(
                $this->createDriver(),
                new Horde_Kolab_Storage_QuerySet_Cached($this, $queryset, $cache),
                $this,
                $cache,
                $sparams
            );
        } else {
            $storage = new Horde_Kolab_Storage_Uncached(
                $this->createDriver(),
                new Horde_Kolab_Storage_QuerySet_Uncached($this, $queryset),
                $this,
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
        if (!empty($params['timelog'])) {
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

        $parser = new Horde_Kolab_Storage_Data_Parser_Structure($driver);
        $format = new Horde_Kolab_Storage_Data_Format_Mime($this, $parser);
        $parser->setFormat($format);
        $driver->setParser($parser);

        if (!empty($params['logger'])) {
            $driver = new Horde_Kolab_Storage_Driver_Decorator_Log(
                $driver, $params['logger']
            );
            $parser->setLogger($params['logger']);
        }
        if (!empty($params['ignore_parse_errors'])) {
            $parser->setLogger(false);
        }
        if (!empty($params['timelog'])) {
            $driver = new Horde_Kolab_Storage_Driver_Decorator_Timer(
                $driver, $timer, $params['timelog']
            );
        }
        return $driver;
    }

    /**
     * Returns a representation for the requested folder.
     *
     * @param Horde_Kolab_Storage_List $list   The folder list handler.
     * @param string                   $folder The path of the folder to return.
     *
     * @return Horde_Kolab_Storage_Folder The folder representation.
     */
    public function createFolder(Horde_Kolab_Storage_List $list,
                                 $folder)
    {
        return new Horde_Kolab_Storage_Folder_Base(
            $list, $folder
        );
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
     * Create a folder type handler.
     *
     * @param string $annotation The folder type annotation value.
     *
     * @return Horde_Kolab_Storage_Folder_Type The folder type handler.
     */
    public function createFoldertype($annotation)
    {
        if (!isset($this->_folder_types[$annotation])) {
            $this->_folder_types[$annotation] = new Horde_Kolab_Storage_Folder_Type($annotation);
        }
        return $this->_folder_types[$annotation];
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

    /**
     * Create a Kolab format handler.
     *
     * @param string $format  The format that the handler should work with.
     * @param string $type    The object type that should be handled.
     * @param string $version The format version.
     *
     * @return Horde_Kolab_Format The format parser.
     */
    public function createFormat($format, $type, $version)
    {
        $key = md5(serialize(array($format, $type, $version)));
        if (!isset($this->_formats[$key])) {
            if (isset($this->_params['format'])) {
                $params = $this->_params['format'];
            } else {
                $params = array();
            }
            $params['version'] = $version;
            $this->_formats[$key] = $this->_format_factory->create(
                $format, $type, $params
            );
        }
        return $this->_formats[$key];
    }
}