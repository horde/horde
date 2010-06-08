<?php
/**
 * The Horde_Auth_Auto class transparently logs users in to Horde using ONE
 * username, either defined in the config or defaulting to 'horde_user'.
 * This is only for use in testing or behind a firewall; it should NOT be
 * used on a public, production machine.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Auth
 */
class Horde_Auth_Auto extends Horde_Auth_Base
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
     * @param array $params  Optional parameters:
     * <pre>
     * 'password' - (string) The password to record in the user's credentials.
     *              DEFAULT: none
     * 'requestuser' - (boolean) If true, allow username to be passed by GET,
     *                 POST or cookie.
     *                 DEFAULT: No
     * 'username' - (string) The username to authenticate everyone as.
     *              DEFAULT: 'horde_user'
     * </pre>
     */
    public function __construct(array $params = array())
    {
        $params = array_merge(array(
            'password' => '',
            'requestuser' => false,
            'username' => 'horde_user'
        ), $params);

        parent::__construct($params);
    }

    /**
     * Horde_Auth_Exception should pass a message string (if any) in the message
     * field, and the REASON_* constant in the code field (defaults to
     * REASON_MESSAGE).
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
     * Automatic authentication: Set the user allowed IP block.
     *
     * @return boolean  Whether or not the client is allowed.
     */
    public function transparent()
    {
        $this->_credentials['userId'] = (!empty($this->_params['requestuser']) && isset($_REQUEST['username']))
            ? $_REQUEST['username']
            : $this->_params['username'];
        $this->_credentials['credentials'] = array(
            'password' => isset($this->_params['password']) ? $this->_params['password'] : null
        );

        return true;
    }

}
