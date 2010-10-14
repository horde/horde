<?php
/**
 * The Horde_Tree_Base:: class provides the abstract interface that all
 * drivers must derive from.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Tree
 */
abstract class Horde_Tree_Base implements Countable
{
    /**
     * Allowed parameters for nodes.
     *
     * @var array
     */
    protected $_allowed = array();

    /**
     * The name of this instance.
     *
     * @var string
     */
    protected $_instance = null;

    /**
     * Hash with header information.
     *
     * @var array
     */
    protected $_header = array();

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
     * Keep count of how many extra columns there are on the left side
     * of the node.
     *
     * @var integer
     */
    protected $_colsLeft = 0;

    /**
     * Keep count of how many extra columns there are on the right side
     * of the node.
     *
     * @var integer
     */
    protected $_colsRight = 0;

    /**
     * Option values.
     *
     * @var array
     */
    protected $_options = array(
        'lines' => true
    );

    /**
     * Stores the sorting criteria temporarily.
     *
     * @var string
     */
    protected $_sortCriteria;

    /**
     * Should the tree be rendered statically?
     *
     * @var boolean
     */
    protected $_static = false;

    /**
     * Constructor.
     *
     * @param string $name   The name of this tree instance.
     * @param array $params  Additional parameters.
     * <pre>
     * session - (array) Callbacks used to store session data. Must define
     *           two keys: 'get' and 'set'. Function definitions:
     *           (string) = get([string - Instance], [string - ID]);
     *           set([string - Instance], [string - ID], [boolean - value]);
     *           DEFAULT: No session storage
     * </pre>
     */
    public function __construct($name, array $params = array())
    {
        $this->_instance = $name;
        $this->setOption($params);
    }

    /**
     * Provide a simpler renderer to fallback to.
     *
     * @return string  The next best renderer.
     * @throws Horde_Tree_Exception
     */
    public function fallback()
    {
        throw new Horde_Tree_Exception('No fallback renderer found.');
    }

    /**
     * Returns the tree.
     *
     * @param boolean $static  If true the tree nodes can't be expanded and
     *                         collapsed and the tree gets rendered expanded.
     *
     * @return string  The HTML code of the rendered tree.
     */
    abstract public function getTree($static = false);

    /**
     * Renders the tree.
     *
     * @param boolean $static  If true the tree nodes can't be expanded and
     *                         collapsed and the tree gets rendered expanded.
     */
    public function renderTree($static = false)
    {
        echo $this->getTree($static);
    }

    /**
     * Sets an option.
     *
     * @param mixed $option  The option name -or- an array of option
     *                       name/value pairs. See constructor for available
     *                       options.
     * @param mixed $value   The option's value.
     */
    public function setOption($options, $value = null)
    {
        if (!is_array($options)) {
            $options = array($options => $value);
        }

        foreach ($options as $option => $value) {
            $this->_options[$option] = $value;
        }
    }

    /**
     * Gets an option's value.
     *
     * @param string $option  The name of the option to fetch.
     *
     * @return mixed  The option's value.
     */
    public function getOption($option)
    {
        return isset($this->_options[$option])
            ? $this->_options[$option]
            : null;
    }

