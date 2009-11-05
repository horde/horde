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
 * The core user credentials (login, pass) are kept within the Auth module and
 * can be retrieved using <code>Auth::getAuth()</code> respectively
 * <code>Auth::getCredential('password')</code>. Any additional Kolab user data
 * relevant for the user session should be accessed via the Horde_Kolab_Session
 * class.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
    private $_imap_server;

    /**
     * The free/busy server for the current user.
     *
     * @var string
     */
    private $_freebusy_server;

    /**
     * The connection parameters for the Kolab storage system.
     *
     * @var array
     */
    private $_storage_params;

    /**
     * The kolab user database connection.
     *
     * @var Horde_Kolab_Server
     */
    private $_server;

    /**
     * The connection to the Kolab storage system.
     *
     * @var Horde_Kolab_Storage
     */
    private $_storage;

    /**
     * Constructor.
     *
     * @param string             $user_id The session will be setup for the user
     *                                    with this ID.
     * @param Horde_Kolab_Server $server  The connection to the Kolab user
     *                                    database.
     * @param array              $params  Kolab configuration settings.
     */
    public function __construct(
        $user_id,
        Horde_Kolab_Server_Composite_Interface $server,
        array $params
    ) {
        $this->_user_id = $user_id;
        $this->_server  = $server;
        $this->_params  = $params;
    }

    /**
     * Try to connect the session handler.
     *
     * @param array $credentials An array of login credentials. For Kolab,
     *                           this must contain a "password" entry.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Session_Exception If the connection failed.
     */
    public function connect(array $credentials = null)
    {
        if (isset($credentials['password'])) {
            $password = $credentials['password'];
        } else {
            $password = '';
        }

        try {
            $this->_server->connect($this->_user_id, $password);
            $user_object = $this->_server->objects->fetch();
        } catch (Horde_Kolab_Server_Exception_Bindfailed $e) {
            throw new Horde_Kolab_Session_Exception_Badlogin($e);
        } catch (Horde_Kolab_Server_Exception $e) {
            throw new Horde_Kolab_Session_Exception($e);
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
            $this->_user_uid = $user->getExternal('uid');
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
            $this->_user_name = $user->getExternal('Firstnamelastname');
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
            $this->_imap_server = $user->getExternal('kolabHomeServer');
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
            $fb_server = $user->getExternal('kolabFreebusyHost');
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
        unset($properties['_storage']);
        unset($properties['_auth']);
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
     * Set the user id used for connecting the session.
     *
     * @param string $id The user id.
     *
     * @return NULL
     */
    public function setId($id)
    {
        $this->_user_id = $id;
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

    /**
     * Return a connection to the Kolab storage system.
     *
     * @return Horde_Kolab_Storage The storage connection.
     *
     * @todo Implement
     */
    public function getStorage()
    {
        if (empty($this->_storage)) {
            //@todo: factory?
            $this->_storage = new Horde_Kolab_Storage(
                'Imap',
                //@todo: Use Session_Auth
                array('hostspec' => $this->getImapServer(),
                      'username' => Horde_Auth::getAuth(),
                      'password' => Horde_Auth::getCredential('password'))
            );
        }
        return $this->_storage;
    }
}
