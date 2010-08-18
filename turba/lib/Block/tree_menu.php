<?php

$block_name = _("Menu List");
$block_type = 'tree';

/**
 * @package Horde_Block
 */
class Horde_Block_turba_tree_menu extends Horde_Block
{
    protected $_app = 'turba';

    protected function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        $browse = Horde::applicationUrl('browse.php');
        $add = Horde::applicationUrl('add.php');

        if ($GLOBALS['addSources']) {
            $newimg = Horde_Themes::img('menu/new.png');

            $tree->addNode(
                $parent . '__new',
                $parent,
                _("New Contact"),
                $indent + 1,
                false,
                array(
                    'icon' => $newimg,
                    'url' => $add
                )
            );

            foreach ($GLOBALS['addSources'] as $addressbook => $config) {
                $tree->addNode(
                    $parent . $addressbook . '__new',
                    $parent . '__new',
                    sprintf(_("in %s"), $config['title']),
                    $indent + 2,
                    false,
                    array(
                        'icon' => $newimg,
                        'url' => $add->copy()->add('source', $addressbook)
                    )
                );
            }
        }

        foreach (Turba::getAddressBooks() as $addressbook => $config) {
            if (!empty($config['browse'])) {
                $tree->addNode(
                    $parent . $addressbook,
                    $parent,
                    $config['title'],
                    $indent + 1,
                    false,
                    array(
                        'icon' => Horde_Themes::img('menu/browse.png'),
                        'url' => $browse->copy()->add('source', $addressbook)
                    )
                );
            }
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
