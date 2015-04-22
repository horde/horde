<?php
/**
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2011-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * This class defines Jquerymobile output for a mailbox (folder tree) list.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Tree_Jquerymobile extends Horde_Tree_Renderer_Base
{
    /**
     * Special mailbox flag.
     *
     * @var boolean
     */
    protected $_isSpecial = false;

    /**
     */
    public function getTree($static = false)
    {
        $this->_nodes = $this->_tree->getNodes();

        $tree = '';
        foreach (array(true, false) as $special) {
            $this->_isSpecial = $special;
            foreach ($this->_tree->getRootNodes() as $node_id) {
                $tree .= $this->_buildTree($node_id);
            }
        }

        return $tree;
    }

    /**
     */
    protected function _buildTree($node_id)
    {
        $node = $this->_nodes[$node_id];
        $output = '';

        if (empty($node['container']) &&
            ($node['special'] == $this->_isSpecial)) {
            $output = $this->_buildTreeNode($node);
        }

        if (!empty($node['children'])) {
            foreach ($node['children'] as $val) {
                $output .= $this->_buildTree($val);
            }
        }

        return $output;
    }

    /**
     */
    protected function _buildTreeNode($node)
    {
        $output = '<li' .
            (isset($node['class']) ? (' class="' . $node['class'] . '"') : '') .
            '>';

        if (isset($this->_extra[$node['id']][Horde_Tree_Renderer::EXTRA_LEFT])) {
            $output .= implode(
                ' ',
                $this->_extra[$node['id']][Horde_Tree_Renderer::EXTRA_LEFT]
            );
        }

        if (!empty($node['url'])) {
            $output .= '<a href="' . strval($node['url']) . '"';
            if (isset($node['urlattributes'])) {
                foreach ($node['urlattributes'] as $key => $val) {
                    $output .= ' ' . $key . '="' . htmlspecialchars($val) . '"';
                }
            }
            $output .= '>';
        }

        if (!empty($node['icon'])) {
            $output .= '<img src="' . $node['icon'] . '" class="ui-li-icon" />';
        }

        $output .= $node['label'];
        if (!empty($node['url'])) {
            $output .= '</a>';
        }

        if (isset($this->_extra[$node['id']][Horde_Tree_Renderer::EXTRA_RIGHT])) {
            $output .= '<span class="ui-li-count">' .
                implode(' ', $this->_extra[$node['id']][Horde_Tree_Renderer::EXTRA_RIGHT]) .
                '</span>';
        }

        return $output . '</li>';
    }

}
