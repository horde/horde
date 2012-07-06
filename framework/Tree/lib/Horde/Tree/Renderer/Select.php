<?php
/**
 * The Horde_Tree_Renderer_Select class provides <option> tag rendering.
 *
 * Additional node parameters:
 * - selected: (boolean) Whether the node is selected.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Ben Chavet <ben@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Tree
 */
class Horde_Tree_Renderer_Select extends Horde_Tree_Renderer_Base
{
    /**
     * Should the tree be rendered statically?
     *
     * @var boolean
     */
    protected $_static = true;

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

        $output = '<option value="' . htmlspecialchars($node_id) . '"' .
            (empty($node['selected']) ? '' : ' selected="selected"') .
            '>' .
            str_repeat('&nbsp;', intval($node['indent']) * 2) .
            htmlspecialchars($node['label']) .
            '</option>';

        if (isset($node['children']) && $node['expanded']) {
            foreach ($node['children'] as $val) {
                $output .= $this->_buildTree($val);
            }
        }

        return $output;
    }

}
