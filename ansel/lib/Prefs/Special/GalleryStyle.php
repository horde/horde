<?php
/**
 * Special prefs handling for the 'default_gallerystyle_select' preference.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class Ansel_Prefs_Special_GalleryStyle implements Horde_Core_Prefs_Ui_Special
{
    /**
     */
    public function init(Horde_Core_Prefs_Ui $ui)
    {
    }

    /**
     */
    public function display(Horde_Core_Prefs_Ui $ui)
    {
        return _("Default style for galleries") .
            Ansel::getStyleSelect('default_gallerystyle_select', $GLOBALS['prefs']->getValue('default_gallerystyle')) .
            '<br />';
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        if (!isset($ui->vars->default_gallerystyle_select)) {
            return false;
        }

        $GLOBALS['prefs']->setValue('default_gallerystyle', $ui->vars->default_gallerystyle_select);
        return true;
    }

}
