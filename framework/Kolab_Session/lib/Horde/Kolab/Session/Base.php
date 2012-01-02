<?php
/**
 * The Horde_Kolab_Session_Base class holds user details retrieved via
 * LDAP in the current session.
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
 * The Horde_Kolab_Session_Base class holds user details retrieved via
 * LDAP in the current session.
 *
 * @todo Rename from Horde_Kolab_Session_Base ->
 * Horde_Kolab_Session_Ldap at some point.
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
class Horde_Kolab_Session_Base extends Horde_Kolab_Session_Abstract
{
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
     * Constructor.
     *
     * @param Horde_Kolab_Server $server  The connection to the Kolab user
     *                                    database.
     * @param array              $params  Kolab configuration settings.
     */
    public function __construct(
        Horde_Kolab_Server_Composite $server,
        array $params
    )
    {
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
    private function _initMail(Horde_Kolab_Server_Object_Hash $user)
    {
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
    private function _initUid(Horde_Kolab_Server_Object_Hash $user)
    {
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
    private function _initName(Horde_Kolab_Server_Object_Hash $user)
    {
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
    private function _initImapServer(Horde_Kolab_Server_Object_Hash $user)
    {
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
    private function _initFreebusyServer(Horde_Kolab_Server_Object_Hash $user)
    {
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
}
