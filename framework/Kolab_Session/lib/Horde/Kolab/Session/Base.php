<?php
/**
 * The Horde_Kolab_Session class holds user details in the current session.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */

/**
 * The Horde_Kolab_Session class holds user details in the current session.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */
class Horde_Kolab_Session_Base implements Horde_Kolab_Session
{
    /**
     * Kolab configuration parameters.
     *
     * @var array
     */
    private $_params;

    /**
     * The session data.
     *
     * @var array
     */
    private $_data;

    /**
     * User ID.
     *
     * @var string
     */
    private $_user_id;

    /**
     * Kolab UID of the user.
     *
     * @var string
     */
    private $_user_uid;

    /**
     * Primary Kolab mail address of the user.
     *
     * @var string
     */
    private $_user_mail;

    /**
     * Full name.
     *
     * @var string
     */
    private $_user_name;

    /**
     * The imap server for the current user.
     *
     * @var string
     */
    private $_imap_server = false;

    /**
     * The free/busy server for the current user.
     *
     * @var string
     */
    private $_freebusy_server;

    /**
     * The kolab user database connection.
     *
     * @var Horde_Kolab_Server
     */
    private $_server;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Server $server  The connection to the Kolab user
     *                                    database.
     * @param array              $params  Kolab configuration settings.
     */
    public function __construct(
        Horde_Kolab_Server_Composite $server,
        array $params
    ) {
        $this->_server  = $server;
        $this->_params  = $params;
    }

    /**
     * Try to connect the session handler.
     *
     * @param string $user_id     The user ID to connect with.
     * @param array  $credentials An array of login credentials. For Kolab,
     *                            this must contain a "password" entry.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Session_Exception If the connection failed.
     */
    public function connect($user_id = null, array $credentials = null)
    {
        $this->_data['user']['id'] = $user_id;
        if (isset($credentials['password'])) {
            $password = $credentials['password'];
        } else {
            $password = '';
        }

        try {
            $this->_server->connect($this->_data['user']['id'], $password);
            $user_object = $this->_server->objects->fetch();
        } catch (Horde_Kolab_Server_Exception_Bindfailed $e) {
            throw new Horde_Kolab_Session_Exception_Badlogin('Invalid credentials!', 0, $e);
        } catch (Horde_Kolab_Server_Exception $e) {
            throw new Horde_Kolab_Session_Exception('Login failed!', 0, $e);
        }

        $this->_initMail($user_object);
        $this->_initUid($user_object);
        $this->_initName($user_object);
        $this->_initImapServer($user_object);
        $this->_initFreebusyServer($user_object);
    }

    /**
     * Initialize the user mail address.
     *
     * @param Horde_Kolab_Server_Object $user The user object.
     *
     * @return NULL
     */
    private function _initMail(
        Horde_Kolab_Server_Object_Hash $user
    ) {
        try {
            $this->_data['user']['mail'] = $user->getSingle('mail');;
        } catch (Horde_Kolab_Server_Exception_Novalue $e) {
            $this->_data['user']['mail'] = $this->_data['user']['id'];
        }
    }

    /**
     * Initialize the user uid.
     *
     * @param Horde_Kolab_Server_Object $user The user object.
     *
     * @return NULL
     */
    private function _initUid(
        Horde_Kolab_Server_Object_Hash $user
    ) {
        try {
            $this->_data['user']['uid'] = $user->getSingle('uid');
        } catch (Horde_Kolab_Server_Exception_Novalue $e) {
            $this->_data['user']['uid'] = $this->_data['user']['id'];
        }
    }

    /**
     * Initialize the user name.
     *
     * @param Horde_Kolab_Server_Object $user The user object.
     *
     * @return NULL
     */
    private function _initName(
        Horde_Kolab_Server_Object_Hash $user
    ) {
        try {
            $this->_data['user']['name'] = $user->getSingle('Firstnamelastname');
        } catch (Horde_Kolab_Server_Exception_Novalue $e) {
            $this->_data['user']['name'] = $this->_data['user']['id'];
        }
    }

    /**
     * Initialize the users imap server FQDN.
     *
     * @param Horde_Kolab_Server_Object $user The user object.
     *
     * @return NULL
     */
    private function _initImapServer(
        Horde_Kolab_Server_Object_Hash $user
    ) {
        try {
            $this->_data['imap']['server'] = $user->getSingle('kolabHomeServer');
        } catch (Horde_Kolab_Server_Exception_Novalue $e) {
            if (isset($this->_params['imap']['server'])) {
                $this->_data['imap']['server'] = $this->_params['imap']['server'];
            } else {
                $this->_data['imap']['server'] = 'localhost';
            }
        }
    }

    /**
     * Initialize the users free/busy URL.
     *
     * @param Horde_Kolab_Server_Object $user The user object.
     *
     * @return NULL
     */
    private function _initFreebusyServer(
        Horde_Kolab_Server_Object_Hash $user
    ) {
        try {
            $fb_server = $user->getSingle('kolabFreebusyHost');
        } catch (Horde_Kolab_Server_Exception_Novalue $e) {
            if (isset($this->_params['freebusy']['url'])) {
                $this->_data['fb']['server'] = $this->_params['freebusy']['url'];
                return;
            } else {
                $fb_server = $this->_data['imap']['server'];
            }
        }

        if (isset($this->_params['freebusy']['url_format'])) {
            $fb_format = $this->_params['freebusy']['url_format'];
        } else {
            $fb_format = 'http://%s/freebusy';
        }

        $this->_data['fb']['server'] = sprintf($fb_format, $fb_server);
    }

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
