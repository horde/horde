<?php
/**
 * The Horde_Tree:: class provides a tree view of hierarchical information. It
 * allows for expanding/collapsing of branches.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Marko Djukic <marko@oblo.com>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Tree
 */
class Horde_Tree
{
    /**
     * Display extra columns to the left of the main tree.
     */
    const EXTRA_LEFT = 0;

    /**
     * Display extra columns to the right of the main tree.
     */
    const EXTRA_RIGHT = 1;

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
    protected $_extra_cols_left = 0;

    /**
     * Keep count of how many extra columns there are on the right side
     * of the node.
     *
     * @var integer
     */
    protected $_extra_cols_right = 0;

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
     * Attempts to return a concrete instance.
     *
     * @param string $name      The name of this tree instance.
     * @param string $renderer  Either the tree renderer driver or a full
     *                          class name to use.
     * @param array $params     Any additional parameters the constructor
     *                          needs.
     *
     * @return Horde_Tree  The newly created concrete instance.
     * @throws Horde_Tree_Exception
     */
    static public function factory($name, $renderer, $params = array())
    {
        $ob = null;

        /* Base drivers (in Tree/ directory). */
        $class = __CLASS__ . '_' . ucfirst($renderer);
        if (class_exists($class)) {
            $ob = new $class($name, $params);
        } else {
            /* Explicit class name, */
            $class = $renderer;
            if (class_exists($class)) {
                $ob = new $class($name, $params);
            }
        }

        if ($ob) {
            if ($ob->isSupported()) {
                return $ob;
            }

            return self::factory($name, $ob->fallback(), $params);
        }

        throw new Horde_Tree_Exception(__CLASS__ . ' renderer not found: ' . $renderer);
    }

    /**
     * Constructor.
     *
     * @param string $name   The name of this tree instance.
     * @param array $params  Additional parameters.
     * <pre>
     * alternate - (boolean) Alternate shading in the table?
     * class - (string) The class to use for the table.
     * hideHeaders - (boolean) Don't render any HTML for the header row, just
     *               use the widths.
     * lines - (boolean) Show tree lines?
     * multiline - (boolean) Do the node labels contain linebreaks?
     * session - (string) The name of the session array key to store data.
     *           If this is an empty string, session storage will be disabled.
     *           DEFAULT: No session storage
     * </pre>
     */
    public function __construct($name, array $params = array())
    {
        $this->_instance = $name;
        $this->setOption($params);

        if (!empty($this->_options['session']) &&
            !isset($_SESSION[$this->_options['session']][$this->_instance])) {
            $_SESSION[$this->_options['session']][$this->_instance] = array();
        }
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
    public function getTree($static = false)
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
     * @param string $option   The name of the option to fetch.
     * @param boolean $html    Whether to format the return value in HTML.
     * @param string $default  A default value to use in case none is set for
     *                         the requested option.
     *
     * @return mixed  The option's value.
     */
    public function getOption($option, $html = false, $default = null)
    {
        $value = null;

        if (!isset($this->_options[$option]) && !is_null($default)) {
            /* Requested option has not been but there is a
             * default. */
            $value = $default;
        } elseif (isset($this->_options[$option])) {
            /* Requested option has been set, get its value. */
            $value = $this->_options[$option];
        }

        if ($html && !is_null($value)) {
            /* Format value for html output. */
            $value = sprintf(' %s="%s"', $option, $value);
        }

        return $value;
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
     *                            addNodeParams() for full details).
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

        if (!empty($this->_options['session'])) {
            $sess = &$_SESSION[$this->_options['session']][$this->_instance];
            $toggle_id = Horde_Util::getFormData(self::TOGGLE . $this->_instance);

            if ($nodeid == $toggle_id) {
                /* We have a URL toggle request for this node. */
                $expanded = $sess['expanded'][$nodeid] = isset($sess['expanded'][$id])
                    /* Use session state if it is set. */
                    ? (!$sess['expanded'][$nodeid])
                    /* Otherwise use what was passed through the function. */
                    : (!$expanded);
            } elseif (isset($sess['expanded'][$nodeid])) {
                /* If we have a saved session state use it. */
                $expanded = $sess['expanded'][$nodeid];
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
            $this->addNodeExtra($id, self::EXTRA_RIGHT, $extra_right);
        }
        if (!empty($extra_left)) {
            $this->addNodeExtra($id, self::EXTRA_LEFT, $extra_left);
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
     * @param array $params  Any other parameters to set.
     * <pre>
     * class - CSS class to use with this node
     * icon - Icon to display next node
     * iconalt - Alt text to use for the icon
     * icondir - Icon directory
     * iconopen - Icon to indicate this node as expanded
     * onclick - Onclick event attached to this node
     * url - URL to link the node to
     * urlclass - CSS class for the node's URL
     * title - Link tooltip title
     * target - Target for the 'url' link
     * </pre>
     */
    public function addNodeParams($id, $params = array())
    {
        $id = $this->_nodeId($id);

        if (!is_array($params)) {
            $params = array($params);
        }

        $allowed = array(
            'class', 'icon', 'iconalt', 'icondir', 'iconopen',
            'onclick', 'url', 'urlclass', 'title', 'target',
        );

        foreach ($params as $param_id => $param_val) {
            // Set only allowed and non-null params.
            if (in_array($param_id, $allowed) && !is_null($param_val)) {
                $this->_nodes[$id][$param_id] = is_object($param_val)
                    ? strval($param_val)
                    : $param_val;
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
        case self::EXTRA_LEFT:
            $this->_nodes[$id]['extra'][self::EXTRA_LEFT] = $extra;
            if ($col_count > $this->_extra_cols_left) {
                $this->_extra_cols_left = $col_count;
            }
            break;

        case self::EXTRA_RIGHT:
            $this->_nodes[$id]['extra'][self::EXTRA_RIGHT] = $extra;
            if ($col_count > $this->_extra_cols_right) {
                $this->_extra_cols_right = $col_count;
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
     *                       information. The following keys are allowed:
     * <pre>
     * align - The alignment inside the header cell
     * class - The CSS class of the header cell
     * html - The HTML content of the header cell
     * width - The width of the header cell
     * </pre>
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

}
