<?php
/**
 * Pastie Base Class.
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
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
        $menu->add(Horde::url('paste.php'), _("Paste"), 'pastie.png');

        return $menu;
    }

}
