<?php
/**
 * The Horde_Kolab_Session_Abstract class provides general
 * functionality for the Kolab user session data.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */

/**
 * The Horde_Kolab_Session_Abstract class provides general
 * functionality for the Kolab user session data.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */
abstract class Horde_Kolab_Session_Abstract implements Horde_Kolab_Session
{
    /**
     * The session data.
     *
     * @var array
     */
    protected $_data;

    /**
     * Return the user id used for connecting the session.
     *
     * @return string The user id.
     */
    public function getId()
    {
        if (isset($this->_data['user']['id'])) {
            return $this->_data['user']['id'];
        }
    }

    /**
     * Return the users mail address.
     *
     * @return string The users mail address.
     */
    public function getMail()
    {
        if (isset($this->_data['user']['mail'])) {
            return $this->_data['user']['mail'];
        }
    }

    /**
     * Return the users uid.
     *
     * @return string The users uid.
     */
    public function getUid()
    {
        if (isset($this->_data['user']['uid'])) {
            return $this->_data['user']['uid'];
        }
    }

    /**
     * Return the users name.
     *
     * @return string The users name.
     */
    public function getName()
    {
        if (isset($this->_data['user']['name'])) {
            return $this->_data['user']['name'];
        }
    }

    /**
     * Return the imap server.
     *
     * @return string The imap host for the current user.
     */
    public function getImapServer()
    {
        if (isset($this->_data['imap']['server'])) {
            return $this->_data['imap']['server'];
        }
    }

    /**
     * Return the freebusy server.
     *
     * @return string The freebusy host for the current user.
     */
    public function getFreebusyServer()
    {
        if (isset($this->_data['fb']['server'])) {
            return $this->_data['fb']['server'];
        }
    }

    /**
     * Import the session data from an array.
     *
     * @param array The session data.
     *
     * @return NULL
     */
    public function import(array $session_data)
    {
        $this->_data = $session_data;
    }

    /**
     * Export the session data as array.
     *
     * @return array The session data.
     */
    public function export()
    {
        return $this->_data;
    }

    /**
     * Clear the session data.
     *
     * @return NULL
     */
    public function purge()
    {
        $this->_data = array();
    }
}
