<?php
/**
 * The Horde_Auth_Shibboleth class only provides transparent authentication
 * based on the headers set by a Shibboleth SP.  Note that this class does
 * not provide any actual SP functionality, it just takes the username
 * from the HTTP headers that should be set by the Shibboleth SP.
 *
 * Copyright 9Star Research, Inc. 2006 http://www.protectnetwork.org/
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Cassio Nishiguchi <cassio@protectnetwork.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Auth
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
     * @param array $params  Parameters:
     * <pre>
     * 'password_header' - (string) Name of the header holding the password of
     *                     the logged in user.
     * 'password_holder' - (string) Where the hordeauth password is stored.
     * 'password_preference' - (string) Name of the Horde preference holding
     *                         the password of the logged in user.
     * 'username_header' - (string) [REQUIRED] Name of the header holding the
     *                     username of the logged in user.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['username_header'])) {
            throw new InvalidArgumentException('Missing username_header parameter.');
        }

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
        throw new Horde_Auth_Exception('Unsupported.');
    }

    /**
     * Automatic authentication: check if the username is set in the
     * configured header.
     *
     * @return boolean  Whether or not the client is allowed.
     */
    public function transparent()
    {
        if (empty($_SERVER[$this->_params['username_header']])) {
            return false;
        }

        $username = $_SERVER[$this->_params['username_header']];

        // Remove scope from username, if present.
        $this->setCredential('userId', $this->_removeScope($username));

        // Set password for hordeauth login.
        switch ($this->_params['password_holder']) {
        case 'header':
            $this->setCredential('credentials', array(
                'password' => $_SERVER[$this->_params['password_header']]
            ));
            break;

        case 'preferences':
            $this->setCredential('credentials', array(
                'password' => $_SERVER[$this->_params['password_preference']]
            ));
            break;
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
        return ($pos !== false)
            ? substr($username, 0, $pos)
            : $username;
    }

}
