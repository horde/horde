<?php
/**
 * The Horde_Core_Tree_Renderer_Simplehtml class extends the
 * Horde_Tree_Simplehtml class to provide for creation of
 * Horde-specific URLs.
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
 * @package  Core
 */
class Horde_Core_Tree_Renderer_Simplehtml extends Horde_Tree_Renderer_Simplehtml
{
    /**
     * Generate a link URL tag.
     *
     * @param string $node_id  The node ID.
     *
     * @return string  The link tag.
     */
    protected function _generateUrlTag($node_id)
    {
        return Horde::selfUrl()
            ->add(Horde_Tree::TOGGLE . $this->_tree->instance, $node_id)
            ->link();
    }

}
