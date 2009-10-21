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

/** We need the Auth library */
require_once 'Horde/Auth.php';

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
     * User ID.
     *
     * @var string
     */
    private $_user_id;

    /**
     * User GUID in the kolab user database.
     *
     * @var string
     */
    private $_user_guid;

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
     * The connection parameters for the Kolab storage system.
     *
     * @var array
     */
    private $_storage_params;

    /**
     * The free/busy server for the current user.
     *
     * @var array|PEAR_Error
     */
    private $_freebusy_server;

    /**
     * Kolab configuration parameters.
     *
     * @var array
     */
    private $_params;

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
     * Provides authentication information for this object.
     *
     * @var Horde_Kolab_Session_Auth
     */
    private $_auth;

    /**
     * Constructor.
     *
     * @param string             $user   The session will be setup for the user
     *                                   with this ID.
     * @param Horde_Kolab_Server $server The connection to the Kolab user
     *                                   database.
     * @param array              $params Kolb configuration settings.
     */
    public function __construct(
        $user,
        Horde_Kolab_Server $server,
        array $params
    ) {
        $this->_server  = $server;
        $this->_params  = $params;

        if (empty($user)) {
            $user = $this->getAnonymousUser();
        }

        $this->_user_id = $user;
    }

    /**
     * Return the name of the anonymous user if set.
     *
     * @return string The name of the anonymous user.
     */
    public function getAnonymousUser()
    {
        if (isset($this->_params['anonymous']['user'])) {
            return $this->_params['anonymous']['user'];
        }
        return '';
    }

    /**
     * Return the password of the anonymous user if set.
     *
     * @return string The password of the anonymous user.
     *
     * @throws Horde_Kolab_Session_Exception If the password is not set.
     */
    public function getAnonymousPass()
    {
        if (isset($this->_params['anonymous']['pass'])) {
            return $this->_params['anonymous']['pass'];
        }
        throw new Horde_Kolab_Session_Exception(
            'No password for the anonymous user!'
        );
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
    public function connect(array $credentials)
    {
        if (empty($credentials)) {
            $password = $this->getAnonymousPass();
        } else if (isset($credentials['password'])) {
            $password = $credentials['password'];
        } else {
            throw new Horde_Kolab_Session_Exception('Missing password!');
        }

        try {
            $this->_server->connect($this->_user_id, $password);
            $this->user_guid = $this->_server->server->getGuid();
            $user_object     = $this->_server->objects->fetch();
        } catch (Horde_Kolab_Server_Exception $e) {
            throw new Horde_Kolab_Session_Exception($e);
        }

        $this->_initMail($user_object);
        $this->_initUid($user_object);
        $this->_initName($user_object);
        $this->_initHosts($user_object);
    }

    /**
     * Initialize the user mail address.
     *
     * @param Horde_Kolab_Server_Object $user The user object.
     *
     * @return NULL
     */
    private function _initMail(Horde_Kolab_Server_Object $user)
    {
        try {
            $this->_user_mail = $user->getExternal('Mail');
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
    private function _initUid(Horde_Kolab_Server_Object $user)
    {
        try {
            $this->_user_uid = $user_object->getExternal('Uid');
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
    private function _initName(Horde_Kolab_Server_Object $user)
    {
        try {
            $this->_user_name = $user_object->getExternal('Fnln');
        } catch (Horde_Kolab_Server_Exception_Novalue $e) {
            $this->_user_name = $this->_user_id;
        }
    }

    /**
     * Initialize the user host settings.
     *
     * @param Horde_Kolab_Server_Object $user The user object.
     *
     * @return NULL
     *
     * @todo Adapt to new structure of this class.
     */
    private function _initHosts(Horde_Kolab_Server_Object $user)
    {
        $result = $user_object->getServer('imap');
        if (!empty($result) && !is_a($result, 'PEAR_Error')) {
            $server = explode(':', $result, 2);
            if (!empty($server[0])) {
                $this->_imap_params['hostspec'] = $server[0];
            }
            if (!empty($server[1])) {
                $this->_imap_params['port'] = $server[1];
            }
        }

        $result = $user_object->getServer('freebusy');
        if (!empty($result) && !is_a($result, 'PEAR_Error')) {
            $this->freebusy_server = $result;
        }

        if (!isset($this->_imap_params['hostspec'])) {
            if (isset($conf['kolab']['imap']['server'])) {
                $this->_imap_params['hostspec'] = $conf['kolab']['imap']['server'];
            } else {
                $this->_imap_params['hostspec'] = 'localhost';
            }
        }

        if (!isset($this->_imap_params['port'])) {
            if (isset($conf['kolab']['imap']['port'])) {
                $this->_imap_params['port'] = $conf['kolab']['imap']['port'];
            } else {
                $this->_imap_params['port'] = 143;
            }
        }

        $this->_imap_params['protocol'] = 'imap/notls/novalidate-cert';
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
     * Return a connection to the Kolab storage system.
     *
     * @return Horde_Kolab_Storage The storage connection.
     *
     * @todo Adapt to new structure of this class.
     */
    public function getStorage()
    {
        if (!isset($this->_imap)) {
            $params = $this->getImapParams();
            if (is_a($params, 'PEAR_Error')) {
                return $params;
            }

            $imap = Horde_Kolab_IMAP::singleton(
                $params['hostspec'],
                $params['port'], true, false
            );
            if (is_a($imap, 'PEAR_Error')) {
                return $imap;
            }

            $result = $imap->connect(
                Horde_Auth::getAuth(),
                Horde_Auth::getCredential('password')
            );
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
            $this->_imap = $imap;
        }
        return $this->_imap;
    }

    /**
     * Set the handler that provides getCurrentUser() for this instance.
     *
     * @param Horde_Kolab_Session_Auth $auth The authentication handler.
     *
     * @return NULL
     */
    public function setAuth(Horde_Kolab_Session_Auth $auth)
    {
        $this->_auth = $auth;
    }

    /**
     * Get the handler that provides getCurrentUser() for this instance.
     *
     * @return Horde_Kolab_Session_Auth The authentication handler.
     */
    public function getAuth()
    {
        if (empty($this->_auth)) {
            throw new Horde_Kolab_Session_Exception('Undefined auth handler!');
        }
        return $this->_auth;
    }

    /**
     * Does the current session still match the authentication information?
     *
     * @param string $user The user the session information is being requested
     *                     for. This is usually empty, indicating the current
     *                     user.
     *
     * @return boolean True if the session is still valid.
     */
    public function isValid($user = null)
    {
        if (empty($this->_auth)) {
            return false;
        }
        $current = $this->_auth->getCurrentUser();
        if (empty($current)) {
            $current = $this->getAnonymousUser();
        }
        if ($current != $this->user_mail) {
            return false;
        }
        if (empty($user)) {
            return true;
        }
        if ($user != $this->user_mail && $user != $this->user_uid) {
            return false;
        }
        return true;
    }
}
