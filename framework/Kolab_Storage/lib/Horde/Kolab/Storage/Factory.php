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
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
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
     * Folder type instances.
     *
     * @var array
     */
    private $_folder_types;

    /**
     * Create the storage handler.
     *
     * @param Horde_Kolab_Storage_Driver $driver The required driver for access
     *                                           to the storage backend.
     *
     * @return Horde_Kolab_Storage The storage handler.
     */
    public function create(Horde_Kolab_Storage_Driver $driver)
    {
        return new Horde_Kolab_Storage_Base($driver, $this);
    }

    /**
     * Create the storage handler based on a set of configuration parameters.
     *
     * @param array $params The parameters for the backend access. See create().
     * <pre>
     *  - driver : The type of backend driver. One of "mock", "php", "pear",
     *             "horde", "horde-socket", and "roundcube".
     *  - params : Backend specific connection parameters.
     *  - logger : An optional log handler.
     *    
     * </pre>
     *
     * @return Horde_Kolab_Storage The storage handler.
     */
    public function createFromParams(array $params)
    {
        $storage = new Horde_Kolab_Storage_Base(
            $this->createDriverFromParams($params),
            $this
        );
        if (!empty($params['logger'])) {
            $storage = new Horde_Kolab_Storage_Decorator_Log(
                $storage, $params['logger']
            );
        }
        return $storage;
    }

    /**
     * Create the storage backend driver based on a set of parameters.
     *
     * @param array $params The parameters for the backend access. See create().
     * <pre>
     *  - driver  : The type of backend driver. One of "mock", "php", "pear",
     *              "horde", "horde-socket", and "roundcube".
     *  - params  : Backend specific connection parameters.
     *  - logger  : An optional log handler.
     *  - timelog : An optional time keeping log handler.
     * </pre>
     *
     * @return Horde_Kolab_Storage_Driver The storage handler.
     */
    public function createDriverFromParams(array $params)
    {
        if (!isset($params['driver'])) {
            throw new Horde_Kolab_Storage_Exception(
                Horde_Kolab_Storage_Translation::t(
                    'Missing "driver" parameter!'
                )
            );
        }
        if (isset($params['params'])) {
            $config = (array) $params['params'];
        } else {
            $config = array();
        }
        $config = array_merge(
            array('host' => 'localhost', 'port' => 143), $config
        );
        if (!empty($params['timelog'])) {
            $timer = new Horde_Support_Timer();
            $timer->push();
        }
        switch ($params['driver']) {
        case 'mock':
            $config['data'] = array('user/test' => array());
            $driver = new Horde_Kolab_Storage_Driver_Mock($this, $config);
            break;
        case 'horde':
            $config['hostspec'] = $config['host'];
            unset($config['host']);
            //$config['debug'] = '/tmp/imap.log';
            $driver = new Horde_Kolab_Storage_Driver_Imap(
                new Horde_Imap_Client_Socket(
                    $config
                ),
                $this
            );
            break;
        case 'horde-php':
            $config['hostspec'] = $config['host'];
            unset($config['host']);
            $driver = new Horde_Kolab_Storage_Driver_Imap(
                new Horde_Imap_Client_Cclient(
                    $config
                ),
                $this
            );
            break;
        case 'php':
            $driver = new Horde_Kolab_Storage_Driver_Cclient($this, $config);
            break;
        case 'pear':
            $client = new Net_IMAP($config['host'], $config['port']);
            Horde_Kolab_Storage_Exception_Pear::catchError(
                $client->login($config['username'], $config['password'])
            );
            $driver = new Horde_Kolab_Storage_Driver_Pear(
                $client, $this, $config
            );
            break;
        case 'roundcube':
            $client = new rcube_imap_generic();
            //$client->setDebug(true);
            $client->connect(
                $config['host'], $config['username'], $config['password'],
                array(
                    'debug_mode' => false,
                    'ssl_mode' => false,
                    'port' => $config['port'],
                    'timeout' => 0,
                    'force_caps' => false,
                )
            );
            $driver = new Horde_Kolab_Storage_Driver_Rcube(
                $client, $this, $config
            );
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
        if (!empty($params['logger'])) {
            $driver = new Horde_Kolab_Storage_Driver_Decorator_Log(
                $driver, $params['logger']
            );
        }
        if (!empty($params['timelog'])) {
            $params['timelog']->info(
                sprintf(
                    'REQUEST OUT IMAP: %s ms [construct]',
                    floor($timer->pop() * 1000)
                )
            );
            $driver = new Horde_Kolab_Storage_Driver_Decorator_Timer(
                $driver, $timer, $params['timelog']
            );
        }
        return $driver;
    }

    /**
     * Create the specified list query type.
     *
     * @param string                   $name The query name.
     * @param Horde_Kolab_Storage_List $list The list that should be queried.
     *
     * @return Horde_Kolab_Storage_Query A query handler.
     *
     * @throws Horde_Kolab_Storage_Exception In case the requested query is not supported.
     */
    public function createListQuery($name, Horde_Kolab_Storage_List $list)
    {
        if (class_exists($name)) {
            $query = new $name($list);
        } else if (strpos($name, 'Horde_Kolab_Storage_List_Query_') === false) {
            $query = $this->createListQuery(
                'Horde_Kolab_Storage_List_Query_' . $name, $list
            );
        } else {
            throw new Horde_Kolab_Storage_Exception(sprintf('No such query "%s"!', $name));
        }
        if (method_exists($query, 'setFactory')) {
            $query->setFactory($this);
        }
        $list->registerQuery($query);
        return $query;
    }

    /**
     * Create a namespace handler.
     *
     * @param string $type   The namespace type.
     * @param array  $params The parameters for the namespace. See 
     *
     * @return Horde_Kolab_Storage_Folder_Namespace The namespace handler.
     */
    public function createNamespace($type, array $params = array())
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
        return new $class($params);
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
}