<?php
/**
 * Beatnik application interface.
 *
 * This file defines Horde's application interface. Other Horde libraries
 * and applications can interact with Beatnik through this API.
 *
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you did not
 * did not receive this file, see http://www.horde.org/licenses/gpl
 *
 * @author  Ben Klang <bklang@horde.org>
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
     */
    public function perms()
    {
        $perms = array(
            'domains' => array(
                'title' => _("Domains")
            ),
        );

        // Run through every domain
        foreach ($beatnik->driver->getDomains() as $domain) {
            $perms['domains:' . $domain['zonename']] = array(
                'title' => $domain['zonename']
            );
        }

        return $perms;
    }

    /**
     */
    public function menu($menu)
    {
        return Beatnik::getMenu();
    }

}
