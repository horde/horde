<?php
/**
 * The Horde_Core_Tree_Simplehtml:: class extends the Horde_Tree_Simplehtml
 * class to provide for creation of Horde-specific URLs.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Core_Tree_Simplehtml extends Horde_Tree_Simplehtml
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
        return Horde::link(Horde::selfUrl()->add(Horde_Tree::TOGGLE . $this->_instance, $node_id));
    }

}
