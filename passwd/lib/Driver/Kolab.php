<?php
/**
 * The Kolab class attempts to change a user's password on the designated Kolab
 * backend. Based off the LDAP passwd class.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @todo Extend Passwd_Driver_Ldap, inject parameters.
 *
 * @author  Stuart Bingë <skbinge@gmail.com>
 * @package Passwd
 */
class Passwd_Driver_Kolab extends Passwd_Driver
{
    /**
     * Changes the user's password.
     *
     * @param string $username      The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @throws Passwd_Exception
     */
    public function changePassword($username, $old_password, $new_password)
    {
        // Connect to the LDAP server.
        $ds = ldap_connect(
            $GLOBALS['conf']['kolab']['ldap']['server'],
            $GLOBALS['conf']['kolab']['ldap']['port']
        );
        if (!$ds) {
            throw new Passwd_Exception(_("Could not connect to LDAP server"));
        }

        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

        // Bind anonymously, or use the phpdn user if available.
        if (!empty($GLOBALS['conf']['kolab']['ldap']['phpdn'])) {
            $phpdn = $GLOBALS['conf']['kolab']['ldap']['phpdn'];
            $phppw = $GLOBALS['conf']['kolab']['ldap']['phppw'];
            $result = @ldap_bind($ds, $phpdn, $phppw);
        } else {
            $result = @ldap_bind($ds);
        }
        if (!$result) {
            throw new Passwd_Exception(_("Could not bind to LDAP server"));
        }

        // Make sure we're using the full user@domain format.
        if (strstr($username, '@') === false) {
            $username .= '@' . $GLOBALS['conf']['kolab']['imap']['maildomain'];
        }

        // Find the user's DN.
        $result = ldap_search(
            $ds,
            $GLOBALS['conf']['kolab']['ldap']['basedn'],
            "mail=$username"
        );
        $entry = ldap_first_entry($ds, $result);
        if ($entry === false) {
            throw new Passwd_Exception(_("User not found."));
        }

        $userdn = ldap_get_dn($ds, $entry);

        // Connect as the user.
        $result = @ldap_bind($ds, $userdn, $old_password);
        if (!$result) {
            throw new Passwd_Exception(_("Incorrect old password."));
        }

        // And finally change the password.
        $new_details['userPassword'] = '{sha}' .
            base64_encode(pack('H*', sha1($new_password)));

        if (!ldap_mod_replace($ds, $userdn, $new_details)) {
            throw new Passwd_Exception(ldap_error($ds));
        }

        ldap_unbind($ds);
    }
}
