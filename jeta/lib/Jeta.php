<?php
/**
 * Copyright 2006-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL).  If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Michael Slusarz <slusarz@curecanti.org>
 * @package Jeta
 */
class Jeta {

    /**
     * Build Jeta's list of menu items.
     */
    function getMenu($returnType = 'object')
    {
        global $registry, $conf;

        require_once 'Horde/Menu.php';
        $menu = new Menu();

        /* Jeta Home. */
        $menu->addArray(array('url' => Horde::applicationUrl('main.php'), 'text' => _("_Shell"), 'icon' => 'jeta.png', 'class' => (basename($_SERVER['PHP_SELF']) == 'main.php' || basename($_SERVER['PHP_SELF']) == 'index.php') ? 'current' : ''));

        return ($returnType == 'object') ? $menu : $menu->render();
    }

}
