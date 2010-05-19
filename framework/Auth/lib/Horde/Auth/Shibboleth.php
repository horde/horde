<?php
/**
 * The Horde_Auth_Shibboleth class only provides transparent authentication
 * based on the headers set by a Shibboleth SP.  Note that this class does
 * not provide any actual SP functionality, it just takes the username
 * from the HTTP headers that should be set by the Shibboleth SP.
 *
 * Required Parameters:
 * <pre>
 * 'username_header' - (string) Name of the header holding the username of the
 *                     logged in user.
 * </pre>
 *
 * Optional Parameters:
 * <pre>
 * 'password_header' - (string) Name of the header holding the password of the
 *                     logged in user.
 * 'password_holder' - (string) Where the hordeauth password is stored.
 * 'password_preference' - (string) Name of the Horde preference holding the
 *                         password of the logged in user.
 * </pre>
 *
 * Copyright 9Star Research, Inc. 2006 http://www.protectnetwork.org/
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Cassio Nishiguchi <cassio@protectnetwork.org>
 * @package Horde_Auth
 */
class Horde_Auth_Shibboleth extends Horde_Auth_Base
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
     * Constructor.
     *
     * @param array $params  A hash containing parameters.
     */
    public function __construct($params = array())
    {
        Horde::assertDriverConfig($params, 'auth', array('username_header'), 'authentication Shibboleth');

        $params = array_merge(array(
            'password_header' => '',
            'password_holder' => '',
            'password_preference' => ''
        ), $params);

        parent::__construct($params);
    }

    /**
     * Authentication stub.
     *
     * On failure, Horde_Auth_Exception should pass a message string (if any)
     * in the message field, and the Horde_Auth::REASON_* constant in the code
     * field (defaults to Horde_Auth::REASON_MESSAGE).
     *
     * @param string $userID      The userID to check.
     * @param array $credentials  An array of login credentials.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        throw new Horde_Auth_Exception('Not implemented!');
    }

    /**
     * Check existing auth for triggers that might invalidate it.
     *
     * @return boolean  Is existing auth valid?
     */
    public function checkExistingAuth()
    {
        return !empty($_SERVER[$this->_params['username_header']]) &&
            $this->_removeScope($_SERVER[$this->_params['username_header']]) == Horde_Auth::getAuth();
    }

    /**
     * Automatic authentication: check if the username is set in the
     * configured header.
     *
     * @return boolean  Whether or not the client is allowed.
     */
    protected function _transparent()
    {
        if (empty($_SERVER[$this->_params['username_header']])) {
            return false;
        }

        $username = $_SERVER[$this->_params['username_header']];

        // Remove scope from username, if present.
        $this->_credentials['userId'] = $this->_removeScope($username);

        // Set password for hordeauth login.
        switch ($this->_params['password_holder']) {
        case 'header':
            $this->_credentials['credentials'] = array(
                'password' => $_SERVER[$this->_params['password_header']]
            );
            break;

        case 'preferences':
            $this->_credentials['credentials'] = array(
                'password' => $_SERVER[$this->_params['password_preference']]
            );
        }

        return true;
    }

    /**
     * Removes the scope from the user name, if present.
     *
     * @param string $username  The full user name.
     *
     * @return string  The user name without scope.
     */
    protected function _removeScope($username)
    {
        $pos = strrpos($username, '@');
        if ($pos !== false) {
            $username = substr($username, 0, $pos);
        }
        return $username;
    }

}
