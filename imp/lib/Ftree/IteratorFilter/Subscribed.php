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
 * Iterator filter for IMP_Ftree that filters by subscribed status.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ftree_IteratorFilter_Subscribed extends FilterIterator
{
    /**
     * Are IMAP subscriptions active?
     *
     * @var boolean
     */
    private $_sub;

    /**
     */
    public function __construct(Iterator $iterator)
    {
        global $injector;

        parent::__construct($iterator);

        $this->_sub = $injector->getInstance('IMP_Ftree')->subscriptions;
    }

    /**
     */
    public function accept()
    {
        return (!$this->_sub ||
                $this->current()->subscribed ||
                $this->current()->children);
    }

}
