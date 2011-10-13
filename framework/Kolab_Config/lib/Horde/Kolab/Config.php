<?php
/**
 * The Kolab Server configuration handler.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Config
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Config
 */

/**
 * The Kolab Server configuration handler.
 *
 * Copyright 2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Config
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Config
 */
class Horde_Kolab_Config
implements ArrayAccess
{
    /**
     * The path to the directory holding the configuration files.
     *
     * @var string
     */
    private $_directory;

    /**
     * Name of the global configuration file.
     *
     * @var string
     */
    private $_global;

    /**
     * Name of the local configuration file.
     *
     * @var string
     */
    private $_local;

    /**
     * The configuration data.
     *
     * @var array
     */
    private $_data;

    /**
     * Constructor.
     *
     * @param string $directory The path to the directory holding the
     *                          configuration files.
     * @param string $global    Name of the global configuration file.
     * @param string $local     Name of the local configuration file.
     */
    public function __construct(
        $directory = '@kolab_server_dir@/etc/kolab',
        $global = 'kolab.globals',
        $local = 'kolab.conf'
    ) {
        if ($directory === '@kolab_server_dir@/etc/kolab') {
            $this->_directory = '/kolab/etc/kolab';
        } else {            
            $this->_directory = realpath($directory);
        }
        $this->_global    = $global;
        $this->_local     = $local;
    }

    /**
     * Read the configuration files.
     *
     * @return NULL
     */
    public function read()
    {
        if (!file_exists($this->_getGlobalConfigFilePath())
            && !file_exists($this->_getLocalConfigFilePath())) {
            throw new Horde_Kolab_Config_Exception(
                'No configuration files found in ' . $this->_directory . '.'
            );
        }

        if (file_exists($this->_getGlobalConfigFilePath())) {
            $this->_loadConfigurationFile(
                $this->_getGlobalConfigFilePath()
            );
        }

        if (file_exists($this->_getLocalConfigFilePath())) {
            $this->_loadConfigurationFile(
                $this->_getLocalConfigFilePath()
            );
        }
    }

    private function _loadConfigurationFile($path)
    {
        if ($this->_data === null) {
            $this->_data = array();
        }

        $fh = fopen($path, 'r');

        while (!feof($fh)) {
            $line = trim(fgets($fh));
            if ($line && preg_match('/^([^:#]+):(.*)/', $line, $matches)) {
                $this->_data[trim($matches[1])] = trim($matches[2]);
            }
        }
        fclose($fh);
    }

    /**
     * Return the path to the global configuration file.
     *
     * @return NULL
     */
    private function _getGlobalConfigFilePath()
    {
        return $this->_directory . DIRECTORY_SEPARATOR . $this->_global;
    }

    /**
     * Return the path to the local configuration file.
     *
     * @return NULL
     */
    private function _getLocalConfigFilePath()
    {
        return $this->_directory . DIRECTORY_SEPARATOR . $this->_local;
    }

    /**
     * Initialize this object if this has not happened yet.
     *
     * @return NULL
     */
    private function _init()
    {
        if ($this->_data === null) {
            $this->read();
        }
    }

    /**
     * Return the value for the given array key.
     *
     * @param string $key The key.
     *
     * @return mixed The value for the given key.
     */
    public function offsetGet($key)
    {
        if (!$this->offsetExists($key)) {
            throw new Horde_Kolab_Config_Exception(
                'Parameter "' . $key . '" has no value!'
            );
        }
        return $this->_data[$key];
    }

    /**
     * Does the requested array value exist in the configuration?
     *
     * @param string $key The key.
     *
     * @return boolean True if the configuration value exists.
     */
    public function offsetExists($key)
    {
        $this->_init();
        if (!is_string($key) || empty($key)) {
            throw new InvalidArgumentException(
                'The key must be a non-empty string!'
            );
        }
        return isset($this->_data[$key]);
    }

    /**
     * Set the given key to the provided value.
     *
     * @param string $key   The key.
     * @param mixed  $value The value that should be stored.
     *
     * @return NULL
     */
    public function offsetSet($key, $value)
    {
    }

    /**
     * Delete the value identified by the given key.
     *
     * @param string $key The key.
     *
     * @return NULL
     */
    public function offsetUnset($key)
    {
    }
}