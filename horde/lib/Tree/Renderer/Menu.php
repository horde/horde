<?php
/**
 * The Horde_Tree_Menu class renders the tree structure of the top
 * application menu.
 *
 * Additional node parameters:
 * - noarrow: (boolean) Whether to hide the arrow next to the top level menu
 *            entry.
 * - onclick: (string) Value for onclick attribute.
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
class Horde_Tree_Renderer_Menu extends Horde_Tree_Renderer_Base
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
        $view->setTemplatePath($GLOBALS['registry']->get('templates', 'horde') . '/tree');
        $view->rootItems = $this->_tree->getRootNodes();
        $view->items = $this->_tree->getNodes();
        return $view->render('menu');
    }

    /**
     * Returns just the JS node definitions as a string.
     *
     * @return object  Object with the following properties: 'files',
     *                 'nodes', 'root_nodes'.
     */
    public function renderNodeDefinitions()
    {
        $result = new stdClass;
        $result->nodes = $this->_tree->getNodes();
        $result->root_nodes = $this->_tree->getRootNodes();
        $result->files = array();

        foreach ($GLOBALS['page_output']->hsl as $val) {
            /* Ignore files that are already loaded before building the
             * tree. */
            if (!in_array($val->file, array('prototype.js', 'hordetree.js', 'accesskeys.js'))) {
                $result->files[] = strval($val->url);
            }
        }

        return $result;
    }
}
