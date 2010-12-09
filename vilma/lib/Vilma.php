<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
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
     * Check whether the current user has administrative permissions over the
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
    public function hasPermission($domain = null, $permmask = null)
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

    function getUserMgrTypes()
    {
        return array(
            'all' => array(
                'singular' => _("All"),
                'plural'   => _("All") ),
            'user' => array(
                'singular' => _("User"),
                'plural' => _("Users"), ),
            'alias' => array(
                'singular' => _("Alias"),
                'plural' => _("Aliases"), ),
            //'grpfwd' => array(
            //    'singular' => _("Group/Forward"),
            //    'plural' => _("Groups and Forwards"), ), );
            'group' => array(
                'singular' => _("Group"),
                'plural' => _("Groups"), ),
            'forward' => array(
                'singular' => _("Forward"),
                'plural' => _("Forwards"),), );
    }

    /**
     * Create tabs to navigate the user manager area
     *
     * return object Horde_Core_Ui_Tabs object
     */
    function getUserMgrTabs(&$vars)
    {
        $url = Horde::url('users/index.php');
        $tabs = new Horde_Core_Ui_Tabs('section', $vars);
        foreach (Vilma::getUserMgrTypes() as $section => $desc) {
            $tabs->addTab($desc['plural'], $url, $section);
        }
        return $tabs;
    }

    /**
     * Set the current domain
     */
    function setCurDomain($domain)
    {
        $GLOBALS['session']->set('vilma', 'domain', $domain);
    }

    /**
     * Strip the domain from an email address (leaving the Username)
     *
     * @param string $email  Email address to strip (leaving the Username)
     *
     * @return string Username portion of supplied email address
     */
    function stripUser($email)
    {
        list($user, $domain) = explode('@', $email);
        return $user;
    }

    /**
     * Strip the username from an email address (leaving the domain)
     *
     * @param string $email  Email address to strip (leaving the domain)
     *
     * @return string Domain portion of supplied email address
     */
    function stripDomain($email)
    {
        $parts = explode('@', $email);
        if (count($parts) == 2) {
            $parts = explode(',', $parts[1]);
            return $parts[0];
        }
        return null;
    }

    function &getMailboxDriver()
    {
        global $conf;

        require_once VILMA_BASE . '/lib/MailboxDriver.php';
        $driver = &Vilma_MailboxDriver::singleton($conf['mailboxes']['driver'],
                                                  $conf['mailboxes']['params']);
        return $driver;
    }
}
