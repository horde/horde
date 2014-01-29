<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Iterator filter for the IMP_Ftree object that allows filtering of specific
 * mailbox names.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ftree_IteratorFilter_Mailboxes extends FilterIterator
{
    /**
     * Mailboxes to filter.
     *
     * @var array
     */
    public $mboxes = array();

    /**
     */
    public function accept()
    {
        return !in_array(strval($this->current()), $this->mboxes);
    }

}
