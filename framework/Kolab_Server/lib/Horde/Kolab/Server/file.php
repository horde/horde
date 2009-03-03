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
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_file extends Horde_Kolab_Server_test
{

    /**
     * The file for storing the database data.
     *
     * @var string
     */
    private $_file;

    /**
     * Construct a new Horde_Kolab_Server object.
     *
     * @param array $params Parameter array.
     */
    public function __construct($params = array())
    {
        if (isset($params['file'])) {
            $this->_file = $params['file'];
        } else {
            $this->_file = Horde::getTempFile('Horde_Kolab_Server', false);
        }
        parent::__construct($params);
    }

    
    /**
     * Load the current state of the database.
     *
     * @return NULL
     */
    protected function load()
    {
        $raw_data = file_get_contents($this->_file);
        $data = @unserialize($raw_data);
        if ($data !== false) {
            $this->_data = $data;
        } else {
            $error = error_get_last();
            Horde::logMessage(sprintf('Horde_Kolab_Server_file failed to read the database from %s. Error was: %s',
                                      $this->_file, $error['message']), __FILE__,
                              __LINE__, PEAR_LOG_WARNING);
            $this->_data = array();
        }
    }

    /**
     * Store the current state of the database.
     *
     * @return NULL
     */
    protected function store()
    {
        $raw_data = serialize($this->_data);
        $result = @file_put_contents($this->_file, $this->_data);
        if ($result === false) {
            $error = error_get_last();
            Horde::logMessage(sprintf('Horde_Kolab_Server_file failed to store the database in %s. Error was: %s',
                                      $this->_file,  $error['message']), __FILE__,
                              __LINE__, PEAR_LOG_WARNING);
        }
    }

    /**
     * Cleans the current state of the database.
     *
     * @return NULL
     */
    public function clean()
    {
        unlink($this->_file);
	$this->_data = array();
	$this->store();
    }

    /**
     * Returns the path to the storage location of the database.
     *
     * @return string The path to the database.
     */
    public function getStoragePAth()
    {
        return $this->_file;
    }
}
