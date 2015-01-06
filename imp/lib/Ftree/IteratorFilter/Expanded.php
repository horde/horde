<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Iterator filter for IMP_Ftree that filters unexpanded elements.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ftree_IteratorFilter_Expanded extends RecursiveFilterIterator
{
    /**
     */
    public function accept()
    {
        return true;
    }

    /**
     */
    public function hasChildren()
    {
        return $this->current()->open;
    }

}
