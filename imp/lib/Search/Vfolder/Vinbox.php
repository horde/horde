<?php
/**
 * This class provides a data structure for storing the virtual inbox.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
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
            $imp_tree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
            $imp_tree->prunePollList();
            return $imp_tree->getPollList(true);
        }

        return parent::__get($name);
    }

}
