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
     * Automatic authentication: Check if the username is set in the
     * configured header.
     *
     * @return boolean  Whether or not the client is allowed.
     * @throws Horde_Auth_Exception
     */
    protected function _transparent()
    {
        if (empty($_SERVER[$this->_params['username_header']])) {
            throw new Horde_Auth_Exception(_("Shibboleth authentication not available."));
        }

        $username = $_SERVER[$this->_params['username_header']];

        // Remove scope from username, if present.
        $pos = strrpos($username, '@');
        if ($pos !== false) {
            $username = substr($username, 0, $pos);
        }

        if (!Horde_Auth::setAuth($username, array('transparent' => 1))) {
            return false;
        }

        // Set password for hordeauth login.
        if ($this->_params['password_holder'] == 'header') {
            Horde_Auth::setCredential('password', $_SERVER[$this->_params['password_header']]);
        } elseif ($this->_params['password_holder'] == 'preferences') {
            Horde_Auth::setCredential('password', $GLOBALS['prefs']->getValue($this->_params['password_preference']));
        }

        return true;
    }

}
