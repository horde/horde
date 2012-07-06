<?php
/**
 * The Horde_Tree_Renderer_Simplehtml class provides simple HTML
 * rendering of a tree (no graphics).
 *
 * Additional node parameters:
 * - class: CSS class to use with the node
 * - url: URL to link the node to
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
class Horde_Tree_Renderer_Simplehtml extends Horde_Tree_Renderer_Base
{
    /**
     * Should the tree be rendered statically?
     *
     * @var boolean
     */
    protected $_static = true;

    /**
     * Recursive function to walk through the tree array and build the output.
     *
     * @param string $node_id  The Node ID.
     *
     * @return string  The tree rendering.
     */
    protected function _buildTree($node_id)
    {
        $node = $this->_nodes[$node_id];

        $output = '<div' .
            (empty($node['class']) ? '' : ' class="' . $node['class'] . '"') .
            '>';
        if (isset($this->_extra[$node_id][Horde_Tree_Renderer::EXTRA_LEFT])) {
            $output .= implode(' ', $this->_extra[$node_id][Horde_Tree_Renderer::EXTRA_LEFT]);
        }
        $output .= str_repeat('&nbsp;', $node['indent'] * 2);

        $output .= empty($node['url'])
            ? $node['label']
            : '<a href="' . strval($node['url']) . '">' . $node['label'] . '</a>';
        if (isset($this->_extra[$node_id][Horde_Tree_Renderer::EXTRA_RIGHT])) {
            $output .= implode(' ', $this->_extra[$node_id][Horde_Tree_Renderer::EXTRA_RIGHT]);
        }

        if (isset($node['children'])) {
            $output .= '&nbsp;[' .
                $this->_generateUrlTag($node_id) .
                ($node['expanded'] ? '-' : '+') .
                '</a>]';
        }

        $output .= '</div>';

        if (isset($node['children']) && $node['expanded']) {
            foreach ($node['children'] as $val) {
                $output .= $this->_buildTree($val);
            }
        }

        return $output;
    }

    /**
     * Generate a link URL.
     *
     * @param string $node_id  The node ID.
     *
     * @return string  The link tag.
     */
    protected function _generateUrlTag($node_id)
    {
        $url = new Horde_Url($_SERVER['PHP_SELF']);
        return $url->add(Horde_Tree::TOGGLE . $this->_tree->instance, $node_id)->link();
    }

}
