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
     * Create the storage handler.
     *
     * @param Horde_Kolab_Storage_Driver $driver The required driver for access
     *                                           to the storage backend.
     *
     * @return Horde_Kolab_Storage The storage handler.
     */
    public function create(Horde_Kolab_Storage_Driver $driver)
    {
        return new Horde_Kolab_Storage_Base($driver);
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
            $this->createDriverFromParams($params)
        );
        if (isset($params['logger'])) {
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
     *  - driver : The type of backend driver. One of "mock", "php", "pear",
     *             "horde", "horde-socket", and "roundcube".
     *  - params : Backend specific connection parameters.
     *
     *    
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
        switch ($params['driver']) {
        case 'mock':
            $config['data'] = array('user/test' => array());
            return new Horde_Kolab_Storage_Driver_Mock($this, $config);
        case 'horde':
            $config['hostspec'] = $config['host'];
            unset($config['host']);
            return new Horde_Kolab_Storage_Driver_Imap(
                new Horde_Imap_Client_Socket(
                    $config
                ),
                $this
            );
        case 'horde-php':
            $config['hostspec'] = $config['host'];
            unset($config['host']);
            return new Horde_Kolab_Storage_Driver_Imap(
                new Horde_Imap_Client_Cclient(
                    $config
                ),
                $this
            );
        case 'php':
            return new Horde_Kolab_Storage_Driver_Cclient($this, $config);
        case 'pear':
            $client = new Net_IMAP($config['host']);
            Horde_Kolab_Storage_Exception_Pear::catchError(
                $client->login($config['username'], $config['password'])
            );
            return new Horde_Kolab_Storage_Driver_Pear(
                $client, $this, $config
            );
        case 'roundcube':
            $client = new rcube_imap_generic();
            $client->connect(
                $config['host'], $config['username'], $config['password'],
                array(
                    'debug_mode' => false,
                    'ssl_mode' => false,
                    'port' => 143,
                    'timeout' => 0,
                    'force_caps' => false,
                )
            );
            return new Horde_Kolab_Storage_Driver_Rcube(
                $client, $this, $config
            );
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
}