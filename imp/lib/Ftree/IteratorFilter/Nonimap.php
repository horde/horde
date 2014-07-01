<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Iterator filter for IMP_Ftree that filters non-IMAP elements.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ftree_IteratorFilter_Nonimap extends FilterIterator
{
    /**
     */
    public function accept()
    {
        $curr = $this->current();

        if (!$curr->nonimap) {
            return true;
        }

        if (($curr->remote || $curr->namespace) && $curr->children) {
            $iterator = new IMP_Ftree_IteratorFilter(
                new IMP_Ftree_Iterator($curr)
            );
            $iterator->add(array(
                $iterator::CONTAINERS,
                $iterator::NONIMAP
            ));

            foreach ($iterator as $val) {
                return true;
            }
        }

        return false;
    }

}
