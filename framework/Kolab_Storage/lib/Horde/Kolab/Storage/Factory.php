<?php
/**
 * A generic factory for the various Kolab_Storage classes.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * A generic factory for the various Kolab_Storage classes.
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
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
        $storage = new Horde_Kolab_Storage_Base(
            $this->createDriver(),
            $this->createQuerySet(),
            $this
        );
        if (!empty($this->_params['cache'])) {
            $storage = new Horde_Kolab_Storage_Decorator_Cache(
                $storage,
                $this->createCache(),
                $this
            );
        }
        if (!empty($this->_params['logger'])) {
            $storage = new Horde_Kolab_Storage_Decorator_Log(
                $storage, $this->_params['logger']
            );
        }
        $storage = new Horde_Kolab_Storage_Decorator_Synchronization(
            $storage, new Horde_Kolab_Storage_Synchronization()
        );
        return $storage;
    }

    /**
     * Create the query handler.
     *
     * @return Horde_Kolab_Storage_QuerySet The query handler.
     */
    public function createQuerySet()
    {
        return new Horde_Kolab_Storage_QuerySet($this);
    }

    /**
     * Create the storage backend driver.
     *
     * @return Horde_Kolab_Storage_Driver The storage handler.
     */
    public function createDriver()
    {
        if (!isset($this->_params['driver'])) {
            throw new Horde_Kolab_Storage_Exception(
                Horde_Kolab_Storage_Translation::t(
                    'Missing "driver" parameter!'
                )
            );
        }
        if (isset($this->_params['params'])) {
            $config = (array) $this->_params['params'];
        } else {
            $config = array();
        }
        if (empty($config['host'])) {
            $config['host'] = 'localhost';
        }
        if (empty($config['port'])) {
            $config['port'] = 143;
        }
        if (!empty($this->_params['timelog'])) {
            $timer = new Horde_Support_Timer();
            $timer->push();
        }
        switch ($this->_params['driver']) {
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
                    $this->_params['driver']
                )
            );
        }

        $parser = new Horde_Kolab_Storage_Data_Parser_Structure($driver);
        $format = new Horde_Kolab_Storage_Data_Format_Mime($this, $parser);
        $parser->setFormat($format);
        $driver->setParser($parser);

        if (!empty($this->_params['logger'])) {
            $driver = new Horde_Kolab_Storage_Driver_Decorator_Log(
                $driver, $this->_params['logger']
            );
        }
        if (!empty($this->_params['timelog'])) {
            $driver = new Horde_Kolab_Storage_Driver_Decorator_Timer(
                $driver, $timer, $this->_params['timelog']
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
    public function createFolder(
        Horde_Kolab_Storage_List $list,
        $folder
    ) {
        return new Horde_Kolab_Storage_Folder_Base(
            $list, $folder
        );
    }

    /**
     * Create the specified list query type.
     *
     * @param string                   $name   The query name.
     * @param Horde_Kolab_Storage_List $list   The list that should be queried.
     * @param array                    $params Additional parameters provided
     *                                         to the query constructor.
     *
     * @return Horde_Kolab_Storage_Query A query handler.
     *
     * @throws Horde_Kolab_Storage_Exception In case the requested query is not supported.
     */
    public function createListQuery($name, Horde_Kolab_Storage_List $list, $params = array())
    {
        return $this->_createQuery($name, $list, $params);
    }

    /**
     * Create the specified data query type.
     *
     * @param string                   $name   The query name.
     * @param Horde_Kolab_Storage_Data $data   The data that should be queried.
     * @param array                    $params Additional parameters provided
     *                                         to the query constructor.
     *
     * @return Horde_Kolab_Storage_Query A query handler.
     *
     * @throws Horde_Kolab_Storage_Exception In case the requested query is not supported.
     */
    public function createDataQuery($name, Horde_Kolab_Storage_Data $data, $params = array())
    {
        return $this->_createQuery($name, $data, $params);
    }

    /**
     * Create the specified query type.
     *
     * @param string $name   The query name.
     * @param mixed  $data   The data that should be queried.
     * @param array  $params Additional parameters provided
     *                       to the query constructor.
     *
     * @return Horde_Kolab_Storage_Query A query handler.
     *
     * @throws Horde_Kolab_Storage_Exception In case the requested query is not supported.
     */
    private function _createQuery($name, $data, $params = array())
    {
        if (class_exists($name)) {
            $constructor_params = array_merge(
                array('factory' => $this), $params
            );
            $query = new $name($data, $constructor_params);
        } else {
            throw new Horde_Kolab_Storage_Exception(sprintf('No such query "%s"!', $name));
        }
        return $query;
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