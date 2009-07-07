<?php
/**
 * The Horde_Auth_Ftp class provides an FTP implementation of the Horde
 * authentication system.
 *
 * Optional parameters:
 * <pre>
 * 'hostspec' - (string) The hostname or IP address of the FTP server.
 *              DEFAULT: 'localhost'
 * 'port' - (integer) The server port to connect to.
 *          DEFAULT: 21
 * </pre>
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Max Kalika <max@horde.org>
 * @package Horde_Auth
 */
class Horde_Auth_Ftp extends Horde_Auth_Driver
{
    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     *
     * @throws Horde_Exception
     */
    public function __construct($params = array())
    {
        if (!Horde_Util::extensionExists('ftp')) {
            throw new Horde_Exception('Horde_Auth_Ftp: Required FTP extension not found. Compile PHP with the --enable-ftp switch.');
            return false;
        }

        $params = array_merge(array(
            'hostspec' => 'localhost',
            'port' => 21
        ), $params);

        parent::__construct($params);
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userId      The userId to check.
     * @param array $credentials  An array of login credentials. For FTP,
     *                            this must contain a password entry.
     *
     * @throws Horde_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        $ftp = @ftp_connect($this->_params['hostspec'], $this->_params['port']);

        $res = $ftp && @ftp_login($ftp, $userId, $credentials['password']);
        @ftp_quit($ftp);

        if ($res) {
            throw new Horde_Exception('', Horde_Auth::REASON_BADLOGIN);
        }
    }

}
