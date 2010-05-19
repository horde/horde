<?php
/**
 * The Kolab class attempts to change a user's password on the designated
 * Kolab backend. Based off the LDAP passwd class.
 *
 * $Horde: passwd/lib/Driver/kolab.php,v 1.2.2.9 2009/01/06 15:25:23 jan Exp $
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Stuart Bingë <skbinge@gmail.com>
 * @since   Passwd 3.0
 * @package Passwd
 */
class Passwd_Driver_kolab extends Passwd_Driver {

    /**
     * Constructs a new Passwd_Driver_kolab object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    function Passwd_Driver_kolab($params = array())
    {
        // We don't need any backends.php-configurable parameters
    }

    /**
     * Changes the user's password.
     *
     * @param string $username      The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @return boolean  True or false based on success of the change.
     */
    function changePassword($username, $old_password, $new_password)
    {
        // Connect to the LDAP server.
        $ds = ldap_connect(
            $GLOBALS['conf']['kolab']['ldap']['server'],
            $GLOBALS['conf']['kolab']['ldap']['port']
        );
        if (!$ds) {
            return PEAR::raiseError(_("Could not connect to LDAP server"));
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
            return PEAR::raiseError(_("Could not bind to LDAP server"));
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
            return PEAR::raiseError(_("User not found."));
        }

        $userdn = ldap_get_dn($ds, $entry);

        // Connect as the user.
        $result = @ldap_bind($ds, $userdn, $old_password);
        if (!$result) {
            return PEAR::raiseError(_("Incorrect old password."));
        }

        // And finally change the password.
        $new_details['userPassword'] = '{sha}' .
            base64_encode(pack('H*', sha1($new_password)));

        if (!ldap_mod_replace($ds, $userdn, $new_details)) {
            return PEAR::raiseError(ldap_error($ds));
        }

        ldap_unbind($ds);

        return true;
    }

}
