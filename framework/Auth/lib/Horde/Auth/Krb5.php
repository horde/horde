<?php
/**
 * The Horde_Auth_Krb5 class provides an kerberos implementation of the Horde
 * authentication system.
 *
 * This driver requires the 'krb5' PHP extension to be loaded.
 * The module can be downloaded here:
 *   http://www.horde.org/download/php/phpkrb5.tar.gz
 *
 * Kerberos must be correctly configured on your system (e.g. /etc/krb5.conf)
 * for this class to work correctly.
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Auth
 */
class Horde_Auth_Krb5 extends Horde_Auth_Base
{
    /**
     * Constructor.
     *
     * @param array $params  Not used.
     *
     * @throws Horde_Auth_Exception
     */
    public function __construct($params = array())
    {
        if (!Horde_Util::extensionExists('krb5')) {
            throw new Horde_Auth_Exception(_("Horde_Auth_Krb5: Required krb5 extension not found."));
        }

        parent::__construct($params);
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userId      The userId to check.
     * @param array $credentials  An array of login credentials.
     *                            For kerberos, this must contain a password
     *                            entry.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        if (empty($credentials['password'])) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }

        $result = krb5_login($userId, $credentials['password']);

        switch ($result) {
        case KRB5_OK:
            return;

        case KRB5_BAD_PASSWORD:
            throw new Horde_Auth_Exception(_("Bad kerberos password."));

        case KRB5_BAD_USER:
            throw new Horde_Auth_Exception(_("Bad kerberos username."));

        default:
            throw new Horde_Auth_Exception(_("Kerberos server rejected authentication."));
        }
    }

}
