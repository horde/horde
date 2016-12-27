<?php
/**
 * Vilma application interface.
 *
 * This file defines Vilma's external API interface.
 *
 * Copyright 2006-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Vilma
 */

/* Determine the base directories. */
if (!defined('VILMA_BASE')) {
    define('VILMA_BASE', realpath(__DIR__ . '/..'));
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(VILMA_BASE . '/config/horde.local.php')) {
        include VILMA_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', realpath(VILMA_BASE . '/..'));
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Vilma_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H5 (1.0.0-git)';

    public $driver = null;
    public $curdomain = null;

    protected function _init()
    {
        $this->driver = Vilma_Driver::factory();

        // Get the currently active domain, possibly storing a change into the
        // session.
        // Domain is passed in by ID, which may or may not be the
        // the same as the actual DNS domain name.
        $domain_id = Horde_Util::getFormData('domain_id');

        if (strlen($domain_id)) {
            $domain = $this->driver->getDomain($domain_id);
            if (!empty($domain['domain_name'])) {
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

    /**
     */
    public function menu($menu)
    {
        $menu->add(
            Horde::url('domains/index.php')->add('domain_id', 0),
            _("_List Domains"),
            'vilma-domain'
        );
        if ($this->curdomain) {
            $domain = $GLOBALS['session']->get('vilma', 'domain');
            $menu->add(
                Horde::url('users/index.php')
                    ->add('domain_id', $domain['domain_id']),
                $domain['domain_name'],
                'vilma-domain'
            );
        }
    }

    /**
     * Adds additional items to the sidebar.
     *
     * @param Horde_View_Sidebar $sidebar  The sidebar object.
     */
    public function sidebar($sidebar)
    {
        if ($this->curdomain) {
            $sidebar->addNewButton(
                _("_New User"),
                Horde::url('users/edit.php')
            );
        } else {
            $sidebar->addNewButton(
                _("_New Domain"),
                Horde::url('domains/edit.php')
            );
        }
    }
}
