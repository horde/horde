<?php
/**
 * Beatnik application interface.
 *
 * This file defines Horde's application interface. Other Horde libraries
 * and applications can interact with Beatnik through this API.
 *
 * Copyright 2006-2010 Alkaloid Networks, LLC (http://projects.alkaloid.net/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.html.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Beatnik
 */

if (!defined('BEATNIK_BASE')) {
    define('BEATNIK_BASE', dirname(__FILE__). '/..');
}

if (!defined('HORDE_BASE')) {
    /* If horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(BEATNIK_BASE. '/config/horde.local.php')) {
        include BEATNIK_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', BEATNIK_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';


class Beatnik_Application extends Horde_Registry_Application
{
    public $version = 'H4 (1.0-git)';
    public $driver = null;
    public $domains = null;

    function _init()
    {
        $this->driver = Beatnik_Driver::factory();

        // Get a list of domains to work with
        $this->domains = $this->driver->getDomains();

        // Jump to new domain
        if (Horde_Util::getFormData('curdomain') !== null && !empty($this->domains)) {
            try {
                $domain = $this->driver->getDomain(Horde_Util::getFormData('curdomain'));
            } catch (Exception $e) {
                $notification->push($e->getMessage(), 'horde.error');
                $domain = $domains[0];
            }

            $_SESSION['beatnik']['curdomain'] = $domain;
        }

        // Determine if the user should see basic or advanced options
        if (!isset($_SESSION['beatnik']['expertmode'])) {
            $_SESSION['beatnik']['expertmode'] = false;
        } elseif (Horde_Util::getFormData('expertmode') == 'toggle') {
            if ($_SESSION['beatnik']['expertmode']) {
                $notification->push(_('Expert Mode off'), 'horde.message');
                $_SESSION['beatnik']['expertmode'] = false;
            } else {
                $notification->push(_('Expert Mode ON'), 'horde.warning');
                $_SESSION['beatnik']['expertmode'] = true;
            }
        }

        // Initialize the page marker
        if (!isset($_SESSION['beatnik']['curpage'])) {
            $_SESSION['beatnik']['curpage'] = 0;
        }
    }

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        $perms['title']['beatnik:domains'] = _("Domains");

        // Run through every domain
        foreach ($beatnik->driver->getDomains() as $domain) {
            $perms['tree']['beatnik']['domains'][$domain['zonename']] = false;
            $perms['title']['beatnik:domains:' . $domain['zonename']] = $domain['zonename'];
        }

        return $perms;
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Beatnik::getMenu();
    }
}
