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
class Vilma {

    /**
     * Check whether the current user has administrative permissions over
     * the requested domain at the given permissions level.
     * Also checks to see if the user is a Vilma superadmin.
     * If the user is a Horde admin they automatically have permission.
     *
     * @param string $domain Domain for which to check permissions
     * @param int $permmask  Permissions that must be set for the user
     *
     * @return boolean       True if the user has the requested permission
     */
    function hasPermission($domain, $permmask = null)
    {
        // FIXME Should this really be the case?  Superadmin is more granular
        if ($GLOBALS['registry']->isAdmin()) {
            return true;
        }

        if ($permmask === null) {
            $permmask = Horde_Perms::SHOW|Horde_Perms::READ;
        }

        # Default deny all permissions
        $user = 0;
        $superadmin = 0;

        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        $superadmin = $perms->hasPermission('vilma:domains',
                                            $GLOBALS['registry']->getAuth(), $permmask);

        $user = $perms->hasPermission($permname, $GLOBALS['registry']->getAuth(), $permmask);

        return ($superadmin | $user);
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
     * Attempt to determine the current domain name based on current user or
     * a domain_id passed in by form.
     *
     * @return mixed string domain on success, false on failure, PEAR::Error on error
     */
    function getCurDomain()
    {
        // Domain is passed in by ID, which may or may not be the
        // the same as the actual DNS domain name
        $domain_id = Horde_Util::getFormData('domain_id');

        if (!empty($domain_id)) {
            // FIXME: Make sure this only runs once per page-load
            $domain = $GLOBALS['vilma_driver']->getDomain($domain_id);
            if (is_a($domain, 'PEAR_Error')) {
                return $domain;
            }
            if (empty($domain['domain_name'])) {
                $domain = false;
            }
            Vilma::setCurDomain($domain);
        } elseif (isset($_SESSION['vilma']['domain'])) {
            $domain = $_SESSION['vilma']['domain'];
        } else {
            $domain = false;
        }

        return $domain;
    }

    /**
     * Set the current domain
     */
    function setCurDomain($domain)
    {
        $_SESSION['vilma']['domain'] = $domain;
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

    /**
     * Build Vilma's list of menu items.
     */
    function getMenu($returnType = 'object')
    {
        $menu = new Horde_Menu();

        $menu->add(Horde::url('domains/index.php'), _("_Domains"), 'domain.png');

        if (Vilma::getCurDomain()) {
            $domain = $_SESSION['vilma']['domain'];
            $url = Horde::url('users/index.php');
            $tmp = Horde_Util::addParameter($url, 'domain_id', $domain['domain_id']);
            $menu->add(Horde::url($tmp), _($domain['domain_name']), 'domain.png');
            $menu->add(Horde::url('users/edit.php'), _("New _Address"), 'user.png', Horde_Themes::img(null, 'horde'));
        } else {
            $menu->add(Horde::url('domains/edit.php'), _("_New Domain"), 'domain.png');
        }

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

}
