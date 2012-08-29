<?php
/**
 * Ulaform internal API interface.
 *
 * This file defines Ulaform's internal API interface. Other applications can
 * interact with Ulaform through this API.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Ulaform
 */

/* Determine the base directories. */
if (!defined('ULAFORM_BASE')) {
    define('ULAFORM_BASE', __DIR__ . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(ULAFORM_BASE . '/config/horde.local.php')) {
        include ULAFORM_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', ULAFORM_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Ulaform_Application extends Horde_Registry_Application
{
    public $version = 'H5 (1.0-git)';

    /**
     * Global variables defined:
     *   $ulaform_driver - TODO
     */
    protected function _init()
    {
        $GLOBALS['ulaform_driver'] = new Ulaform_Driver($GLOBALS['injector']->getInstance('Horde_Db_Adapter'));
    }

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        $perms['forms'] = array(
            'title' => _("Forms")
        );

        try {
            foreach ($GLOBALS['injector']->getInstance('Ulaform_Factory_Driver')->create()->getAvailableForms() as $form) {
                $perms['forms:' . $form['form_id']] = array(
                    'title' => $form['form_name'],
                );
            }
        } catch (Ulaform_Exception $e) {}

        return $perms;
    }

    /**
     * Generate the menu to use in ulaform administration pages.
     */
    public function menu($menu)
    {
        $menu->setMask(Horde_Menu::MASK_ALL & ~Horde_Menu::MASK_PREFS);

        $menu->add(Horde::url('forms.php'), _("_List Forms"), 'ulaform.png');
        $menu->add(Horde::url('edit.php'), _("_New Form"), 'new.png');
    }

}
