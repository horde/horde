<?php
class Skeleton_Api extends Horde_Registry_Api
{
    public $version = 'H4 (0.1-git)';

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Skeleton::getMenu();
    }

}
