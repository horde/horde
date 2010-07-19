<?php

$block_name = _("Enter Time");
$block_type = 'tree';

/**
 * @package Horde_Block
 */
class Horde_Block_hermes_tree_menu extends Horde_Block
{
    protected $_app = 'hermes';

    protected function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        $tree->addNode($parent . '__add',
                       $parent,
                       _("Enter Time"),
                       $indent + 1,
                       false,
                       array('icon' => 'hermes.png',
                             'url' => Horde::applicationUrl('entry.php')));
        $tree->addNode($parent . '__search',
                       $parent,
                       _("Search Time"),
                       $indent + 1,
                       false,
                       array('icon' => 'search.png',
                             'url' => Horde::applicationUrl('search.php')));
    }

}
