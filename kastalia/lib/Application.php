<?php
class Kastalia_Application extends Horde_Registry_Application
{
    public $version = '1.0.1';

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Kastalia::getMenu();
    }

}
