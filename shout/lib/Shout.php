<?php
/**
 * Shout:: defines an set of classes for the Shout application.
 *
 * $Id$
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @version $Revision: 94 $
 * @since   Shout 0.1
 * @package Shout
 */

class Shout
{
    var $applist = array();
    var $_applist_curapp = '';
    var $_applist_curfield = '';

    /**
     * Build Shout's list of menu items.
     *
     * @access public
     */
    static public function getMenu($returnType = 'object')
    {
        global $conf, $context, $section, $action;

        require_once 'Horde/Menu.php';

        $menu = new Horde_Menu(HORDE_MENU_MASK_ALL);

        $menu->add(Horde::applicationUrl('extensions.php'), _("Extensions"), "user.png");
        $menu->add(Horde::applicationUrl('devices.php'), _("Devices"), "shout.png");
        $menu->add(Horde::applicationUrl('routes.php'), _("Call Paths"));


        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

    /**
     * Generate the tabs at the top of each Shout pages
     *
     * @param &$vars Reference to the passed in variables
     *
     * @return object Horde_UI_Tabs
     */
    static public function getTabs($context, &$vars)
    {
        global $shout;
        $perms = Horde_Perms::singleton();

        $permprefix = 'shout:contexts:' . $context;

        $tabs = new Horde_UI_Tabs('section', $vars);

        if (Shout::checkRights($permprefix . ':extensions', null, 1)) {
            $url = Horde::applicationUrl('extensions.php');
            $tabs->addTab(_("_Extensions"), $url, 'extensions');
        }

        if (Shout::checkRights($permprefix . ':dialplan', null, 1)) {
            $url = Horde::applicationUrl('dialplan.php');
            $tabs->addTab(_("_Automated Attendant"), $url, 'dialplan');
        }

        if (Shout::checkRights($permprefix . ':conference', null, 1)) {
            $url = Horde::applicationUrl('conference.php');
            $tabs->addTab(_("_Conference Rooms"), $url, 'conference');
        }

       if (Shout::checkRights($permprefix . ':moh', null, 1)) {
            $url = Horde::applicationUrl('moh.php');
            $tabs->addTab(_("_Music on Hold"), $url, 'moh');
        }

        return $tabs;
    }

    /**
     * Checks for the given permissions for the current user on the given
     * permission.  Optionally check for higher-level permissions and ultimately
     * test for superadmin priveleges.
     *
     * @param string $permname Name of the permission to check
     *
     * @param optional int $permmask Bitfield of permissions to check for
     *
     * @param options int $numparents Check for the same permissions this
     *                                many levels up the tree
     *
     * @return boolean the effective permissions for the user.
     */
    static public function checkRights($permname, $permmask = null, $numparents = 0)
    {
        if (Horde_Auth::isAdmin()) { return true; }

        $perms = Horde_Perms::singleton();
        if ($permmask === null) {
            $permmask = PERMS_SHOW|PERMS_READ;
        }

        # Default deny all permissions
        $user = 0;
        $superadmin = 0;

        $superadmin = $perms->hasPermission('shout:superadmin',
            Horde_Auth::getAuth(), $permmask);

        while ($numparents >= 0) {
            $tmpuser = $perms->hasPermission($permname,
                Horde_Auth::getAuth(), $permmask);

            $user = $user | $tmpuser;
            if ($numparents > 0) {
                $pos = strrpos($permname, ':');
                if ($pos) {
                    $permname = substr($permname, 0, $pos);
                }
            }
            $numparents--;
        }
        $test = $superadmin | $user;

        return ($test & $permmask) == $permmask;
    }

    /**
     * Generate new device authentication tokens.
     *
     * This method is designed to generate random strings for the
     * authentication ID and password.  The result is intended to be used
     * for automatically generated device information.  The user is prevented
     * from specifying usernames and passwords for these reasons:
     * 1) If a username and/or password can be easily guessed, monetary loss
     *    is likely through the fraudulent placing of telephone calls.
     *    This has been observed in the wild far too many times already.
     *
     * 2) The username and password are only needed to be programmed into the
     *    device once, and then stored semi-permanently.  In some cases, the
     *    provisioning can be done automatically.  For these reasons, having
     *    user-friendly usernames and passswords is not terribly important.
     *
     * @param string $context  Context for this credential pair
     *
     * @return array  Array of (string $deviceID, string $devicePassword)
     */
    static public function genDeviceAuth($context)
    {
        $devid = uniqid($context);
        $password = uudecode(md5(uniqid(mt_rand(), true)));

        // This simple password generation algorithm inspired by Jon Haworth
        // http://www.laughing-buddha.net/jon/php/password/

        // define possible characters
        // Vowels excluded to avoid potential pronounceability
        $possible = "0123456789bcdfghjkmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ";

        $password = "";
        $i = 0;
        while ($i < 12) {
            // pick a random character from the possible ones
            $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);

            // we don't want this character if it's already in the password
            if (!strstr($password, $char)) {
              $password .= $char;
              $i++;
            }
        }

        return array($devid, $password);
    }
}
