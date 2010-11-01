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
define('VILMA_BASE', dirname(__FILE__) . '/..');

class Vilma_Application extends Horde_Registry_Application
{
    public $driver = null;
    public $curdomain = null;

    protected function _init()
    {
        $this->driver = Vilma_Driver::singleton();

        // Get the currently active domain, possibly storing a change into the
        // session
        $this->curdomain = Vilma::getCurDomain();
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
