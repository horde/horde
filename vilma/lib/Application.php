<?php
/**
 * Shout application interface.
 *
 * This file defines Shout's external API interface.
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

    public function perms()
    {
        static $perms = array();
        if (!empty($perms)) {
            return $perms;
        }

        require_once VILMA_BASE . '/lib/base.php';
        global $vilma_driver;

        $perms['tree']['vilma']['superadmin'] = false;
        $perms['title']['vilma:superadmin'] = _("Super Administrator");

        $domains = $vilma_driver->getDomains();

        // Run through every domain
        foreach ($domains as $domain) {
            $d = $domain['domain_id'];
            $perms['tree']['vilma']['domains'][$d] = false;
            $perms['title']['vilma:domains:' . $d] = $domain['name'];
        }

        return $perms;
    }
}