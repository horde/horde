<?php
/**
 * The Horde_Auth_Http class transparently logs users in to Horde using
 * already present HTTP authentication headers.
 *
 * The 'encryption' parameter specifies what kind of passwords are in
 * the .htpasswd file. The supported options are 'crypt-des' (standard
 * crypted htpasswd entries) and 'aprmd5'. This information is used if
 * you want to directly authenticate users with this driver, instead
 * of relying on transparent auth.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Auth
 */
class Horde_Auth_Http extends Horde_Auth_Base
{
    /**
     * An array of capabilities, so that the driver can report which
     * operations it supports and which it doesn't.
     *
     * @var array
     */
    protected $_capabilities = array(
        'transparent' => true
    );

    /**
     * Array of usernames and hashed passwords.
     *
     * @var array
     */
    protected $_users = array();

    /**
     * Constructor.
     *
     * @param array $params  A hash containing parameters.
     */
    public function __construct($params = array())
    {
        parent::__construct($params);

        // Default to DES passwords.
        if (empty($this->_params['encryption'])) {
            $this->_params['encryption'] = 'crypt-des';
        }

        if (!empty($this->_params['htpasswd_file'])) {
            $users = file($this->_params['htpasswd_file']);
            if (is_array($users)) {
                // Enable the list users capability.
                $this->_capabilities['list'] = true;

                // Put users into alphabetical order.
                sort($users);

                foreach ($users as $line) {
                    list($user, $pass) = explode(':', $line, 2);
                    $this->_users[trim($user)] = trim($pass);
                }
            }
        }
    }

    /**
     * Find out if a set of login credentials are valid. Only supports
     * htpasswd files with DES passwords right now.
     *
     * @param string $userId       The userId to check.
     * @param array  $credentials  An array of login credentials. For IMAP,
     *                             this must contain a password entry.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        if (empty($credentials['password']) ||
            empty($this->_users[$userId])) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }

        $hash = Horde_Auth::getCryptedPassword($credentials['password'], $this->_users[$userId], $this->_params['encryption'], !empty($this->_params['show_encryption']));

        if ($hash != $this->_users[$userId]) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }
    }

    /**
     * List all users in the system.
     *
     * @return array  The array of userIds.
     */
    public function listUsers()
    {
        return array_keys($this->_users);
    }

    /**
     * Automatic authentication: Find out if the client has HTTP
     * authentication info present.
     *
     * @return boolean  Whether or not the client is allowed.
     * @throws Horde_Auth_Exception
     */
    protected function _transparent()
    {
        if (!empty($_SERVER['PHP_AUTH_USER']) &&
            !empty($_SERVER['PHP_AUTH_PW'])) {
            return Horde_Auth::setAuth(Horde_Util::dispelMagicQuotes($_SERVER['PHP_AUTH_USER']), array('password' => Horde_Util::dispelMagicQuotes($_SERVER['PHP_AUTH_PW']), 'transparent' => 1));
        }

        throw new Horde_Auth_Exception(_("HTTP Authentication not found."));
    }

}
