<?php
/**
 * Horde application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Horde through this API.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @package Horde
 */

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once dirname(__FILE__) . '/core.php';

class Horde_Application extends Horde_Registry_Application
{
    /**
     */
    public $version = '4.0-git';

    /**
     */
    public function perms()
    {
        return array(
            'max_blocks' => array(
                'title' => _("Maximum Number of Portal Blocks"),
                'type' => 'int'
            )
        );
    }

    /**
     */
    public function hasPermission($permission, $allowed, $opts = array())
    {
        switch ($permission) {
        case 'max_blocks':
            $allowed = max($allowed);
            break;
        }

        return $allowed;
    }

    /**
     */
    public function menu($menu)
    {
        $menu->add(Horde::url('services/portal/', false, array('app' => 'horde')), Horde_Core_Translation::t("_Home"), 'horde.png');
    }

    /**
     */
    public function prefsInit($ui)
    {
        $GLOBALS['injector']->getInstance('Horde_Prefs_Ui')->prefsInit($ui);
    }

    /**
     */
    public function prefsGroup($ui)
    {
        $GLOBALS['injector']->getInstance('Horde_Prefs_Ui')->prefsGroup($ui);
    }

    /**
     */
    public function prefsSpecial($ui, $item)
    {
        return $GLOBALS['injector']->getInstance('Horde_Prefs_Ui')->prefsSpecial($ui, $item);
    }

    /**
     */
    public function prefsSpecialUpdate($ui, $item)
    {
        return $GLOBALS['injector']->getInstance('Horde_Prefs_Ui')->prefsSpecialUpdate($ui, $item);
    }

    /**
     */
    public function prefsCallback($ui)
    {
        $GLOBALS['injector']->getInstance('Horde_Prefs_Ui')->prefsCallback($ui);
    }

}
