<?php
class Kastalia_Api extends Horde_Registry_Api
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
