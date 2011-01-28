<?php
/**
 * The Horde_Auth_Smbclient class provides an smbclient implementation of
 * the Horde authentication system.
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Jon Parise <jon@horde.org>
 * @author   Marcus I. Ryan <marcus@riboflavin.net>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Auth
 */
class Horde_Auth_Smbclient extends Horde_Auth_Base
{
    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     * 'domain' - (string) [REQUIRED] The domain name to authenticate with.
     * 'group' - Group name that the user must be a member of.
     *           DEFAULT: none
     * 'hostspec' - (string) [REQUIRED] IP, DNS Name, or NetBios name of the
     *              SMB server to authenticate with.
     * 'smbclient_path' - (string) [REQUIRED] The location of the smbclient
     *                    utility.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        foreach (array('hostspec', 'domain', 'smbclient_path') as $val) {
            throw new InvalidArgumentException('Missing ' . $val . ' parameter.');
        }

        parent::__construct($params);
    }

    /**
     * Find out if the given set of login credentials are valid.
     *
     * @param string $userId      The userId to check.
     * @param array $credentials  An array of login credentials.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        if (empty($credentials['password'])) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }

        /* Authenticate. */
        $cmdline = implode(' ', array(
            $this->_params['smbclient_path'],
            '-L',
            $this->_params['hostspec'],
            '-W',
            $this->_params['domain'],
            '-U',
            $userId
        ));

        $sc = popen($cmdline, 'w');
        if ($sc === false) {
            throw new Horde_Auth_Exception('Unable to execute smbclient.');
        }

        fwrite($sc, $credentials['password']);
        $rc = pclose($sc);

        if (intval($rc & 0xff) != 0) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }
    }

}
