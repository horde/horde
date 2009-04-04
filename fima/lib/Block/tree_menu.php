<?php

$block_name = _("Menu List");
$block_type = 'tree';

/**
 * $Horde: fima/lib/Block/tree_menu.php,v 1.0 2008/06/03 00:11:13 trt Exp $
 */
class Horde_Block_fima_tree_menu extends Horde_Block {

    var $_app = 'fima';

    function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        global $registry;

        $menus = array(
            array('add', _("Add Postings"), 'add.png', Util::addParameter(Horde::applicationUrl('postings.php'), 'actionID', 'add_postings')),
            array('search', _("Search"), 'search.png', Horde::applicationUrl('search.php'), $registry->getImageDir('horde')),
            array('accounts', _("Accounts"), 'accounts.png', Horde::applicationUrl('accounts.php')),
            array('reports', _("Reports"), 'report.png', Horde::applicationUrl('report.php')),
        );
        
        foreach ($menus as $menu) {
            $tree->addNode($parent . $menu[0],
                           $parent,
                           $menu[1],
                           $indent + 1,
                           false,
                           array('icon' => $menu[2],
                                 'icondir' => isset($menu[4]) ? $menu[4] : $registry->getImageDir(),
                                 'url' => $menu[3]));
        }
    }

}
