<?php
/**
 * The Agora_Tree_Flat:: class extends the Horde_Tree_Renderer_Base class to provide
 * agora flat threded view.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Ben Chavet <ben@horde.org>
 * @author Duck <duck@obala.net>
 */
class Agora_Tree_Flat extends Horde_Tree_Renderer_Html
{
    /**
     * Recursive function to walk through the tree array and build the output.
     *
     * @access private
     *
     * @param string $node_id  The Node ID.
     *
     * @return string  The tree rendering.
     */
    protected function _buildTree($node_id)
    {
        $extra = $this->_nodes[$node_id]['extra'][1];
        $output = '<div class="messageContainer" style="margin-left: ' . (int)$this->_nodes[$node_id]['indent'] . '0px">' . "\n"
                . '<div class="messageAuthor">' . "\n"
                . $extra['link'] . '<strong>' . $extra['message_subject'] . '</strong></a><br />' . "\n"
                . _("Posted by") . ': ' . $extra['message_author'] . "\n<br />"
                . _("on: ") . $extra['message_date'] . "\n"
                . ' <br /> ' . "\n";

        if (isset($extra['message_author_moderator'])) {
            $output .= _("Moderator") . '<br />';
        }

        if (!empty($extra['actions'])) {
            $output .= '<span class="small"> [ ' . implode(', ', $extra['actions']) . ' ] </span>';
        }

        $output .= '</div>' . "\n"
                 . '<div class="messageBody"><p>' . $this->_nodes[$node_id]['label'] . '</p></div>' . "\n"
                 . '<br class="clear" /></div>' . "\n";

        if (isset($this->_nodes[$node_id]['children']) &&
            $this->_nodes[$node_id]['expanded']) {
            $num_subnodes = count($this->_nodes[$node_id]['children']);
            for ($c = 0; $c < $num_subnodes; $c++) {
                $child_node_id = $this->_nodes[$node_id]['children'][$c];
                $output .= $this->_buildTree($child_node_id);
            }
        }

        return $output;
    }
}
