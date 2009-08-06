<?php
class Kastalia_Api extends Horde_Registry_Api
{
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
