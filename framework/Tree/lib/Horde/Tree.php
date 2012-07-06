<?php
/**
 * The Horde_Tree class provides a tree view of hierarchical
 * information. It allows for expanding/collapsing of branches.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Marko Djukic <marko@oblo.com>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Tree
 */
class Horde_Tree implements Countable
{
    /**
     * The preceding text, before the Horde_Tree instance name, used for
     * collapse/expand submissions.
     */
    const TOGGLE = 'ht_toggle_';

    /**
     * The name of this instance.
     *
     * @var string
     */
    public $instance;

    /**
     * Callbacks used to store session data.
     *
     * @var array
     */
    protected $_session = array();

    /**
     * An array containing all the tree nodes.
     *
     * @var array
     */
    protected $_nodes = array();

    /**
     * The top-level nodes in the tree.
     *
     * @var array
     */
    protected $_root_nodes = array();

    /**
     * Constructor.
     *
     * @param string $name    The name of this tree instance.
     * @param array $session  Callbacks used to store session data. Must define
     *                        two keys: 'get' and 'set'. Function definitions:
     *                        (string) = get([string - Instance], [string - ID]);
     *                        set([string - Instance], [string - ID], [boolean - value]);
     *                        DEFAULT: No session storage
     */
    public function __construct($name, $session = array())
    {
        $this->instance = $name;
        $this->_session = $session;
    }

    /**
     * Adds a node to the node tree array.
     *
     * @param array $node  A hash with node properties:
     *                     - id:       (string) The unique node id.
     *                     - parent:   (string) The parent's unique node id.
     *                     - label:    (string) The text label for the node.
     *                     - expanded: (boolean) Is this level expanded or not.
     *                     - params:   (array) Any other parameters to set
     *                                 (see addNodeParams() of the renderers
     *                                 for full details).
     */
    public function addNode($node)
    {
        $node = array_merge(
            array('parent' => null,
                  'expanded' => true,
                  'params' => array()),
            $node);

        $nodeid = $this->nodeId($node['id']);
        $expanded = $node['expanded'];

        if ($this->_session) {
            $toggle_id = Horde_Util::getFormData(Horde_Tree::TOGGLE . $this->instance);

            if ($nodeid == $toggle_id) {
                /* We have a URL toggle request for this node. */
                $expanded = (call_user_func($this->_session['get'], $this->instance, $node['id']) !== null)
                    /* Use session state if it is set. */
                    ? !call_user_func($this->_session['get'], $this->instance, $nodeid)
                    /* Otherwise use what was passed through the function. */
                    : !$node['expanded'];
                call_user_func($this->_session['set'], $this->instance, $nodeid, $expanded);
            } elseif (($exp_get = call_user_func($this->_session['get'], $this->instance, $nodeid)) !== null) {
                /* If we have a saved session state use it. */
                $expanded = $exp_get;
            }
        }

        $this->_nodes[$nodeid]['label'] = $node['label'];
        $this->_nodes[$nodeid]['expanded'] = $expanded;

        /* If any params included here add them now. */
        if (!empty($node['params'])) {
            $this->addNodeParams($node['id'], $node['params']);
        }

        if (is_null($node['parent'])) {
            if (!in_array($nodeid, $this->_root_nodes)) {
                $this->_root_nodes[] = $nodeid;
            }
        } else {
            $parent = $this->nodeId($node['parent']);
            if (empty($this->_nodes[$parent]['children'])) {
                $this->_nodes[$parent]['children'] = array();
            }
            if (!in_array($nodeid, $this->_nodes[$parent]['children'])) {
                $this->_nodes[$parent]['children'][] = $nodeid;
            }
        }
    }

    /**
     * Adds additional parameters to a node.
     *
     * @param string $id     The unique node id.
     * @param array $params  Parameters to set (key/value pairs).
     */
    public function addNodeParams($id, $params = array())
    {
        $id = $this->nodeId($id);

        if (!is_array($params)) {
            $params = array($params);
        }

        foreach ($params as $p_id => $p_val) {
            // Set only non-null params.
            if (!is_null($p_val)) {
                $this->_nodes[$id][$p_id] = is_object($p_val)
                    ? strval($p_val)
                    : $p_val;
            }
        }
    }

    /**
     * Returns the root node IDs.
     *
     * @return array  The root nodes.
     */
    public function getRootNodes()
    {
        return $this->_root_nodes;
    }

    /**
     * Returns the nodes of the tree.
     *
     * @return array  The nodes with IDs as keys and node hashes as values.
     */
    public function getNodes()
    {
        $this->_buildIndents($this->_root_nodes);
        return $this->_nodes;
    }

    /**
     * Sorts the tree by the specified node property.
     *
     * @param string $criteria  The node property to sort by.
     * @param integer $id       Used internally for recursion.
     */
    public function sort($criteria, $id = -1)
    {
        if (!isset($this->_nodes[$id]['children'])) {
            return;
        }

        if ($criteria == 'key') {
            ksort($this->_nodes[$id]['children']);
        } else {
            $this->_sortCriteria = $criteria;
            usort($this->_nodes[$id]['children'], array($this, 'sortHelper'));
        }

        foreach ($this->_nodes[$id]['children'] as $child) {
            $this->sort($criteria, $child);
        }
    }

    /**
     * Helper method for sort() to compare two tree elements.
     */
    public function sortHelper($a, $b)
    {
        if (!isset($this->_nodes[$a][$this->_sortCriteria])) {
            return 1;
        }

        if (!isset($this->_nodes[$b][$this->_sortCriteria])) {
            return -1;
        }

        return strcoll($this->_nodes[$a][$this->_sortCriteria],
                       $this->_nodes[$b][$this->_sortCriteria]);
    }

    /**
     * Returns whether the specified node is currently expanded.
     *
     * @param mixed $id  The unique node id.
     *
     * @return boolean  True if the specified node is expanded.
     */
    public function isExpanded($id)
    {
        $id = $this->nodeId($id);

        return isset($this->_nodes[$id])
            ? $this->_nodes[$id]['expanded']
            : false;
    }

    /**
     * Set the indent level for each node in the tree.
     *
     * @param array $nodes     TODO
     * @param integer $indent  TODO
     */
    protected function _buildIndents($nodes, $indent = 0)
    {
        foreach ($nodes as $id) {
            $this->_nodes[$id]['indent'] = $indent;
            if (!empty($this->_nodes[$id]['children'])) {
                $this->_buildIndents($this->_nodes[$id]['children'], $indent + 1);
            }
        }
    }

    /**
     * Returns the escaped node ID.
     *
     * @param string $id  Node ID.
     *
     * @return string  Escaped node ID.
     */
    public function nodeId($id)
    {
        return rawurlencode($id);
    }

    /* Countable methods. */

    public function count()
    {
        return count($this->_nodes);
    }
}
