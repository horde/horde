<?php
/**
 * A persistent file-based driver for simulating a Kolab user database stored in
 * LDAP.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * This class provides a persistant class for testing the Kolab Server DB.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Connection_File 
extends Horde_Kolab_Server_Connection_Mock
{

    /**
     * The file for storing the database data.
     *
     * @var string
     */
    private $_file;

    /**
     * Set configuration parameters.
     *
     * @param array $params The parameters.
     *
     * @return NULL
     */
    public function setParams(array $params)
    {
        if (isset($params['file'])) {
            $this->_file = $params['file'];
        }

        parent::setParams($params);
    }

    /**
     * Get the file parameter.
     *
     * @return NULL
     */
    private function _getFile()
    {
        if (empty($this->_file)) {
            throw new Horde_Kolab_Server_Exception('The file based driver requires a \'file\' parameter.');
        }
        return $this->_file;
    }
    
    /**
     * Load the current state of the database.
     *
     * @return NULL
     */
    protected function load()
    {
        $raw_data = file_get_contents($this->_getFile());
        if (!$raw_data === false) {
            $data = @unserialize($raw_data);
            if ($data !== false) {
                $this->data = $data;
            } else {
                $error = error_get_last();
                if (isset($this->logger)) {
                    $this->logger->warn(sprintf('Horde_Kolab_Server_file failed to read the database from %s. Error was: %s',
                                                $this->_getFile(), $error['message']));
                }
                $this->data = array();
            }
        }
    }

    /**
     * Store the current state of the database.
     *
     * @return NULL
     */
    protected function store()
    {
        $raw_data = serialize($this->data);
        $result = @file_put_contents($this->_getFile(), $raw_data);
        if ($result === false) {
            $error = error_get_last();
            if (isset($this->logger)) {
                $this->logger->warn(sprintf('Horde_Kolab_Server_file failed to store the database in %s. Error was: %s',
                                            $this->_getFile(),  $error['message']));
            }
        }
    }

    /**
     * Cleans the current state of the database.
     *
     * @return NULL
     */
    public function clean()
    {
        unlink($this->_getFile());
        $this->data = array();
        $this->store();
    }

    /**
     * Returns the path to the storage location of the database.
     *
     * @return string The path to the database.
     */
    public function getStoragePath()
    {
        return $this->_getFile();
    }
}
