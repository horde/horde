<?php
class Kastalia_Application extends Horde_Registry_Application
{
    public $version = '1.0.1';

    /**
     * Add additional items to the menu.
     *
     * @param Horde_Menu $menu  The menu object.
     */
    public function menu($menu)
    {
        return Kastalia::getMenu();
    }

}
