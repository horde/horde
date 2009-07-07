<?php
/**
 * The Horde_Auth_Smbclient class provides an smbclient implementation of
 * the Horde authentication system.
 *
 * Required parameters:
 * <pre>
 * 'domain'          The domain name to authenticate with.
 * 'hostspec'        IP, DNS Name, or NetBios Name of the SMB server to
 *                   authenticate with.
 * 'smbclient_path'  The location of the smbclient(1) utility.
 * </pre>
 *
 * Optional parameters:
 * <pre>
 * 'group' - Group name that the user must be a member of. Will be
 *           ignored if the value passed is a zero length string.
 * </pre>
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Jon Parise <jon@horde.org>
 * @author  Marcus I. Ryan <marcus@riboflavin.net>
 * @package Horde_Auth
 */
class Horde_Auth_Smbclient extends Horde_Auth_Driver
{
    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        /* Ensure we've been provided with all of the necessary parameters. */
        Horde::assertDriverConfig($params, 'auth',
            array('hostspec', 'domain', 'smbclient_path'),
            'authentication smbclient');

        parent::__construct($params);
    }

    /**
     * Find out if the given set of login credentials are valid.
     *
     * @param string $userId      The userId to check.
     * @param array $credentials  An array of login credentials.
     *
     * @throws Horde_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        if (empty($credentials['password'])) {
            throw new Horde_Exception('', Horde_Auth::REASON_BADLOGIN);
        }

        /* Authenticate. */
        $cmdline = implode(' ', array($this->_params['smbclient_path'],
                                      '-L',
                                      $this->_params['hostspec'],
                                      '-W',
                                      $this->_params['domain'],
                                      '-U',
                                      $userId));

        $sc = popen($cmdline, 'w');
        if ($sc === false) {
            throw new Horde_Exception(_("Unable to execute smbclient."));
        }

        fwrite($sc, $credentials['password']);
        $rc = pclose($sc);

        if ((int)($rc & 0xff) != 0) {
            throw new Horde_Exception('', Horde_Auth::REASON_BADLOGIN);
        }
    }

}
