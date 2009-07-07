<?php
/**
 * The Horde_Auth_Auto class transparently logs users in to Horde using ONE
 * username, either defined in the config or defaulting to 'horde_user'.
 * This is only for use in testing or behind a firewall; it should NOT be
 * used on a public, production machine.
 *
 * Optional parameters:
 * <pre>
 * 'password' - (string) The password to record in the user's credentials.
 *              DEFAULT: none
 * 'requestuser' - (boolean) If true, allow username to be passed by GET, POST
 *                 or cookie.
 *                DEFAULT: No
 * 'username' - (string) The username to authenticate everyone as.
 *              DEFAULT: 'horde_user'
 * </pre>
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Auth
 */
class Horde_Auth_Auto extends Horde_Auth_Driver
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
        parent::__construct($params);

        if (!isset($this->_params['username'])) {
            $this->_params['username'] = 'horde_user';
        }
    }

    /**
     * Automatic authentication: Set the user allowed IP block.
     *
     * @return boolean  Whether or not the client is allowed.
     */
    protected function _transparent()
    {
        $username = (!empty($this->_params['requestuser']) && isset($_REQUEST['username']))
            ? $_REQUEST['username']
            : $this->_params['username'];

        return Horde_Auth::setAuth($username, array(
            'transparent' => 1,
            'password' => isset($this->_params['password']) ? $this->_params['password'] : null
        ));
    }

}
