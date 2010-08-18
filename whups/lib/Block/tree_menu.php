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
        $tree->addNode(
            $parent . '__new',
            $parent,
            _("New Ticket"),
            $indent + 1,
            false,
            array(
                'icon' => Horde_Themes::img('create.png'),
                'url' => Horde::applicationUrl('ticket/create.php')
            )
        );

        $tree->addNode(
            $parent . '__search',
            $parent,
            _("Search"),
            $indent + 1,
            false,
            array(
                'icon' => Horde_Themes::img('search.png'),
                'url' => Horde::applicationUrl('search.php')
            )
        );
    }

}
