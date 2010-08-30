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
     * Mark the session as connected.
     *
     * @var true
     */
    private $_connected = false;

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
        $this->_user_id = $user_id;
        if (isset($credentials['password'])) {
            $password = $credentials['password'];
        } else {
            $password = '';
        }

        try {
            $this->_server->connect($this->_user_id, $password);
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

        $this->_connected = true;
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
            $this->_user_mail = $user->getSingle('mail');;
        } catch (Horde_Kolab_Server_Exception_Novalue $e) {
            $this->_user_mail = $this->_user_id;
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
            $this->_user_uid = $user->getSingle('uid');
        } catch (Horde_Kolab_Server_Exception_Novalue $e) {
            $this->_user_uid = $this->_user_id;
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
            $this->_user_name = $user->getSingle('Firstnamelastname');
        } catch (Horde_Kolab_Server_Exception_Novalue $e) {
            $this->_user_name = $this->_user_id;
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
            $this->_imap_server = $user->getSingle('kolabHomeServer');
        } catch (Horde_Kolab_Server_Exception_Novalue $e) {
            if (isset($this->_params['imap']['server'])) {
                $this->_imap_server = $this->_params['imap']['server'];
            } else {
                $this->_imap_server = 'localhost';
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
                $this->_freebusy_server = $this->_params['freebusy']['url'];
                return;
            } else {
                $fb_server = $this->_imap_server;
            }
        }

        if (isset($this->_params['freebusy']['url_format'])) {
            $fb_format = $this->_params['freebusy']['url_format'];
        } else {
            $fb_format = 'http://%s/freebusy';
        }

        $this->_freebusy_server = sprintf($fb_format, $fb_server);
    }

    /**
     * Returns the properties that need to be serialized.
     *
     * @return array  List of serializable properties.
     */
    public function __sleep()
    {
        $properties = get_object_vars($this);
        unset($properties['_server']);
        $properties = array_keys($properties);
        return $properties;
    }

    /**
     * Return the user id used for connecting the session.
     *
     * @return string The user id.
     */
    public function getId()
    {
        return $this->_user_id;
    }

    /**
     * Return the users mail address.
     *
     * @return string The users mail address.
     */
    public function getMail()
    {
        return $this->_user_mail;
    }

    /**
     * Return the users uid.
     *
     * @return string The users uid.
     */
    public function getUid()
    {
        return $this->_user_uid;
    }

    /**
     * Return the users name.
     *
     * @return string The users name.
     */
    public function getName()
    {
        return $this->_user_name;
    }

    /**
     * Return the imap server.
     *
     * @return string The imap host for the current user.
     */
    public function getImapServer()
    {
        return $this->_imap_server;
    }

    /**
     * Return the freebusy server.
     *
     * @return string The freebusy host for the current user.
     */
    public function getFreebusyServer()
    {
        return $this->_freebusy_server;
    }
}
