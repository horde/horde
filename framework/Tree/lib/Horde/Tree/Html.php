<?php
/**
 * The Horde_Tree_Html:: class extends the Horde_Tree class to provide
 * HTML specific rendering functions.
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
class Horde_Tree_Html extends Horde_Tree
{
    /**
     * Node list.
     *
     * @var array
     */
    protected $_nodes = array();

    /**
     * Node position list.
     *
     * @var array
     */
    protected $_node_pos = array();

    /**
     * Drop line cache.
     *
     * @var array
     */
    protected $_dropline = array();

    /**
     * Current value of the alt tag count.
     *
     * @var integer
     */
    protected $_alt_count = 0;

    /**
     * Allowed parameters for nodes.
     *
     * @var array
     */
    protected $_allowed = array(
        'class',
        'icon',
        'iconalt',
        'iconopen',
        'onclick',
        'url',
        'urlclass',
        'title',
        'target'
    );

    /**
     * Images array.
     *
     * @var array
     */
    protected $_images = array(
        'line' => null,
        'blank' => null,
        'join' => null,
        'join_bottom' => null,
        'plus' => null,
        'plus_bottom' => null,
        'plus_only' => null,
        'minus' => null,
        'minus_bottom' => null,
        'minus_only' => null,
        'null_only' => null,
        'folder' => null,
        'folderopen' => null,
        'leaf' => null
    );

    /**
     * Constructor.
     *
     * @param string $name   The name of this tree instance.
     * @param array $params  Additional parameters:
     * <pre>
     * alternate - (boolean) Alternate shading in the table?
     * class - (string) The class to use for the table.
     * lines - (boolean) Show tree lines?
     * multiline - (boolean) Do the node labels contain linebreaks?
     * </pre>
     */
    public function __construct($name, array $params = array())
    {
        parent::__construct($name, $params);
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
        $this->_static = (bool)$static;
        $this->_buildIndents($this->_root_nodes);

        $tree = $this->_buildHeader();
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
     * icon - Icon to display next node
     * iconalt - Alt text to use for the icon
     * iconopen - Icon to indicate this node as expanded
     * onclick - Onclick event attached to this node
     * url - URL to link the node to
     * urlclass - CSS class for the node's URL
     * target - Target for the 'url' link
     * title - Link tooltip title
     * </pre>
     */
    public function addNodeParams($id, $params = array())
    {
        parent::addNodeParams($id, $params);
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
        parent::setHeader($header);
    }

    /**
     * Returns the HTML code for a header row, if necessary.
     *
     * @return string  The HTML code of the header row or an empty string.
     */
    protected function _buildHeader()
    {
        if (!count($this->_header)) {
            return '';
        }

        $html = '<div';
        /* If using alternating row shading, work out correct
         * shade. */
        if ($this->getOption('alternate')) {
            $html .= ' class="item' . $this->_alt_count . '"';
            $this->_alt_count = 1 - $this->_alt_count;
        }
        $html .= '>';

        foreach ($this->_header as $header) {
            $html .= '<div class="leftFloat';
            if (!empty($header['class'])) {
                $html .= ' ' . $header['class'];
            }
            $html .= '"';

            $style = '';
            if (!empty($header['width'])) {
                $style .= 'width:' . $header['width'] . ';';
            }
            if (!empty($header['align'])) {
                $style .= 'text-align:' . $header['align'] . ';';
            }
            if (!empty($style)) {
                $html .= ' style="' . $style . '"';
            }
            $html .= '>';
            $html .= empty($header['html']) ? '&nbsp;' : $header['html'];
            $html .= '</div>';
        }

        return $html . '</div>';
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
        $output = $this->_buildLine($node_id);

        if (isset($this->_nodes[$node_id]['children']) &&
            $this->_nodes[$node_id]['expanded']) {
            $num_subnodes = count($this->_nodes[$node_id]['children']);
            for ($c = 0; $c < $num_subnodes; $c++) {
                $child_node_id = $this->_nodes[$node_id]['children'][$c];
                $this->_node_pos[$child_node_id] = array();
                $this->_node_pos[$child_node_id]['pos'] = $c + 1;
                $this->_node_pos[$child_node_id]['count'] = $num_subnodes;
                $output .= $this->_buildTree($child_node_id);
            }
        }

        return $output;
    }

    /**
     * Function to create a single line of the tree.
     *
     * @param string $node_id  The Node ID.
     *
     * @return string  The rendered line.
     */
    protected function _buildLine($node_id)
    {
        $className = 'treeRow';
        if (!empty($this->_nodes[$node_id]['class'])) {
            $className .= ' ' . $this->_nodes[$node_id]['class'];
        }
        /* If using alternating row shading, work out correct
         * shade. */
        if ($this->getOption('alternate')) {
            $className .= ' item' . $this->_alt_count;
            $this->_alt_count = 1 - $this->_alt_count;
        }

        $line = '<div class="' . $className . '">';

        /* If we have headers, track which logical "column" we're in
         * for any given cell of content. */
        $column = 0;

        if (isset($this->_nodes[$node_id]['extra'][self::EXTRA_LEFT])) {
            $extra = $this->_nodes[$node_id]['extra'][self::EXTRA_LEFT];
            $cMax = count($extra);
            for ($c = 0; $c < $cMax; ++$c) {
                $style = '';
                if (isset($this->_header[$column]['width'])) {
                    $style .= 'width:' . $this->_header[$column]['width'] . ';';
                }

                $line .= '<div class="leftFloat"';
                if (!empty($style)) {
                    $line .= ' style="' . $style . '"';
                }
                $line .= '>' . $extra[$c] . '</div>';

                $column++;
            }
        }

        $style = '';
        if (isset($this->_header[$column]['width'])) {
            $style .= 'width:' . $this->_header[$column]['width'] . ';';
        }
        $line .= '<div class="leftFloat"';
        if (!empty($style)) {
            $line .= ' style="' . $style . '"';
        }
        $line .= '>';

        if ($this->getOption('multiline')) {
            $line .= '<table cellspacing="0"><tr><td>';
        }

        for ($i = intval($this->_static); $i < $this->_nodes[$node_id]['indent']; ++$i) {
            $line .= $this->_generateImage(($this->_dropline[$i] && $this->getOption('lines', false, true)) ? $this->_images['line'] : $this->_images['blank']);
        }
        $line .= $this->_setNodeToggle($node_id) . $this->_setNodeIcon($node_id);
        if ($this->getOption('multiline')) {
            $line .= '</td><td>';
        }
        $line .= $this->_setLabel($node_id);

        if ($this->getOption('multiline')) {
            $line .= '</td></tr></table>';
        }

        $line .= '</div>';
        ++$column;

        if (isset($this->_nodes[$node_id]['extra'][self::EXTRA_RIGHT])) {
            $extra = $this->_nodes[$node_id]['extra'][self::EXTRA_RIGHT];
            $cMax = count($extra);
            for ($c = 0; $c < $cMax; ++$c) {
                $style = '';
                if (isset($this->_header[$column]['width'])) {
                    $style .= 'width:' . $this->_header[$column]['width'] . ';';
                }

                $line .= '<div class="leftFloat"';
                if (!empty($style)) {
                    $line .= ' style="' . $style . '"';
                }
                $line .= '>' . $extra[$c] . '</div>';

                $column++;
            }
        }

        return $line . "</div>\n";
    }

    /**
     * Sets the label on the tree line.
     *
     * @param string $node_id  The Node ID.
     *
     * @return string  The label for the tree line.
     */
    protected function _setLabel($node_id)
    {
        $n = $this->_nodes[$node_id];

        $output = '<span';
        if (!empty($n['onclick'])) {
            $output .= ' onclick="' . $n['onclick'] . '"';
        }
        $output .= '>';

        $label = $n['label'];
        if (!empty($n['url'])) {
            $target = '';
            if (!empty($n['target'])) {
                $target = ' target="' . $n['target'] . '"';
            } elseif ($target = $this->getOption('target')) {
                $target = ' target="' . $target . '"';
            }
            $output .= '<a' . (!empty($n['urlclass']) ? ' class="' . $n['urlclass'] . '"' : '') . ' href="' . $n['url'] . '"' . $target . '>' . $label . '</a>';
        } else {
            $output .= $label;
        }

        return $output . '</span>';
    }

    /**
     * Sets the node toggle on the tree line.
     *
     * @param string $node_id  The Node ID.
     *
     * @return string  The node toggle for the tree line.
     */
    protected function _setNodeToggle($node_id)
    {
        $link_start = '';

        if (($this->_nodes[$node_id]['indent'] == 0) &&
            isset($this->_nodes[$node_id]['children'])) {
            /* Top level node with children. */
            $this->_dropline[0] = false;
            if ($this->_static) {
                return '';
            } elseif (!$this->getOption('lines', false, true)) {
                $img = $this->_images['blank'];
            } elseif ($this->_nodes[$node_id]['expanded']) {
                $img = $this->_images['minus_only'];
            } else {
                $img = $this->_images['plus_only'];
            }

            if (!$this->_static) {
                $link_start = $this->_generateUrlTag($node_id);
            }
        } elseif (($this->_nodes[$node_id]['indent'] != 0) &&
            !isset($this->_nodes[$node_id]['children'])) {
            /* Node without children. */
            if ($this->_node_pos[$node_id]['pos'] < $this->_node_pos[$node_id]['count']) {
                /* Not last node. */
                $img = $this->getOption('lines', false, true)
                    ? $this->_images['join']
                    : $this->_images['blank'];

                $this->_dropline[$this->_nodes[$node_id]['indent']] = true;
            } else {
                /* Last node. */
                $img = $this->getOption('lines', false, true)
                    ? $this->_images['join_bottom']
                    : $this->_images['blank'];

                $this->_dropline[$this->_nodes[$node_id]['indent']] = false;
            }
        } elseif (isset($this->_nodes[$node_id]['children'])) {
            /* Node with children. */
            if ($this->_node_pos[$node_id]['pos'] < $this->_node_pos[$node_id]['count']) {
                /* Not last node. */
                if (!$this->getOption('lines', false, true)) {
                    $img = $this->_images['blank'];
                } elseif ($this->_static) {
                    $img = $this->_images['join'];
                } elseif ($this->_nodes[$node_id]['expanded']) {
                    $img = $this->_images['minus'];
                } else {
                    $img = $this->_images['plus'];
                }
                $this->_dropline[$this->_nodes[$node_id]['indent']] = true;
            } else {
                /* Last node. */
                if (!$this->getOption('lines', false, true)) {
                    $img = $this->_images['blank'];
                } elseif ($this->_static) {
                    $img = $this->_images['join_bottom'];
                } elseif ($this->_nodes[$node_id]['expanded']) {
                    $img = $this->_images['minus_bottom'];
                } else {
                    $img = $this->_images['plus_bottom'];
                }
                $this->_dropline[$this->_nodes[$node_id]['indent']] = false;
            }

            if (!$this->_static) {
                $link_start = $this->_generateUrlTag($node_id);
            }
        } else {
            /* Top level node with no children. */
            if ($this->_static) {
                return '';
            }

            $img = $this->getOption('lines', false, true)
                ? $this->_images['null_only']
                : $this->_images['blank'];

            $this->_dropline[0] = false;
        }

        return $link_start .
            $this->_generateImage($img, 'treeToggle') .
            ($link_start ? '</a>' : '');
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
        return $url->add(self::TOGGLE . $this->_instance, $node_id)->link();
    }

    /**
     * Generate the icon image.
     *
     * @param string $src    The source image.
     * @param string $class  Additional class to add to image.
     * @param string $alt    Alt text to add to the image.
     *
     * @return string  A HTML tag to display the image.
     */
    protected function _generateImage($src, $class = '', $alt = null)
    {
        $img = '<img src="' . $src . '"';

        if ($class) {
            $img .= ' class="' . $class . '"';
        }

        if (!is_null($alt)) {
            $img .= ' alt="' . $alt . '"';
        }

        return $img . ' />';
    }

    /**
     * Sets the icon for the node.
     *
     * @param string $node_id  The Node ID.
     *
     * @return string  The node icon for the tree line.
     */
    protected function _setNodeIcon($node_id)
    {
        if (isset($this->_nodes[$node_id]['icon'])) {
            if (empty($this->_nodes[$node_id]['icon'])) {
                return '';
            }

            /* Node has a user defined icon. */
            if (isset($this->_nodes[$node_id]['iconopen']) &&
                $this->_nodes[$node_id]['expanded']) {
                $img = $this->_nodes[$node_id]['iconopen'];
            } else {
                $img = $this->_nodes[$node_id]['icon'];
            }
        } else {
            /* Use standard icon set. */
            if (isset($this->_nodes[$node_id]['children'])) {
                /* Node with children. */
                $img = ($this->_nodes[$node_id]['expanded'])
                    ? $this->_images['folderopen']
                    : $this->_images['folder'];
            } else {
                /* Leaf node (no children). */
                $img = $this->_images['leaf'];
            }
        }

        return $this->_generateImage($img, 'treeIcon', isset($this->_nodes[$node_id]['iconalt']) ? htmlspecialchars($this->_nodes[$node_id]['iconalt']) : null);
    }

}
