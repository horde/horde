<?php
/**
 * Sesha application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Skeleton through this API.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Sesha
 */

/* Determine the base directories. */
if (!defined('SESHA_BASE')) {
    define('SESHA_BASE', __DIR__ . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(SESHA_BASE . '/config/horde.local.php')) {
        include SESHA_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', SESHA_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';
/*
 * The sesha application class
 * @package Sesha
 */
class Sesha_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H5 (1.0.0-git)';

    public function perms()
    {
        $permissions = array(
            'admin' => array(
                'title' => _("Administration"),
            ),
            'addStock' => array(
                'title' => _("Add Stock")
            )
        );
        return $permissions;
    }
}
