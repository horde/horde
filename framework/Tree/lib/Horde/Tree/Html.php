<?php
/**
 * The Horde_Tree_Html:: class provides HTML specific rendering functions.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Marko Djukic <marko@oblo.com>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Tree
 */
class Horde_Tree_Html extends Horde_Tree_Base
{
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
    protected $_altCount = 0;

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
        'join_top' => null,
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
     *             DEFAULT: false
     * class - (string) The class to use for the table.
     *         DEFAULT: ''
     * hideHeaders - (boolean) Don't render any HTML for the header row, just
     *               use the widths.
     *               DEFAULT: false
     * lines - (boolean) Show tree lines?
     *         DEFAULT: true
     * lines_base - (boolean) Show tree lines for the base level? Requires
     *              'lines' to be true also.
     *              DEFAULT: false
     * multiline - (boolean) Do the node labels contain linebreaks?
     *             DEFAULT: false
     * </pre>
     */
    public function __construct($name, array $params = array())
    {
        $params = array_merge(array(
            'lines' => true
        ), $params);

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
     * class - The CSS class of the header cell
     * html - The HTML content of the header cell
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
        if (!count($this->_header) ||
            $this->getOption('hideHeaders')) {
            return '';
        }

        $className = 'treeRowHeader';

        /* If using alternating row shading, work out correct
         * shade. */
        if ($this->getOption('alternate')) {
            $className .= ' item' . $this->_altCount;
            $this->_altCount = 1 - $this->_altCount;
        }

        $html = '<div class="' . $className . '">';

        foreach ($this->_header as $header) {
            $html .= '<span';
            if (!empty($header['class'])) {
                $html .= ' class="' . $header['class'] . '"';
            }

            $html .= '>' .
                (empty($header['html']) ? '&nbsp;' : $header['html'])
                . '</span>';
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
        $node = $this->_nodes[$node_id];
        $output = $this->_buildLine($node_id);

        if (isset($node['children']) && $node['expanded']) {
            foreach ($node['children'] as $key => $val) {
                $child_node_id = $node['children'][$key];
                $this->_node_pos[$child_node_id] = array(
                    'count' => count($node['children']),
                    'pos' => $key + 1
                );
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
        $node = $this->_nodes[$node_id];

        $className = 'treeRow';
        if (!empty($node['class'])) {
            $className .= ' ' . $node['class'];
        }

        /* If using alternating row shading, work out correct
         * shade. */
        if ($this->getOption('alternate')) {
            $className .= ' item' . $this->_altCount;
            $this->_altCount = 1 - $this->_altCount;
        }

        $line = '<div class="' . $className . '">';

        /* If we have headers, track which logical "column" we're in
         * for any given cell of content. */
        $column = 0;

        if (isset($node['extra'][Horde_Tree::EXTRA_LEFT])) {
            $extra = $node['extra'][Horde_Tree::EXTRA_LEFT];
            $cMax = count($extra);
            while ($column < $cMax) {
                $line .= $this->_addColumn($column) . $extra[$column] . '</span>';
                ++$column;
            }
        }

        $line .= $this->_addColumn($column++);

        if ($this->getOption('multiline')) {
            $line .= '<table cellspacing="0"><tr><td>';
        }

        for ($i = intval($this->_static); $i < $node['indent']; ++$i) {
            $line .= $this->_generateImage(($this->_dropline[$i] && $this->getOption('lines')) ? $this->_images['line'] : $this->_images['blank']);
        }
        $line .= $this->_setNodeToggle($node_id) . $this->_setNodeIcon($node_id);
        if ($this->getOption('multiline')) {
            $line .= '</td><td>';
        }
        $line .= $this->_setLabel($node_id);

        if ($this->getOption('multiline')) {
            $line .= '</td></tr></table>';
        }

        $line .= '</span>';

        if (isset($node['extra'][Horde_Tree::EXTRA_RIGHT])) {
            $extra = $node['extra'][Horde_Tree::EXTRA_RIGHT];
            $cMax = count($extra);
            for ($c = 0, $cMax = count($extra); $c < $cMax; ++$c) {
                $line .= $this->_addColumn($column++) . $extra[$c] . '</span>';
            }
        }

        return $line . "</div>\n";
    }

    /**
     */
    protected function _addColumn($column)
    {
        $line = '<span';
        if (isset($this->_header[$column]['class'])) {
            $line .= ' class="' . $this->_header[$column]['class'] . '"';
        }
        return $line . '>';
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
        $node = $this->_nodes[$node_id];

        /* Top level node. */
        if ($node['indent'] == 0) {
            $this->_dropline[0] = false;

            if ($this->_static) {
                return '';
            }

            /* KEY:
             * 0: Only node
             * 1: Top node
             * 2: Middle node
             * 3: Bottom node */
            $node_type = 0;
            if ($this->getOption('lines_base') &&
                (count($this->_root_nodes) > 1)) {
                switch (array_search($node_id, $this->_root_nodes)) {
                case 0:
                    $node_type = 1;
                    $this->_dropline[0] = true;
                    break;

                case (count($this->_root_nodes) - 1):
                    $node_type = 3;
                    break;

                default:
                    $node_type = 2;
                    $this->_dropline[0] = true;
                    break;
                }
            }

            if (isset($node['children'])) {
                if (!$this->getOption('lines')) {
                    $img = $this->_images['blank'];
                } elseif ($node['expanded']) {
                    $img = $node_type
                        ? (($node_type == 2) ? $this->_images['minus'] : $this->_images['minus_bottom'])
                        : $this->_images['minus_only'];
                } else {
                    $img = $node_type
                        ? (($node_type == 2) ? $this->_images['plus'] : $this->_images['plus_bottom'])
                        : $this->_images['plus_only'];
                }

                $link_start = $this->_generateUrlTag($node_id);
            } else {
                if ($this->getOption('lines')) {
                    switch ($node_type) {
                    case 0:
                        $img = $this->_images['null_only'];
                        break;

                    case 1:
                        $img = $this->_images['join_top'];
                        break;

                    case 2:
                        $img = $this->_images['join'];
                        break;

                    case 3:
                        $img = $this->_images['join_bottom'];
                        break;
                    }
                } else {
                    $img = $this->_images['blank'];
                }
            }
        } elseif (isset($node['children'])) {
            /* Node with children. */
            if ($this->_node_pos[$node_id]['pos'] < $this->_node_pos[$node_id]['count']) {
                /* Not last node. */
                if (!$this->getOption('lines')) {
                    $img = $this->_images['blank'];
                } elseif ($this->_static) {
                    $img = $this->_images['join'];
                } elseif ($node['expanded']) {
                    $img = $this->_images['minus'];
                } else {
                    $img = $this->_images['plus'];
                }
                $this->_dropline[$node['indent']] = true;
            } else {
                /* Last node. */
                if (!$this->getOption('lines')) {
                    $img = $this->_images['blank'];
                } elseif ($this->_static) {
                    $img = $this->_images['join_bottom'];
                } elseif ($node['expanded']) {
                    $img = $this->_images['minus_bottom'];
                } else {
                    $img = $this->_images['plus_bottom'];
                }
                $this->_dropline[$node['indent']] = false;
            }

            if (!$this->_static) {
                $link_start = $this->_generateUrlTag($node_id);
            }
        } else {
            /* Node without children. */
            if ($this->_node_pos[$node_id]['pos'] < $this->_node_pos[$node_id]['count']) {
                /* Not last node. */
                $img = $this->getOption('lines')
                    ? $this->_images['join']
                    : $this->_images['blank'];

                $this->_dropline[$node['indent']] = true;
            } else {
                /* Last node. */
                $img = $this->getOption('lines')
                    ? $this->_images['join_bottom']
                    : $this->_images['blank'];

                $this->_dropline[$node['indent']] = false;
            }
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
        return $url->add(Horde_Tree::TOGGLE . $this->_instance, $node_id)->link();
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
        $node = $this->_nodes[$node_id];

        if (isset($node['icon'])) {
            if (empty($node['icon'])) {
                return '';
            }

            /* Node has a user defined icon. */
            $img = (isset($node['iconopen']) && $node['expanded'])
                ? $node['iconopen']
                : $node['icon'];
        } elseif (isset($node['children'])) {
            /* Standard icon set: node with children. */
            $img = $node['expanded']
                ? $this->_images['folderopen']
                : $this->_images['folder'];
        } else {
            /* Standard icon set: leaf node (no children). */
            $img = $this->_images['leaf'];
        }

        return $this->_generateImage($img, 'treeIcon', isset($node['iconalt']) ? htmlspecialchars($node['iconalt']) : null);
    }

}
