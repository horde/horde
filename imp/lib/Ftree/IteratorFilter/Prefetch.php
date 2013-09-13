<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Stores/delivers pre-fetched IMP_Ftree_IteratorFilter data. Needed
 * because IMP_Ftree_IteratorFilter requires a RecursiveFilterIterator object
 * to be returned (otherwise, this could be accomplished by using something
 * simpler like an ArrayIterator instead).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ftree_IteratorFilter_Prefetch extends RecursiveFilterIterator
{
    /**
     */
    public function accept()
    {
        return true;
    }

    /**
     */
    public function getChildren()
    {
        /* This should never be reached. */
        return new self(new IMP_Ftree_IteratorFilter(array()));
    }

    /**
     */
    public function hasChildren()
    {
        return false;
    }

}
