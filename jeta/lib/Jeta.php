<?php
/**
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL).  If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Jeta
 */
class Jeta
{
    /**
     * Build Jeta's list of menu items.
     */
    public function getMenu()
    {
        $menu = new Horde_Menu();

        /* Jeta Home. */
        $menu->addArray(array('url' => Horde::url('index.php'), 'text' => _("_Shell"), 'icon' => 'jeta.png', 'class' => (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'current' : ''));

        return $menu;
    }

}
