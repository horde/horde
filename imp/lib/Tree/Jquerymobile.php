<?php
/**
 * Copyright 2011-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2011-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * This class defines Jquerymobile output for a mailbox (folder tree) list.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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
