<?php
class Skoli_Application extends Horde_Registry_Application
{
    public $version = 'H4 (0.1-git)';

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Skoli::getMenu();
    }

}
