<?php
/**
 * Sam application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Sam through this API.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Sam
 */

/* Determine the base directories. */
if (!defined('SAM_BASE')) {
    define('SAM_BASE', __DIR__ . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(SAM_BASE . '/config/horde.local.php')) {
        include SAM_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', SAM_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Sam_Application extends Horde_Registry_Application
{
    /**
     */
    public $version = 'H5 (1.0-git)';

    /**
     */
    protected function _bootstrap()
    {
        $GLOBALS['injector']->bindFactory('Sam_Driver', 'Sam_Factory_Driver', 'create');
    }

    /**
     */
    public function menu($menu)
    {
        if ($GLOBALS['conf']['enable']['rules']) {
            $menu->add(Horde::url('spam.php'), _("Spam Options"), 'sam.png',
                       null, null, null,
                       basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);
        }
        try {
            $whitelist_url = $GLOBALS['registry']->link('mail/showWhitelist');
            $menu->add(Horde::url($whitelist_url), _("Whitelist"), 'whitelist.png');
        } catch (Horde_Exception $e) {
        }
        try {
            $blacklist_url = $GLOBALS['registry']->link('mail/showBlacklist');
            $menu->add(Horde::url($blacklist_url), _("Blacklist"), 'blacklist.png');
        } catch (Horde_Exception $e) {
        }
    }
}
