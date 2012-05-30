<?php
/**
 * The Horde_Tree_Renderer_Base class provides the abstract interface
 * that all drivers must derive from.
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
abstract class Horde_Tree_Renderer_Base
{
    /**
     * The tree object.
     *
     * @var Horde_Tree
     */
    protected $_tree;

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
     * An array containing extra columns for the tree nodes.
     *
     * @var array
     */
    protected $_extra = array();

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
     * @param Horde_Tree $tree  A tree object.
     * @param array $params     Additional parameters.
     */
    public function __construct(Horde_Tree $tree, array $params = array())
    {
        $this->_tree = $tree;
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
     *                         This option has no effect in this driver.
     *
     * @return string  The HTML code of the rendered tree.
     */
    public function getTree($static = false)
    {
        $this->_nodes = $this->_tree->getNodes();

        $tree = '';
        foreach ($this->_tree->getRootNodes() as $node_id) {
            $tree .= $this->_buildTree($node_id);
        }

        return $tree;
    }

    /**
     * Recursive function to walk through the tree array and build the output.
     *
     * Should be overwritten by a sub-class if it doesn't implement
     * its own getTree() method.
     *
     * @param string $node_id  The Node ID.
     *
     * @return string  The tree rendering.
     */
    protected function _buildTree($id)
    {
        return '';
    }

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
        $this->_tree->addNode($node);

        /* If any extra columns included here add them now. */
        if (!empty($node['right'])) {
            $this->addNodeExtra($node['id'],
                                Horde_Tree_Renderer::EXTRA_RIGHT,
                                $node['right']);
        }
        if (!empty($node['left'])) {
            $this->addNodeExtra($node['id'],
                                Horde_Tree_Renderer::EXTRA_LEFT,
                                $node['left']);
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
        $this->_tree->addNodeParams($id, $params);
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
        $id = $this->_tree->nodeId($id);

        if (!is_array($extra)) {
            $extra = array($extra);
        }

        $col_count = count($extra);

        switch ($side) {
        case Horde_Tree_Renderer::EXTRA_LEFT:
            $this->_extra[$id][Horde_Tree_Renderer::EXTRA_LEFT] = $extra;
            if ($col_count > $this->_colsLeft) {
                $this->_colsLeft = $col_count;
            }
            break;

        case Horde_Tree_Renderer::EXTRA_RIGHT:
            $this->_extra[$id][Horde_Tree_Renderer::EXTRA_RIGHT] = $extra;
            if ($col_count > $this->_colsRight) {
                $this->_colsRight = $col_count;
            }
            break;
        }
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
     * Sorts the tree by the specified node property.
     *
     * @param string $criteria  The node property to sort by.
     */
    public function sort($criteria)
    {
        return $this->_tree->sort($criteria);
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
}
