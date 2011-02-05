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
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
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
     * Charset used by this driver.
     *
     * @var string
     */
    protected $charset = 'UTF7-IMAP';

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
     * Set a parameter setting for this connection.
     *
     * @param string $key    The parameter key.
     * @param mixed  $value  The parameter value.
     *
     * @return NULL
     */
    public function setParam($key, $value)
    {
        $this->_params[$key] = $value;
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
     * Encode IMAP path names from  UTF-8 to the driver charset.
     *
     * @param string $path The UTF-8 encoded path name.
     *
     * @return string The path name in the driver charset.
     */
    protected function encodePath($path)
    {
        return Horde_String::convertCharset($path, 'UTF-8', $this->charset);
    }

    /**
     * Decode IMAP path names from the driver charset to UTF-8.
     *
     * @param string $path The the driver charset encoded path name.
     *
     * @return string The path name in UTF-8.
     */
    protected function decodePath($path)
    {
        return Horde_String::convertCharset($path,  $this->charset, 'UTF-8');
    }

    /**
     * Decode a list of IMAP path names from the driver charset to UTF-8.
     *
     * @param array $list The the driver charset encoded path names.
     *
     * @return array The path names in UTF-8.
     */
    protected function decodeList(array $list)
    {
        return array_map(array($this, 'decodePath'), $list);
    }

    /**
     * Decode the keys of a list of IMAP path names from the driver charset to
     * UTF-8.
     *
     * @param array $list The list with the driver charset encoded path names as
     *                    keys.
     *
     * @return array The list with path names in UTF-8 as keys.
     */
    protected function decodeListKeys(array $list)
    {
        $result = array();
        foreach ($list as $key => $value) {
            $result[$this->decodePath($key)] = $value;
        }
        return $result;
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
                    'config', $this->getAuth(), $this->_params['namespaces']
                );
            } else {
                $this->_namespace = $this->_factory->createNamespace(
                    'fixed', $this->getAuth()
                );
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