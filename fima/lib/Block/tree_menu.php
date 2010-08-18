<?php

$block_name = _("Menu List");
$block_type = 'tree';

class Horde_Block_fima_tree_menu extends Horde_Block {

    var $_app = 'fima';

    function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        $menus = array(
            array('add', _("Add Postings"), 'add.png', Horde_Util::addParameter(Horde::applicationUrl('postings.php'), 'actionID', 'add_postings')),
            array('search', _("Search"), 'search.png', Horde::applicationUrl('search.php')),
            array('accounts', _("Accounts"), 'accounts.png', Horde::applicationUrl('accounts.php')),
            array('reports', _("Reports"), 'report.png', Horde::applicationUrl('report.php'))
        );

        foreach ($menus as $menu) {
            $tree->addNode(
                $parent . $menu[0],
                $parent,
                $menu[1],
                $indent + 1,
                false,
                array(
                    'icon' => Horde_Themes::img($menu[2]),
                    'url' => $menu[3]
                )
            );
        }
    }

}
