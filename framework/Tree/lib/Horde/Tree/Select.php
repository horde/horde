<?php
/**
 * The Horde_Tree_Select:: class extends the Horde_Tree class to provide
 * <option> tag rendering.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Ben Chavet <ben@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Tree
 */
class Horde_Tree_Select extends Horde_Tree
{
    /**
     * Node list.
     *
     * @var array
     */
    protected $_nodes = array();

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
    public function getTree()
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
     * @param array $params  Any other parameters to set.
     * <pre>
     * selected - (boolean) Whether this node is selected.
     * </pre>
     */
    public function addNodeParams($id, $params = array())
    {
        $id = $this->_nodeId($id);

        if (!is_array($params)) {
            $params = array($params);
        }

        $allowed = array('selected');

        foreach ($params as $param_id => $param_val) {
            /* Set only allowed and non-null params. */
            if (in_array($param_id, $allowed) && !is_null($param_val)) {
                $this->_nodes[$id][$param_id] = $param_val;
            }
        }
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
        $selected = $this->_nodes[$node_id]['selected']
            ? ' selected="selected"'
            : '';

        $output = '<option value="' . htmlspecialchars($node_id) . '"' . $selected . '>' .
            str_repeat('&nbsp;&nbsp;', intval($this->_nodes[$node_id]['indent'])) . htmlspecialchars($this->_nodes[$node_id]['label']) .
            '</option>';

        if (isset($this->_nodes[$node_id]['children']) &&
            $this->_nodes[$node_id]['expanded']) {
            $num_subnodes = count($this->_nodes[$node_id]['children']);
            for ($c = 0; $c < $num_subnodes; ++$c) {
                $child_node_id = $this->_nodes[$node_id]['children'][$c];
                $output .= $this->_buildTree($child_node_id);
            }
        }

        return $output;
    }

}
