<?php

$block_name = _("Menu List");
$block_type = 'tree';

/**
 * @package Horde_Block
 */
class Horde_Block_skoli_tree_menu extends Horde_Block {

    var $_app = 'skoli';

    function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        require_once dirname(__FILE__) . '/../base.php';

        $add = Horde::applicationUrl('add.php');
        $add_img = Horde_Themes::img('add.png');

        $classes = Skoli::listClasses(false, Horde_Perms::EDIT);
        if (count($classes) > 0) {
            $tree->addNode(
                $parent . '__new',
                $parent,
                _("New Entry"),
                $indent + 1,
                false,
                array(
                    'icon' => $add_img,
                    'url' => $add
                )
            );

            foreach ($classes as $name => $class) {
                $tree->addNode(
                    $parent . $name . '__new',
                    $parent . '__new',
                    sprintf(_("in %s"), $class->get('name')),
                    $indent + 2,
                    false,
                    array(
                        'icon' => $add_img,
                        'url' => $add->copy()->add('class', $name)
                    )
                );
            }

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

}
