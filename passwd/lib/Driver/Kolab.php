<?php
/**
 * Copyright 2005-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2005-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */

/**
 * The Kolab class attempts to change a user's password on the designated Kolab
 * backend. Based off the LDAP passwd class.
 *
 * @todo Extend Passwd_Driver_Ldap, inject parameters.
 *
 * @author    Stuart Bingë <skbinge@gmail.com>
 * @category  Horde
 * @copyright 2005-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */
class Passwd_Driver_Kolab extends Passwd_Driver
{
    /**
     */
    protected function _changePassword($user, $oldpass, $newpass)
    {
        global $conf;

        // Connect to the LDAP server.
        $ds = ldap_connect(
            $conf['kolab']['ldap']['server'],
            $conf['kolab']['ldap']['port']
        );
        if (!$ds) {
            throw new Passwd_Exception(_("Could not connect to LDAP server"));
        }

        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

        // Bind anonymously, or use the phpdn user if available.
        if (!empty($conf['kolab']['ldap']['phpdn'])) {
            $phpdn = $conf['kolab']['ldap']['phpdn'];
            $phppw = $conf['kolab']['ldap']['phppw'];
            $result = @ldap_bind($ds, $phpdn, $phppw);
        } else {
            $result = @ldap_bind($ds);
        }
        if (!$result) {
            throw new Passwd_Exception(_("Could not bind to LDAP server"));
        }

        // Make sure we're using the full user@domain format.
        if (strstr($user, '@') === false) {
            $user .= '@' . $conf['kolab']['imap']['maildomain'];
        }

        // Find the user's DN.
        $result = ldap_search(
            $ds,
            $conf['kolab']['ldap']['basedn'],
            'mail=' . $user
        );
        $entry = ldap_first_entry($ds, $result);
        if ($entry === false) {
            throw new Passwd_Exception(_("User not found."));
        }

        $userdn = ldap_get_dn($ds, $entry);

        // Connect as the user.
        $result = @ldap_bind($ds, $userdn, $oldpass);
        if (!$result) {
            throw new Passwd_Exception(_("Incorrect old password."));
        }

        // And finally change the password.
        $new_details['userPassword'] = '{sha}' .
            base64_encode(pack('H*', sha1($newpass)));

        if (!ldap_mod_replace($ds, $userdn, $new_details)) {
            throw new Passwd_Exception(ldap_error($ds));
        }

        ldap_unbind($ds);
    }

}
