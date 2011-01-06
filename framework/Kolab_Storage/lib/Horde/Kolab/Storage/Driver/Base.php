<?php
/**
 * The base driver definition for accessing Kolab storage drivers.
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
 * The base driver definition for accessing Kolab storage drivers.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
abstract class Horde_Kolab_Storage_Driver_Base
implements Horde_Kolab_Storage_Driver
{
    /**
     * Factory for generating helper objects.
     *
     * @var Horde_Kolab_Storage_Factory
     */
    private $_factory;

    /**
     * Additional connection parameters.
     *
     * @var array
     */
    private $_params;

    /**
     * Memory cache for the namespace of this driver.
     *
     * @var Horde_Kolab_Storage_Folder_Namespace
     */
    protected $_namespace;

    /**
     * The backend to use.
     *
     * @var mixed
     */
    private $_backend;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Factory $factory A factory for helper objects.
     * @param array $params                        Connection parameters.
     */
    public function __construct(
        Horde_Kolab_Storage_Factory $factory,
        $params = array()
    ) {
        $this->_factory = $factory;
        if (isset($params['backend'])) {
            $this->setBackend($params['backend']);
        }
        $this->_params  = $params;
    }

    /**
     * Returns the actual backend driver.
     *
     * If there is no driver set the driver should be constructed within this
     * method.
     *
     * @return mixed The backend driver.
     */
    public function getBackend()
    {
        if ($this->_backend === null) {
            $this->_backend = $this->createBackend();
        }
        return $this->_backend;
    }

    /**
     * Set the backend driver.
     *
     * @param mixed $backend The driver that should be used.
     *
     * @return NULL
     */
    public function setBackend($backend)
    {
        $this->_backend = $backend;
    }

    /**
     * Return all parameter settings for this connection.
     *
     * @return array The parameters.
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Return a parameter setting for this connection.
     *
     * @param string $key     The parameter key.
     * @param mixed  $default An optional default value.
     *
     * @return mixed The parameter value.
     */
    public function getParam($key, $default = null)
    {
        return isset($this->_params[$key]) ? $this->_params[$key] : $default;
    }

    /**
     * Return the id of the user currently authenticated.
     *
     * @return string The id of the user that opened the IMAP connection.
     */
    public function getAuth()
    {
        return $this->getParam('username');
    }

    /**
     * Return the unique connection id.
     *
     * @return string The connection id.
     */
    public function getId()
    {
        return $this->getAuth() . '@'
            . $this->getParam('host') . ':'
            . $this->getParam('port');
    }

    /**
     * Return the factory.
     *
     * @return Horde_Kolab_Storage_Factory The factory.
     */
    protected function getFactory()
    {
        return $this->_factory;
    }

    /**
     * Retrieve the namespace information for this connection.
     *
     * @return Horde_Kolab_Storage_Folder_Namespace The initialized namespace handler.
     */
    public function getNamespace()
    {
        if ($this->_namespace === null) {
            if (isset($this->_params['namespaces'])) {
                $this->_namespace = $this->_factory->createNamespace(
                    'config', $this->_params['namespaces']
                );
            } else {
                $this->_namespace = $this->_factory->createNamespace('fixed');
            }
        }
        return $this->_namespace;
    }

    /**
     * Split a name for the METADATA extension into the correct syntax for the
     * older ANNOTATEMORE version.
     *
     * @param string $name  A name for a metadata entry.
     *
     * @return array  A list of two elements: The entry name and the value
     *                type.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getAnnotateMoreEntry($name)
    {
        if (substr($name, 0, 7) == '/shared') {
            return array(substr($name, 7), 'value.shared');
        } else if (substr($name, 0, 8) == '/private') {
            return array(substr($name, 8), 'value.priv');
        }

        $this->_exception('Invalid METADATA entry: ' . $name);
    }

}