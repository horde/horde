<?php
/**
 * The Horde_Core_Tree_Menu class renders the tree structure of the top
 * application menu.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Tree_Menu extends Horde_Tree_Base
{
    /**
     * Allowed parameters for nodes.
     *
     * @var array
     */
    protected $_allowed = array(
        'active',
        'class',
        'icon',
        'noarrow',
        'url',
        'target'
    );

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
        $view->setTemplatePath($GLOBALS['registry']->get('templates', 'horde') . '/topbar');
        $view->rootItems = $this->_root_nodes;
        $view->items = $this->_nodes;
        return $view->render('menu');
    }
}
