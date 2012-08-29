<?php
/**
 * This class defines Jquerymobile output for a mailbox (folder tree) list.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 GPL
 * @package  IMP
 */
class IMP_Tree_Jquerymobile extends Horde_Tree_Renderer_Jquerymobile
{
    /**
     */
    protected function _buildTree($node_id, $special)
    {
        $node = &$this->_nodes[$node_id];
        $output = '';

        if (empty($node['container'])) {
            $output = parent::_buildTree($node_id, $special);
        } elseif (!empty($node['children'])) {
            foreach ($node['children'] as $val) {
                $output .= $this->_buildTree($val, $special);
            }
        }

        return $output;
    }
}
