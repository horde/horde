<?php
/**
 * Skeleton Base Class.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Your Name <you@example.com>
 * @package Skeleton
 */
class Skeleton
{
    /**
     * Build Skeleton's list of menu items.
     */
    static public function getMenu()
    {
        $menu = new Horde_Menu(Horde_Menu::MASK_ALL);
        $menu->add(Horde::url('list.php'), _("List"), 'user.png', Horde_Themes::img(null, 'horde'));

        return $menu;
    }

}
