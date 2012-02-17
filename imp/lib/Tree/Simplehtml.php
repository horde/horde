<?php
/**
 * Extends Core's Simplehtml class to allow us to catch expand/collapse
 * requests so that the 'expanded_folders' pref can be updated.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Tree_Simplehtml extends Horde_Core_Tree_Simplehtml
{
    /**
     * Should this element be toggled?
     *
     * @param string $node_id  The node ID.
     *
     * @return boolean  True of the element should be toggled.
     */
    public function shouldToggle($id)
    {
        return ($this->_nodeId($id) == Horde_Util::getFormData(Horde_Tree::TOGGLE . $this->_instance));
    }

}
