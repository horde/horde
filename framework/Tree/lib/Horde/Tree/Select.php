<?php
/**
 * The Horde_Tree_Select:: class provides <option> tag rendering.
 *
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Ben Chavet <ben@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Tree
 */
class Horde_Tree_Select extends Horde_Tree_Base
{
    /**
     * Allowed parameters for nodes.
     *
     * @var array
     */
    protected $_allowed = array(
        'selected'
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
     * @param boolean $static  If true the tree nodes can't be expanded and
     *                         collapsed and the tree gets rendered expanded.
     *                         This option has no effect in this driver.
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
     * selected - (boolean) Whether this node is selected.
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

        $output = '<option value="' . htmlspecialchars($node_id) . '"' .
            (empty($node['selected']) ? '' : ' selected="selected"') .
            '>' .
            str_repeat('&nbsp;', intval($node['indent']) * 2) .
            htmlspecialchars($node['label']) .
            '</option>';

        if (isset($node['children']) && $node['expanded']) {
            foreach ($node['children'] as $val) {
                $output .= $this->_buildTree($val);
            }
        }

        return $output;
    }

}
