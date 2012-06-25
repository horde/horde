<?php
/**
 * Klutz application API.
 *
 * This file defines Klutz's core API interface. Other core Horde libraries can
 * interact with Klutz through this API.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Klutz
 */

/* Determine the base directories. */
if (!defined('KLUTZ_BASE')) {
    define('KLUTZ_BASE', __DIR__ . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(KLUTZ_BASE . '/config/horde.local.php')) {
        include KLUTZ_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', KLUTZ_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Klutz_Application extends Horde_Registry_Application
{
    /**
     */
    public $version = 'H5 (2.0-git)';

    /**
     * Global variables defined:
     */
    protected function _init()
    {
        $GLOBALS['registry']->setTimeZone();

        $GLOBALS['klutz'] = new Klutz();
        $GLOBALS['klutz_driver'] = Klutz_Driver::factory();
    }

    /**
     */
    public function perms()
    {
        return array(
            'admin' => array(
                'title' => _("Administration"),
            )
        );
    }

    /**
     */
    public function menu($menu)
    {
        global $conf, $injector;

        $today = Horde::url('comics.php');
        $today = Horde_Util::addParameter($today, array('actionID' => 'day',
                                                  'date' => mktime(0, 0, 0)));

        $me = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];

        /* Klutz's menu items. */
        $menu->add(Horde::url('comics.php'), _("_Browse"), 'klutz.png',
                   null, '', null, ($me == $today) ? '__noselection' : (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null));

        $menu->add($today, _("_Today"), 'today.png', null, '', null,
                   ($me == $today) ? 'current' : '__noselection');

        if ($GLOBALS['registry']->isAdmin(array('permission' => 'klutz:admin'))) {
            $menu->add(Horde::url('backend.php'), _("_Update"), 'klutz.png');
        }
    }

}
