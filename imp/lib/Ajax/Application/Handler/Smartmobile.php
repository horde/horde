<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Defines AJAX actions used exclusively in the IMP smartmobile view.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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

        return $this->_base->callAction('getForwardData');
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
        $output = $this->_base->callAction('showMessage');

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

    /**
     * AJAX action: Send message.
     *
     * @see IMP_Ajax_Application#getForwardData()
     *
     * @return string  HTML to use for the folder tree.
     */
    public function smartmobileSendMessage()
    {
        global $injector, $prefs;

        $identity = $injector->getInstance('IMP_Identity');
        $send_id = $prefs->isLocked('default_identity')
            ? null
            : $this->vars->identity;

        /* There is no sent-mail config option on smartmobile compose page,
         * so need to add that information now. */
        if ($identity->getValue('save_sent_mail', $send_id)) {
            $sent_mbox = $identity->getValue('sent_mail_folder', $send_id);
            if ($sent_mbox && !$sent_mbox->readonly) {
                $this->vars->save_sent_mail = true;
                $this->vars->save_sent_mail_mbox = $sent_mbox->form_to;
            }
        }

        return $this->_base->callAction('sendMessage');
    }

}
