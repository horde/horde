<?php
/**
 * The Horde_Tree_Simplehtml:: class provides simple HTML rendering of a tree
 * (no graphics).
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Tree
 */
class Horde_Tree_Simplehtml extends Horde_Tree_Base
{
    /**
     * Allowed parameters for nodes.
     *
     * @var array
     */
    protected $_allowed = array(
        'class',
        'url'
    );

    /**
     * Should the tree be rendered statically?
     *
     * @var boolean
     */
    protected $_static = true;

    /**
     * Returns the tree.
     *
     * @return string  The HTML code of the rendered tree.
     */
    public function getTree($static = false)
    {
        $this->_buildIndents($this->_root_nodes);

        $tree = '';
        foreach ($this->_root_nodes as $node_id) {
            $tree .= $this->_buildTree($node_id);
        }

        return $tree;
    }

    /**
     * Adds additional parameters to a node.
     *
     * @param string $id     The unique node id.
     * @param array $params  Parameters to set (key/value pairs).
     * <pre>
     * class - CSS class to use with this node
     * url - URL to link the node to
     * </pre>
     */
    public function addNodeParams($id, $params = array())
    {
        parent::addNodeParams($id, $params);
    }

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
        if (isset($node['extra'][Horde_Tree::EXTRA_LEFT])) {
            $output .= implode(' ', $node['extra'][Horde_Tree::EXTRA_LEFT]);
        }
        $output .= str_repeat('&nbsp;', $node['indent'] * 2);

        $output .= empty($node['url'])
            ? $node['label']
            : '<a href="' . strval($node['url']) . '">' . $node['label'] . '</a>';
        if (isset($node['extra'][Horde_Tree::EXTRA_RIGHT])) {
            $output .= implode(' ', $node['extra'][Horde_Tree::EXTRA_RIGHT]);
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
        return $url->add(Horde_Tree::TOGGLE . $this->_instance, $node_id)->link();
    }

}
