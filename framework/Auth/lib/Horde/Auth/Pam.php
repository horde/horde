<?php
/**
 * The Horde_Auth_Pam:: class provides a PAM-based implementation of the Horde
 * authentication system.
 *
 * PAM (Pluggable Authentication Modules) is a flexible mechanism for
 * authenticating users. It has become the standard authentication system for
 * Linux, Solaris and FreeBSD.
 *
 * This driver relies on the PECL PAM package:
 *
 *      http://pecl.php.net/package/PAM
 *
 * Optional parameters:
 * <pre>
 * 'service' - (string) The name of the PAM service to use when
 *             authenticating.
 *             DEFAULT: php
 * </pre>
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Jon Parise <jon@horde.org>
 * @package Horde_Auth
 */
class Horde_Auth_Pam extends Horde_Auth_Base
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
        if (!Horde_Util::extensionExists('pam')) {
            throw new Horde_Exception(_("PAM authentication is not available."));
        }

        if (!empty($params['service'])) {
            ini_set('pam.servicename', $params['service']);
        }

        parent::__construct($params);
    }

    /**
     * Find out if a set of login credentials are valid.
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

        $error = null;
        if (!pam_auth($userId, $credentials['password'], $error)) {
            throw new Horde_Exception($error);
        }
    }

}
