<?php
/**
 * The Horde_Kolab_Session class holds additional user details for the current
 * session.
 *
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/** We need the Auth library */
require_once 'Horde/Auth.php';

/**
 * The Horde_Kolab_Session class holds additional user details for the current
 * session.
 *
 * The core user credentials (login, pass) are kept within the Auth module and
 * can be retrieved using <code>Auth::getAuth()</code> respectively
 * <code>Auth::getCredential('password')</code>. Any additional Kolab user data
 * relevant for the user session should be accessed via the Horde_Kolab_Session
 * class.
 *
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
class Horde_Kolab_Session
{
    /**
     * User ID.
     *
     * @var string
     */
    var $user_id;

    /**
     * User UID.
     *
     * @var string
     */
    var $user_uid;

    /**
     * Primary user mail address.
     *
     * @var string
     */
    var $user_mail;

    /**
     * Full name.
     *
     * @var string
     */
    var $user_name = '';

    /**
     * True if the Kolab_Server login was successfull.
     *
     * @var boolean|PEAR_Error
     */
    var $auth;

    /**
     * The connection parameters for the IMAP server.
     *
     * @var array|PEAR_Error
     */
    var $_imap_params;

    /**
     * Our IMAP connection.
     *
     * @var Horde_Kolab_IMAP
     */
    var $_imap;

    /**
     * The free/busy server for the current user.
     *
     * @var array|PEAR_Error
     */
    var $freebusy_server;

    /**
     * Constructor.
     *
     * @param string $user        The session will be setup for the user with
     *                            this ID.
     * @param array  $credentials An array of login credentials. For Kolab,
     *                            this must contain a "password" entry.
     */
    public function __construct($user = null, $credentials = null)
    {
        global $conf;

        if (empty($user)) {
            $user = Horde_Auth::getAuth();
            if (empty($user)) {
                $user = 'anonymous';
            } else if (!strpos($user, '@')) {
                $user = $user . '@' . (!empty($_SERVER['SERVER_NAME']) ?
                                       $_SERVER['SERVER_NAME'] : 'localhost');
            }
        }

        $this->user_id      = $user;
        $this->_imap_params = array();

        $user_object = null;

        if ($user != 'anonymous') {
            $server = $this->getServer($user, $credentials);
            $this->user_uid = $server->uid;
            $user_object    = $server->fetch();

            if (empty($conf['kolab']['imap']['allow_special_users'])
                && !is_a($user_object, 'Horde_Kolab_Server_Object_Kolab_User')) {
                throw new Horde_Kolab_Server_Exception(_('Access to special Kolab users is denied.'));
            }
            if (isset($conf['kolab']['server']['deny_group'])) {
                $dn = $server->gidForMail($conf['kolab']['server']['deny_group']);
                if (empty($dn)) {
                    Horde::logMessage('The Kolab configuration setting $conf[\'kolab\'][\'server\'][\'deny_group\'] holds a non-existing group!',
                                      __FILE__, __LINE__, PEAR_LOG_WARNING);
                } else if (in_array($dn, $user_object->getGroups())) {
                    throw new Horde_Kolab_Server_Exception(_('You are member of a group that may not login on this server.'));
                }
            }
            if (isset($conf['kolab']['server']['allow_group'])) {
                $dn = $server->gidForMail($conf['kolab']['server']['allow_group']);
                if (empty($dn)) {
                    Horde::logMessage('The Kolab configuration setting $conf[\'kolab\'][\'server\'][\'allow_group\'] holds a non-existing group!',
                                      __FILE__, __LINE__, PEAR_LOG_WARNING);
                } else if (!in_array($dn, $user_object->getGroups())) {
                    throw new Horde_Kolab_Server_Exception(_('You are no member of a group that may login on this server.'));
                }
            }

        $this->auth = true;

            $result = $user_object->get(Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_MAIL);
            if (!empty($result) && !is_a($result, 'PEAR_Error')) {
                $this->user_mail = $result;
            }

            $result = $user_object->get(Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_SID);
            if (!empty($result) && !is_a($result, 'PEAR_Error')) {
                $this->user_id = $result;
            }

            $result = $user_object->get(Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_FNLN);
            if (!empty($result) && !is_a($result, 'PEAR_Error')) {
                $this->user_name = $result;
            }

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
        }

        if (empty($this->user_mail)) {
            $this->user_mail = $user;
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
    function __sleep()
    {
        $properties = get_object_vars($this);
        unset($properties['_imap']);
        $properties = array_keys($properties);
        return $properties;
    }

    /**
     * Get the Kolab Server connection.
     *
     * @param string $user        The session will be setup for the user with
     *                            this ID.
     * @param array  $credentials An array of login credentials. For Kolab,
     *                            this must contain a "password" entry.
     *
     * @return Horde_Kolab_Server|PEAR_Error The Kolab Server connection.
     */
    function getServer($user = null, $credentials = null)
    {
        $params = array();
        if ($this->user_uid) {
            $params['uid']  = $this->user_uid;
            $params['pass'] = Horde_Auth::getCredential('password');
        } else if (isset($user)) {
            $params['user'] = $user;
            if (isset($credentials['password'])) {
                $params['pass'] = $credentials['password'];
            } else {
                $params['pass'] = Horde_Auth::getCredential('password');
            }
        }
        return Horde_Kolab_Server::singleton($params);
    }

    /**
     * Get the IMAP connection parameters.
     *
     * @return array|PEAR_Error The IMAP connection parameters.
     */
    function getImapParams()
    {
        return $this->_imap_params;
    }

    /**
     * Create an IMAP connection.
     *
     * @return Horde_Kolab_IMAP|PEAR_Error The IMAP connection.
     */
    function getImap()
    {
        if (!isset($this->_imap)) {
            $params = $this->getImapParams();
            if (is_a($params, 'PEAR_Error')) {
                return $params;
            }

            $imap = Horde_Kolab_IMAP::singleton($params['hostspec'],
                                                $params['port'], true, false);
            if (is_a($imap, 'PEAR_Error')) {
                return $imap;
            }

            $result = $imap->connect(Horde_Auth::getAuth(),
                                     Horde_Auth::getCredential('password'));
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
            $this->_imap = $imap;
        }
        return $this->_imap;
    }

    /**
     * Attempts to return a reference to a concrete Horde_Kolab_Session instance.
     *
     * It will only create a new instance if no Horde_Kolab_Session instance
     * currently exists or if a user ID has been specified that does not match the
     * user ID/user mail of the current session.
     *
     * @param string $user        The session will be setup for the user with
     *                            this ID.
     * @param array  $credentials An array of login credentials. For Kolab,
     *                            this must contain a "password" entry.
     *
     * @static
     *
     * @return Horde_Kolab_Session  The concrete Session reference.
     */
    static public function singleton($user = null, $credentials = null, $destruct = false)
    {
        static $session;

        if (!isset($session)) {
            /* Horde_Kolab_Server currently has no caching so we mainly
             * cache some user information here as reading this data
             * may be expensive when running in a multi-host
             * environment. */
            $hs      = Horde_SessionObjects::singleton();
            $session = $hs->query('kolab_session');
        }

        if (empty($user)) {
            $user = Horde_Auth::getAuth();
        }

        if ($destruct || empty($session)
            || ($user != $session->user_mail && $user != $session->user_id)) {
            $session = new Horde_Kolab_Session($user, $credentials);
        }

        register_shutdown_function(array(&$session, 'shutdown'));

        return $session;
    }

    /**
     * Stores the object in the session cache.
     *
     * @return NULL
     */
    function shutdown()
    {
        $session = Horde_SessionObjects::singleton();
        $session->overwrite('kolab_session', $this, false);
    }
}