    /**
     * Adds a node to the node tree array.
     *
     * @param string $id          The unique node id.
     * @param string $parent      The parent's unique node id.
     * @param string $label       The text label for the node.
     * @param string $indent      Deprecated, this is calculated automatically
     *                            based on the parent node.
     * @param boolean $expanded   Is this level expanded or not.
     * @param array $params       Any other parameters to set (@see
     *                            self::addNodeParams() for full details).
     * @param array $extra_right  Any other columns to display to the right of
     *                            the tree.
     * @param array $extra_left   Any other columns to display to the left of
     *                            the tree.
     */
    public function addNode($id, $parent, $label, $indent = null,
                            $expanded = true, $params = array(),
                            $extra_right = array(), $extra_left = array())
    {
        $nodeid = $this->_nodeId($id);

        if ($session = $this->getOption('session')) {
            $toggle_id = Horde_Util::getFormData(Horde_Tree::TOGGLE . $this->_instance);

            if ($nodeid == $toggle_id) {
                /* We have a URL toggle request for this node. */
                $expanded = (call_user_func($session['get'], $this->_instance, $id) !== null)
                    /* Use session state if it is set. */
                    ? !call_user_func($session['get'], $this->_instance, $nodeid)
                    /* Otherwise use what was passed through the function. */
                    : !$expanded;
                call_user_func($session['set'], $this->_instance, $nodeid, $expanded);
            } elseif (($exp_get = call_user_func($session['get'], $this->_instance, $nodeid)) !== null) {
                /* If we have a saved session state use it. */
                $expanded = $exp_get;
            }
        }

        $this->_nodes[$nodeid]['label'] = $label;
        $this->_nodes[$nodeid]['expanded'] = $expanded;

        /* If any params included here add them now. */
        if (!empty($params)) {
            $this->addNodeParams($id, $params);
        }

        /* If any extra columns included here add them now. */
        if (!empty($extra_right)) {
            $this->addNodeExtra($id, Horde_Tree::EXTRA_RIGHT, $extra_right);
        }
        if (!empty($extra_left)) {
            $this->addNodeExtra($id, Horde_Tree::EXTRA_LEFT, $extra_left);
        }

        if (is_null($parent)) {
            if (!in_array($nodeid, $this->_root_nodes)) {
                $this->_root_nodes[] = $nodeid;
            }
        } else {
            $parent = $this->_nodeId($parent);
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
        $id = $this->_nodeId($id);

        if (!is_array($params)) {
            $params = array($params);
        }

        foreach ($params as $p_id => $p_val) {
            // Set only allowed and non-null params.
            if (!is_null($p_val) && in_array($p_id, $this->_allowed)) {
                $this->_nodes[$id][$p_id] = is_object($p_val)
                    ? strval($p_val)
                    : $p_val;
            }
        }
    }

    /**
     * Adds extra columns to be displayed to the side of the node.
     *
     * @param mixed $id      The unique node id.
     * @param integer $side  Which side to place the extra columns on.
     * @param array $extra   Extra columns to display.
     */
    public function addNodeExtra($id, $side, $extra)
    {
        $id = $this->_nodeId($id);

        if (!is_array($extra)) {
            $extra = array($extra);
        }

        $col_count = count($extra);

        switch ($side) {
        case Horde_Tree::EXTRA_LEFT:
            $this->_nodes[$id]['extra'][Horde_Tree::EXTRA_LEFT] = $extra;
            if ($col_count > $this->_colsLeft) {
                $this->_colsLeft = $col_count;
            }
            break;

        case Horde_Tree::EXTRA_RIGHT:
            $this->_nodes[$id]['extra'][Horde_Tree::EXTRA_RIGHT] = $extra;
            if ($col_count > $this->_colsRight) {
                $this->_colsRight = $col_count;
            }
            break;
        }
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
        $id = $this->_nodeId($id);

        return isset($this->_nodes[$id])
            ? $this->_nodes[$id]['expanded']
            : false;
    }

    /**
     * Adds column headers to the tree table.
     *
     * @param array $header  An array containing hashes with header
     *                       information.
     */
    public function setHeader($header)
    {
        $this->_header = $header;
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
     * Check the current environment to see if we can render the tree.
     *
     * @return boolean  Whether or not this backend will function.
     */
    public function isSupported()
    {
        return true;
    }

    /**
     * Returns the escaped node ID.
     *
     * @param string $id  Node ID.
     *
     * @return string  Escaped node ID.
     */
    protected function _nodeId($id)
    {
        return rawurlencode($id);
    }

    /* Countable methods. */

    public function count()
    {
        return count($this->_nodes);
    }

}
