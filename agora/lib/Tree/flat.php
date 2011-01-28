<?php
/**
 * The Horde_Tree_agoraflat:: class extends the Horde_Tree class to provide
 * agora flat threded view.
 *
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Ben Chavet <ben@horde.org>
 * @author Duck <duck@obala.net>
 */
class Horde_Tree_agoraflat extends Horde_Tree {

    /**
     * TODO
     *
     * @var array
     */
    var $_nodes = array();

    /**
     * Constructor.
     */
    function Horde_Tree_agoraflat($tree_name, $params)
    {
        parent::Horde_Tree($tree_name, $params);
        $this->_static = true;
    }

    /**
     * Returns the tree.
     *
     * @return string  The HTML code of the rendered tree.
     */
    function getTree()
    {
        $this->_buildIndents($this->_root_nodes);

        $tree = '';
        foreach ($this->_root_nodes as $node_id) {
            $tree .= $this->_buildTree($node_id);
        }
        return $tree;
    }

    /**
     * Checks the current environment to see if we can render the HTML tree.
     * HTML is always renderable, at least until we add a php-gtk tree
     * backend, in which case this implementation will actually need a body.
     *
     * @static
     *
     * @return boolean  Whether or not this Tree:: backend will function.
     */
    function isSupported()
    {
        return true;
    }

    /**
     * Returns just the JS node definitions as a string. This is a no-op for
     * the select renderer.
     */
    function renderNodeDefinitions()
    {
    }

    /**
     * Adds additional parameters to a node.
     *
     * @param string $id     The unique node id.
     * @param array $params  Any other parameters to set.
     * <pre>
     * selected --  Whether this node is selected
     * </pre>
     */
    function addNodeParams($id, $params = array())
    {
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
     * @access private
     *
     * @param string $node_id  The Node ID.
     *
     * @return string  The tree rendering.
     */
    function _buildTree($node_id)
    {
        $extra = $this->_nodes[$node_id]['extra'][1];
        $output = '<div class="messageContainer" style="margin-left: ' . (int)$this->_nodes[$node_id]['indent'] . '0px">' . "\n"
                . '<div class="messageAuthor">' . "\n"
                . $extra['link'] . '<strong>' . $extra['message_subject'] . '</strong></a><br />' . "\n"
                . _("Posted by") . ': ' . $extra['message_author'] . "\n<br />"
                . _("on: ") . $extra['message_date'] . "\n"
                . ' <br /> ' . "\n";

        if (isset($extra['message_author_moderator'])) {
            $output .= _("Moderator") . '<br />';
        }

        if (!empty($extra['actions'])) {
            $output .= '<span class="small"> [ ' . implode(', ', $extra['actions']) . ' ] </span>';
        }

        $output .= '</div>' . "\n"
                 . '<div class="messageBody"><p>' . $this->_nodes[$node_id]['label'] . '</p></div>' . "\n"
                 . '<br class="clear" /></div>' . "\n";

        if (isset($this->_nodes[$node_id]['children']) &&
            $this->_nodes[$node_id]['expanded']) {
            $num_subnodes = count($this->_nodes[$node_id]['children']);
            for ($c = 0; $c < $num_subnodes; $c++) {
                $child_node_id = $this->_nodes[$node_id]['children'][$c];
                $output .= $this->_buildTree($child_node_id);
            }
        }

        return $output;
    }
}
