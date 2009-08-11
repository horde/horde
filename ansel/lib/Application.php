<?php
/**
 * Ansel application API.
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Application extends Horde_Registry_Application
{
    public $version = 'H4 (2.0-git)';

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        $perms = array();
        $perms['tree']['ansel']['admin'] = false;
        $perms['title']['ansel:admin'] = _("Administrators");

        return $perms;
    }

    /**
     * Special preferences handling on update.
     *
     * @param string $item      The preference name.
     * @param boolean $updated  Set to true if preference was updated.
     *
     * @return boolean  True if preference was updated.
     */
    public function prefsHandle($item, $updated)
    {
        switch ($item) {
        case 'default_category_select':
            $default_category = Horde_Util::getFormData('default_category_select');
            if (!is_null($default_category)) {
                $GLOBALS['prefs']->setValue('default_category', $default_category);
                return true;
            }
            break;

        case 'default_gallerystyle_select':
            $default_style = Horde_Util::getFormData('default_gallerystyle_select');
            if (!is_null($default_style)) {
                $GLOBALS['prefs']->setValue('default_gallerystyle', $default_style);
                return true;
            }
            break;
        }

        return $updated;
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Ansel::getMenu();
    }

}
