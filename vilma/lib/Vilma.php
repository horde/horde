<?php
/**
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @author  David Cummings <davidcummings@acm.org>
 * @package Vilma
 */
class Vilma
{
    /**
     * Checks whether the current user has administrative permissions over the
     * requested domain at the given permissions level.
     *
     * Also checks to see if the user is a Vilma superadmin.  If the user is a
     * Horde admin they automatically have permission.
     *
     * @param string $domain     Domain for which to check permissions.
     * @param integer $permmask  Permissions that must be set for the user.
     *
     * @return boolean  True if the user has the requested permission.
     */
    static public function hasPermission($domain = null, $permmask = null)
    {
        if ($GLOBALS['registry']->isAdmin()) {
            return true;
        }

        if (is_null($permmask)) {
            $permmask = Horde_Perms::SHOW | Horde_Perms::READ;
        }
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');

        if ($perms->hasPermission('vilma:domains', $GLOBALS['registry']->getAuth(), $permmask)) {
            return true;
        }
        if ($domain &&
            $perms->hasPermission('vilma:domains:' . $domain, $GLOBALS['registry']->getAuth(), $permmask)) {
            return true;
        }

        return false;
    }

    static public function getUserMgrTypes()
    {
        return array(
            'all' => array(
                'singular' => _("All"),
                'plural'   => _("All")),
            'user' => array(
                'singular' => _("User"),
                'plural' => _("Users")),
            'alias' => array(
                'singular' => _("Alias"),
                'plural' => _("Aliases")),
            'group' => array(
                'singular' => _("Group"),
                'plural' => _("Groups")),
            'forward' => array(
                'singular' => _("Forward"),
                'plural' => _("Forwards")));
    }

    /**
     * Creates tabs to navigate the user manager area.
     *
     * @return Horde_Core_Ui_Tabs
     */
    static public function getUserMgrTabs(Variables $vars)
    {
        $url = Horde::url('users/index.php');
        $tabs = new Horde_Core_Ui_Tabs('section', $vars);
        foreach (Vilma::getUserMgrTypes() as $section => $desc) {
            $tabs->addTab($desc['plural'], $url, $section);
        }
        return $tabs;
    }

    /**
     * Set the current domain.
     */
    static public function setCurDomain($domain = null)
    {
        $GLOBALS['session']->set('vilma', 'domain', $domain);
    }

    /**
     * Strips the domain from an email address (leaving the user name).
     *
     * @param string $email  Email address to strip.
     *
     * @return string  User name portion of the email address.
     */
    static public function stripUser($email)
    {
        list($user, $domain) = explode('@', $email);
        return $user;
    }

    /**
     * Strip the user name from an email address (leaving the domain).
     *
     * @param string $email  Email address to strip.
     *
     * @return string  Domain portion of the email address.
     */
    static public function stripDomain($email)
    {
        $parts = explode('@', $email);
        if (count($parts) == 2) {
            $parts = explode(',', $parts[1]);
            return $parts[0];
        }
        return null;
    }
}
