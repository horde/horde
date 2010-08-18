<?php

$block_name = _("Menu List");
$block_type = 'tree';

/**
 *
 * @package Horde_Block
 */
class Horde_Block_mnemo_tree_menu extends Horde_Block
{
    protected $_app = 'mnemo';

    protected function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        $add = Horde::applicationUrl('memo.php')->add('actionID', 'add_memo');

        $tree->addNode(
            $parent . '__new',
            $parent,
            _("New Note"),
            $indent + 1,
            false,
            array(
                'icon' => Horde_Themes::img('add.png'),
                'url' => $add
            )
        );

        foreach (Mnemo::listNotepads() as $name => $notepad) {
            if ($notepad->get('owner') != $GLOBALS['registry']->getAuth() &&
                !empty($GLOBALS['conf']['share']['hidden']) &&
                !in_array($notepad->getName(), $GLOBALS['display_notepads'])) {

                continue;
            }

            $tree->addNode(
                $parent . $name . '__new',
                $parent . '__new',
                sprintf(_("in %s"), $notepad->get('name')),
                $indent + 2,
                false,
                array(
                    'icon' => Horde_Themes::img('add.png'),
                    'url' => $add->copy()->add('memolist', $name)
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
