<?php
/**
 * Vilma application interface.
 *
 * This file defines Vilma's external API interface.
 *
 * Copyright 2006-2010 Alkaloid Networks <http://www.alkaloid.net/>
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Vilma
 */

/* Determine the base directories. */
if (!defined('VILMA_BASE')) {
    define('VILMA_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(VILMA_BASE . '/config/horde.local.php')) {
        include VILMA_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', VILMA_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Vilma_Application extends Horde_Registry_Application
{
    public $driver = null;
    public $curdomain = null;

    protected function _init()
    {
        $this->driver = Vilma_Driver::singleton();

        // Get the currently active domain, possibly storing a change into the
        // session.
        // Domain is passed in by ID, which may or may not be the
        // the same as the actual DNS domain name.
        $domain_id = Horde_Util::getFormData('domain_id');

        if (!empty($domain_id)) {
            $domain = $this->driver->getDomain($domain_id);
            if (!is_a($domain, 'PEAR_Error') &&
                !empty($domain['domain_name'])) {
                $this->curdomain = $domain;
                Vilma::setCurDomain($domain);
            }
        } elseif ($domain = $GLOBALS['session']->get('vilma', 'domain')) {
            $this->curdomain = $domain;
        }
    }

    public function perms()
    {
        $perms = array(
            'superadmin' => array(
                'title' => _("Super Administrator")
            )
        );

        $domains = $this->driver->getDomains();

        // Run through every domain
        foreach ($domains as $domain) {
            $perms['domains:' . $domain['domain_id']] = array(
                'title' => $domain['name']
            );
        }

        return $perms;
    }
}
