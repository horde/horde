<?php
/**
 * Crumb Base Class.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Crumb
 */
class Crumb {

    /**
     * Build Crumb's list of menu items.
     */
    function getMenu()
    {
        global $conf, $registry, $browser, $print_link;

        $menu = new Horde_Menu(Horde_Menu::MASK_ALL);
        $menu->add(Horde::url('listclients.php'), _("List Clients"), 'user.png', Horde_Themes::img(null, 'horde'));
        $menu->add(Horde::url('addclient.php'), _("Add Client"), 'user.png', Horde_Themes::img(null, 'horde'));

        return $menu;
    }

}
