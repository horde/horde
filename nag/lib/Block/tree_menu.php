<?php

$block_name = _("Menu List");
$block_type = 'tree';

/**
 * @package Horde_Block
 */
class Horde_Block_nag_tree_menu extends Horde_Block
{
    protected $_app = 'nag';

    protected function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        global $registry;

        $add = Horde::applicationUrl('task.php')->add('actionID', 'add_task');
        $icondir = (string)Horde_Themes::img();

        $tree->addNode($parent . '__new',
                       $parent,
                       _("New Task"),
                       $indent + 1,
                       false,
                       array('icon' => 'add.png',
                             'icondir' => $icondir,
                             'url' => $add));

        foreach (Nag::listTasklists() as $name => $tasklist) {
            if ($tasklist->get('owner') != $GLOBALS['registry']->getAuth() &&
                !empty($GLOBALS['conf']['share']['hidden']) &&
                !in_array($tasklist->getName(), $GLOBALS['display_tasklists'])) {
                continue;
            }
            $tree->addNode($parent . $name . '__new',
                           $parent . '__new',
                           sprintf(_("in %s"), $tasklist->get('name')),
                           $indent + 2,
                           false,
                           array('icon' => 'add.png',
                                 'icondir' => $icondir,
                                 'url' => Horde_Util::addParameter($add, array('tasklist_id' => $name))));
        }

        $tree->addNode($parent . '__search',
                       $parent,
                       _("Search"),
                       $indent + 1,
                       false,
                       array('icon' => 'search.png',
                             'icondir' => (string)Horde_Themes::img(null, 'horde'),
                             'url' => Horde::applicationUrl('search.php')));
    }

}
