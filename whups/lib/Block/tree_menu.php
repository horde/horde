<?php

$block_name = _("Menu List");
$block_type = 'tree';

/**
 * $Horde: whups/lib/Block/tree_menu.php,v 1.2 2007/08/09 04:01:02 chuck Exp $
 *
 * @package Horde_Block
 */
class Horde_Block_whups_tree_menu extends Horde_Block {

    var $_app = 'whups';

    function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        global $registry;

        $tree->addNode($parent . '__new',
                       $parent,
                       _("New Ticket"),
                       $indent + 1,
                       false,
                       array('icon' => 'create.png',
                             'icondir' => $registry->getImageDir(),
                             'url' => Horde::applicationUrl('ticket/create.php')));

        $tree->addNode($parent . '__search',
                       $parent,
                       _("Search"),
                       $indent + 1,
                       false,
                       array('icon' => 'search.png',
                             'icondir' => $registry->getImageDir('horde'),
                             'url' => Horde::applicationUrl('search.php')));
    }

}
