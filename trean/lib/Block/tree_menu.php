<?php

$block_name = _("Menu List");
$block_type = 'tree';

/**
 */
class Horde_Block_trean_tree_menu extends Horde_Block
{
    protected $_app = 'trean';

    protected function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        $tree->addNode(
            $parent . '__new',
            $parent,
            _("Add"),
            $indent + 1,
            false,
            array(
                'icon' => Horde_Themes::img('add.png'),
                'url' => Horde::applicationUrl('add.php')
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

        $folders = Trean::listFolders();
        if ($folders instanceof PEAR_Error) {
            $browse = Horde::applicationUrl('browse.php');

            foreach ($folders as $folder) {
                $parent_id = $folder->getParent();
                $tree->addNode(
                    $parent . $folder->getId(),
                    $parent . $parent_id,
                    $folder->get('name'),
                    $indent + substr_count($folder->getName(), ':') + 1,
                    false,
                    array(
                        'icon' => Horde_Themes::img('tree/folder.png'),
                        'url' => $browse->copy()->add('f', $folder->getId())
                    )
                );
            }
        }
    }

}
