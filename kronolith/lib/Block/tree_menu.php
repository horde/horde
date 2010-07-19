<?php

$block_name = _("Menu List");
$block_type = 'tree';

/**
 * @package Horde_Block
 */
class Horde_Block_kronolith_tree_menu extends Horde_Block
{
    protected $_app = 'kronolith';

    protected function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        global $registry;

        $menus = array(
            array('new', _("New Event"), 'new.png', Horde::applicationUrl('new.php')),
            array('day', _("Day"), 'dayview.png', Horde::applicationUrl('day.php')),
            array('work', _("Work Week"), 'workweekview.png', Horde::applicationUrl('workweek.php')),
            array('week', _("Week"), 'weekview.png', Horde::applicationUrl('week.php')),
            array('month', _("Month"), 'monthview.png', Horde::applicationUrl('month.php')),
            array('year', _("Year"), 'yearview.png', Horde::applicationUrl('year.php')),
            array('search', _("Search"), 'search.png', Horde::applicationUrl('search.php'), (string)Horde_Themes::img(null, 'horde')),
        );

        foreach ($menus as $menu) {
            $tree->addNode($parent . $menu[0],
                           $parent,
                           $menu[1],
                           $indent + 1,
                           false,
                           array('icon' => $menu[2],
                                 'icondir' => isset($menu[4]) ? $menu[4] : (string)Horde_Themes::img(),
                                 'url' => $menu[3]));
        }
    }

}
