<?php
/**
 * The Horde_Tree_Sidebar class renders tree structures in the sidebar menus.
 *
 * Additional node parameters:
 * - icon: (string) Icon to display next node.
 * - onclick: (string) Value for onclick attribute.
 * - selected: (boolean) Whether the node is selected.
 * - target: (string) Target for the 'url' link.
 * - url: (string|Horde_Url) URL for the node link.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Horde
 */
class Horde_Tree_Renderer_Sidebar extends Horde_Tree_Renderer_Base
{
    /**
     * Returns the tree.
     *
     * @param boolean $static  Unused.
     *
     * @return string  The HTML code of the rendered tree.
     */
    public function getTree($static = false)
    {
        $view = $GLOBALS['injector']->getInstance('Horde_View');
        $view->setTemplatePath(array(
            $GLOBALS['registry']->get('templates', 'horde') . '/sidebar',
            $GLOBALS['registry']->get('templates', 'horde') . '/tree'
        ));
        $view->rootItems = $this->_tree->getRootNodes();
        $view->items = $this->_tree->getNodes();
        return $view->render('sidebar');
    }
}
