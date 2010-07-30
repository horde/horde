<?php
/**
 * Horde application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Horde through this API.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
     * The application's version.
     *
     * @var string
     */
    public $version = '4.0-git';

    /**
     * Returns a list of available permissions.
     *
     * @return array  TODO
     */
    public function perms()
    {
        $perms = array();

        $perms['tree']['horde']['max_blocks'] = false;
        $perms['title']['horde:max_blocks'] = _("Maximum Number of Portal Blocks");
        $perms['type']['horde:max_blocks'] = 'int';

        return $perms;
    }

    /**
     * Returns the specified permission for the given app permission.
     *
     * @param string $permission  The permission to check.
     * @param mixed $allowed      The allowed permissions.
     * @param array $opts         Additional options (NONE).
     *
     * @return mixed  The value of the specified permission.
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
     * Populate dynamically-generated preference values.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsEnum($ui)
    {
        $GLOBALS['injector']->getInstance('Horde_Prefs_Ui')->prefsEnum($ui);
    }

    /**
     * Code to run on init when viewing prefs for this application.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsInit($ui)
    {
        $GLOBALS['injector']->getInstance('Horde_Prefs_Ui')->prefsInit($ui);
    }

    /**
     * Generate code used to display a special preference.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return string  The HTML code to display on the options page.
     */
    public function prefsSpecial($ui, $item)
    {
        return $GLOBALS['injector']->getInstance('Horde_Prefs_Ui')->prefsSpecial($ui, $item);
    }

    /**
     * Special preferences handling on update.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return boolean  True if preference was updated.
     */
    public function prefsSpecialUpdate($ui, $item)
    {
        return $GLOBALS['injector']->getInstance('Horde_Prefs_Ui')->prefsSpecialUpdate($ui, $item);
    }

    /**
     * Called when preferences are changed.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsCallback($ui)
    {
        $GLOBALS['injector']->getInstance('Horde_Prefs_Ui')->prefsCallback($ui);
    }

}
