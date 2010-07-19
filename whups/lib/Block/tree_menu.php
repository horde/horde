<?php

$block_name = _("Menu List");
$block_type = 'tree';

/**
 * @package Horde_Block
 */
class Horde_Block_whups_tree_menu extends Horde_Block
{
    protected $_app = 'whups';

    protected function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        global $registry;

        $tree->addNode($parent . '__new',
                       $parent,
                       _("New Ticket"),
                       $indent + 1,
                       false,
                       array('icon' => 'create.png',
                             'icondir' => (string)Horde_Themes::img(),
                             'url' => Horde::applicationUrl('ticket/create.php')));

        $tree->addNode($parent . '__search',
                       $parent,
                       _("Search"),
                       $indent + 1,
                       false,
                       array('icon' => 'search.png',
                             'icondir' => (string)Horde_Themes::img(),
                             'url' => Horde::applicationUrl('search.php')));
    }

}
