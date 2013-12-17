<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Data structure for storing the virtual inbox.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Search_Vfolder_Vinbox extends IMP_Search_Vfolder_Builtin
{
    /**
     * Initialization tasks.
     */
    protected function _init()
    {
        $this->_id = 'vinbox';
        $this->_label = _("Virtual Inbox");

        $this->add(new IMP_Search_Element_Flag(
            Horde_Imap_Client::FLAG_SEEN,
            false
        ));
        $this->add(new IMP_Search_Element_Flag(
            Horde_Imap_Client::FLAG_DELETED,
            false
        ));
    }

    /**
     * Get object properties.
     * Only create mailbox list on demand.
     *
     * @see __get()
     */
    public function __get($name)
    {
        switch ($name) {
        case 'mboxes':
            $poll = $GLOBALS['injector']->getInstance('IMP_Ftree')->poll;
            $poll->prunePollList();
            return $poll->getPollList(true);
        }

        return parent::__get($name);
    }

}
