<?php
/**
 * The Horde_Tree:: class provides a tree view of hierarchical information. It
 * allows for expanding/collapsing of branches and maintains their state.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Marko Djukic <marko@oblo.com>
 * @category Horde
 * @package  Horde_Tree
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
     * Singleton instances.
     *
     * @var array
     */
    static protected $_instances = array();

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
      * Image directory location.
      *
      * @var string
      */
    protected $_img_dir = '';

    /**
     * Images array.
     *
     * @var array
     */
    protected $_images = array(
        'line' => 'line.png',
        'blank' => 'blank.png',
        'join' => 'join.png',
        'join_bottom' => 'joinbottom.png',
        'plus' => 'plus.png',
        'plus_bottom' => 'plusbottom.png',
        'plus_only' => 'plusonly.png',
        'minus' => 'minus.png',
        'minus_bottom' => 'minusbottom.png',
        'minus_only' => 'minusonly.png',
        'null_only' => 'nullonly.png',
        'folder' => 'folder.png',
        'folderopen' => 'folderopen.png',
        'leaf' => 'leaf.png'
    );

    /**
     * Stores the sorting criteria temporarily.
     *
     * @var string
     */
    protected $_sortCriteria;

    /**
     * Use session to store cached Tree data?
     *
     * @var boolean
     */
    protected $_usesession = true;

    /**
     * Should the tree be rendered statically?
     *
     * @var boolean
     */
    protected $_static = false;

    /**
     * Attempts to return a reference to a concrete instance.
     * It will only create a new instance if no instance with the same
     * parameters currently exists.
     *
     * This method must be invoked as:
     *   $var = Horde_Tree::singleton($name[, $renderer[, $params]]);
     *
     * @param mixed $name       @see Horde_Tree::factory.
     * @param string $renderer  @see Horde_Tree::factory.
     * @param array $params     @see Horde_Tree::factory.
     *
     * @return Horde_Tree  The concrete instance.
     * @throws Horde_Exception
     */
    static public function singleton($name, $renderer, $params = array())
    {
        ksort($params);
        $id = $name . ':' . $renderer . ':' . serialize($params);

        if (!isset(self::$_instances[$id])) {
            self::$_instances[$id] = self::factory($name, $renderer, $params);
            if (!self::$_instances[$id]->isSupported()) {
                $renderer = self::fallback($renderer);
                return self::singleton($name, $renderer, $params);
            }
        }

        return self::$_instances[$id];
    }

    /**
     * Attempts to return a concrete instance.
     *
     * @param string $name     The name of this tree instance.
     * @param mixed $renderer  The type of concrete subclass to return. This
     *                         is based on the rendering driver. The code is
     *                         dynamically included.
     * @param array $params    Any additional parameters the constructor
     *                         needs.
     *
     * @return Horde_Tree  The newly created concrete instance.
     * @throws Horde_Exception
     */
    static public function factory($name, $renderer, $params = array())
    {
        $class = __CLASS__ . '_' . ucfirst($renderer);
        if (class_exists($class)) {
            return new $class($name, $params);
        }

        throw new Horde_Exception('Horde_Tree renderer not found: ' . $renderer);
    }

    /**
     * Try to fall back to a simpler renderer.
     *
     * @paran string $renderer  The renderer that we can't handle.
     *
     * @return string  The next best renderer.
     * @throws Horde_Exception
     */
    public function fallback($renderer)
    {
        switch ($renderer) {
        case 'javascript':
            return 'html';

        case 'html':
            throw new Horde_Exception('No fallback renderer found.');
        }
    }

    /**
     * Constructor.
     *
     * @param string $name   The name of this tree instance.
     * @param array $params  Additional parameters:
     * <pre>
     * 'nosession' - (boolean) If true, do not store tree data in session.
     * </pre>
     */
    public function __construct($name, $params = array())
    {
        $this->_instance = $name;
        $this->_usesession = empty($params['nosession']);
        unset($params['nosession']);
        $this->setOption($params);

        /* Set up the session for later to save tree states. */
        if ($this->_usesession &&
            !isset($_SESSION['horde_tree'][$this->_instance])) {
            $_SESSION['horde_tree'][$this->_instance] = array();
        }

        $this->_img_dir = $GLOBALS['registry']->getImageDir('horde') . '/tree';

        if (!empty($GLOBALS['nls']['rtl'][$GLOBALS['language']])) {
            $rev_imgs = array(
                'line', 'join', 'join_bottom', 'plus', 'plus_bottom',
                'plus_only', 'minus', 'minus_bottom', 'minus_only',
                'null_only', 'leaf'
            );
            foreach ($rev_imgs as $val) {
                $this->_images[$val] = 'rev-' . $this->_images[$val];
            }
        }
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
     * @param mixed $option  The option name -or- an array of option name/value
     *                       pairs. Available options:
     * <pre>
     * alternate - (boolean) Alternate shading in the table?
     * class - (string) The class to use for the table.
     * hideHeaders - (boolean) Don't render any HTML for the header row, just
     *               use the widths.
     * lines - (boolean) Show tree lines?
     * multiline - (boolean) Do the node labels contain linebreaks?
     * </pre>
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
        $this->_nodes[$id]['label'] = $label;

        if ($this->_usesession) {
            $session_state = $_SESSION['horde_tree'][$this->_instance];
            $toggle_id = Horde_Util::getFormData(self::TOGGLE . $this->_instance);
            if ($id == $toggle_id) {
                /* We have a url toggle request for this node. */
                if (isset($session_state['expanded'][$id])) {
                    /* Use session state if it is set. */
                    $expanded = (!$session_state['expanded'][$id]);
                } else {
                    /* Otherwise use what was passed through the
                     * function. */
                    $expanded = (!$expanded);
                }

                /* Save this state to session. */
                $_SESSION['horde_tree'][$this->_instance]['expanded'][$id] = $expanded;
            } elseif (isset($session_state['expanded'][$id])) {
                /* If we have a saved session state use it. */
                $expanded = $session_state['expanded'][$id];
            }
        }

        $this->_nodes[$id]['expanded'] = $expanded;

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
            if (!in_array($id, $this->_root_nodes)) {
                $this->_root_nodes[] = $id;
            }
        } else {
            if (empty($this->_nodes[$parent]['children'])) {
                $this->_nodes[$parent]['children'] = array();
            }
            if (!in_array($id, $this->_nodes[$parent]['children'])) {
                $this->_nodes[$parent]['children'][] = $id;
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
                // Cast Horde_Url objects
                if ($param_id == 'url') {
                    $param_val = (string)$param_val;
                }
                $this->_nodes[$id][$param_id] = $param_val;
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
     * Returns just the JS node definitions as a string.
     *
     * @return string  The Javascript node array definitions.
     */
    public function renderNodeDefinitions()
    {
    }

}
