<?php
/**
 * Defines AJAX actions used exclusively in the IMP smartmobile view.
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
class IMP_Ajax_Application_Handler_Smartmobile extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Get autocomplete data.
     *
     * Variables used:
     *   - search: (string) The address search string.
     *
     * @return array  Returns an array of matched addresses (full address),
     *                which are HTML escaped.
     */
    public function smartmobileAutocomplete()
    {
        $imple = new IMP_Ajax_Imple_ContactAutoCompleter();
        return array_map('htmlspecialchars', $imple->getAddressList($this->vars->search)->addresses);
    }

    /**
     * AJAX action: Get forward compose data.
     *
     * @see IMP_Ajax_Application#getForwardData()
     */
    public function smartmobileGetForwardData()
    {
        $GLOBALS['notification']->push(_("Forwarded message will be automatically added to your outgoing message."), 'horde.message');

        return $this->_base->getForwardData();
    }

    /**
     * AJAX action: Generate data necessary to display a message.
     *
     * @see IMP_Ajax_Application#showMessage()
     *
     * @return object  Adds the following entries to the base object:
     *   - suid: (string) The search mailbox UID.
     */
    public function smartmobileShowMessage()
    {
        $output = $this->_base->showMessage();

        if (IMP_Mailbox::formFrom($this->vars->view)->search) {
            $output->suid = IMP_Ajax_Application_ListMessages::searchUid(IMP_Mailbox::formFrom($output->mbox), $output->uid);
        }

        return $output;
    }

    /**
     * AJAX action: Check access rights for creation of a submailbox.
     *
     * Variables used:
     *   - all: (integer) If 1, return all mailboxes. Otherwise, return only
     *          INBOX, special mailboxes, and polled mailboxes.
     *
     * @return string  HTML to use for the folder tree.
     */
    public function smartmobileFolderTree()
    {
        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $imptree->setIteratorFilter($this->vars->all ? 0 : Imp_Imap_Tree::FLIST_POLLED);

        return $imptree->createTree($this->vars->all ? 'smobile_folders_all' : 'smobile_folders', array(
            'poll_info' => true,
            'render_type' => 'IMP_Tree_Jquerymobile'
        ))->getTree(true);
    }

}
