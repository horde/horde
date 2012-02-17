<?php
/**
 * The Horde_Tree_Jquerymobile class provides rendering of a tree as a jQuery
 * Mobile list view.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Tree
 */
class Horde_Tree_Jquerymobile extends Horde_Tree_Base
{
    /**
     * Allowed parameters for nodes.
     *
     * @var array
     */
    protected $_allowed = array(
        'class',
        'icon',
        'special',
        'url',
        'urlattributes',
    );

    /**
     * Returns the tree.
     *
     * @return string  The HTML code of the rendered tree.
     */
    public function getTree($static = false)
    {
        $tree = '';
        foreach (array(true, false) as $special) {
            foreach ($this->_root_nodes as $node_id) {
                $tree .= $this->_buildTree($node_id, $special);
            }
        }

        return $tree;
    }

    /**
     * Recursive function to walk through the tree array and build the output.
     *
     * @param string $node_id  The Node ID.
     *
     * @return string  The tree rendering.
     */
    protected function _buildTree($node_id, $special)
    {
        $node = $this->_nodes[$node_id];
        $output = '';

        if ($node['special'] == $special) {
            $output = '<li';
            if (isset($node['class'])) {
                $output .= ' class="' . $node['class'] . '"';
            }
            $output .= '>';
            if (isset($node['extra'][Horde_Tree::EXTRA_LEFT])) {
                $output .= implode(' ', $node['extra'][Horde_Tree::EXTRA_LEFT]);
            }
            if (!empty($node['url'])) {
                $output .= '<a href="' . (string)$node['url'] . '"';
                if (isset($node['urlattributes'])) {
                    foreach ($node['urlattributes'] as $attribute => $value) {
                        $output .= ' ' . $attribute . '="' . htmlspecialchars($value) . '"';
                    }
                }
                $output .= '>';
            }
            $output .= $this->_getIcon($node_id) . $node['label'];
            if (!empty($node['url'])) {
                $output .= '</a>';
            }
            if (isset($node['extra'][Horde_Tree::EXTRA_RIGHT])) {
                $output .= '<span class="ui-li-count">' . implode(' ', $node['extra'][Horde_Tree::EXTRA_RIGHT]) . '</span>';
            }
            $output .= '</li>';
        }

        if (isset($node['children'])) {
            foreach ($node['children'] as $val) {
                $output .= $this->_buildTree($val, $special);
            }
        }

        return $output;
    }

    /**
     * Sets the icon for the node.
     *
     * @param string $node_id  The Node ID.
     *
     * @return string  The node icon for the tree line.
     */
    protected function _getIcon($node_id)
    {
        $node = $this->_nodes[$node_id];
        if (empty($node['icon'])) {
            return '';
        }
        return '<img src="' . $node['icon'] . '" class="ui-li-icon">';
    }

}
