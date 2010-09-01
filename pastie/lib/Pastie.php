<?php
/**
 * Pastie Base Class.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Your Name <you@example.com>
 * @package Pastie
 */
class Pastie
{
    /**
     * Build Pastie's list of menu items.
     */
    static public function getMenu()
    {
        $menu = new Horde_Menu(Horde_Menu::MASK_ALL);
        $menu->add(Horde::url('paste.php'), _("Paste"), 'pastie.png', Horde_Themes::img(null, 'horde'));

        return $menu;
    }

}
