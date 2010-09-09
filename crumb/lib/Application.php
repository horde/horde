<?php
class Crumb_Application extends Horde_Registry_Application
{
    public $version = 'H4 (0.1-git)';

    /**
     * Add additional items to the menu.
     *
     * @param Horde_Menu $menu  The menu object.
     */
    public function menu($menu)
    {
        return Crumb::getMenu();
    }

}
