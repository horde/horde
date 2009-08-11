<?php
/**
 * Folks application API.
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/folks/LICENSE.
 *
 * @package Folks
 */
class Folks_Application extends Horde_Registry_Application
{
    public $version = 'H4 (0.1-git)';

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Folks::getMenu();
    }

}
