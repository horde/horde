<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Defines AJAX actions used exclusively in the IMP smartmobile view.
 *
 * Global tasks:
     - flag_config: (boolean) True if flag information is needed.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Application_Handler_Smartmobile
extends Horde_Core_Ajax_Application_Handler
{
    /**
     */
    public function __construct(Horde_Core_Ajax_Application $base)
    {
        parent::__construct($base);

        /* Disable quota - not used in smartmobile for now. */
        $base->queue->quota(null);

        /* Disable implicit polling - not used in smartmobile for now. */
        $base->queue->poll(null);

        if ($this->vars->flag_config) {
            $base->queue->flagConfig(Horde_Registry::VIEW_SMARTMOBILE);
        }
    }

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
        return array_map('htmlspecialchars', $imple->getAddressList($this->vars->search)->base_addresses);
    }

    /**
     * AJAX action: Get forward compose data.
     *
     * @see IMP_Ajax_Application#getForwardData()
     */
    public function smartmobileGetForwardData()
    {
        $result = $this->_base->callAction('getForwardData');

        if ($result && $result->opts->attach) {
            $GLOBALS['notification']->push(_("Forwarded message will be automatically added to your outgoing message."), 'horde.message');
        }

        return $result;
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
        $ftree = $GLOBALS['injector']->getInstance('IMP_Ftree');

        /* Poll all mailboxes on initial display. */
        $this->_base->queue->poll($ftree->poll->getPollList(), true);

        $iterator = new AppendIterator();

        /* Add special mailboxes explicitly. INBOX should appear first. */
        $special = new ArrayIterator();
        $special->append($ftree['INBOX']);
        foreach (IMP_Mailbox::getSpecialMailboxesSort() as $val) {
            if (isset($ftree[$val])) {
                $special->append($ftree[$val]);
            }
        }
        $iterator->append($special);

        /* Now add polled mailboxes. */
        $filter = new IMP_Ftree_IteratorFilter($ftree);
        $filter->add(array(
            $filter::CONTAINERS,
            $filter::REMOTE,
            $filter::SPECIALMBOXES
        ));
        if (!$this->vars->all) {
            $filter->add($filter::POLLED);
        }
        $filter->mboxes = array('INBOX');
        $iterator->append($filter);

        return $ftree->createTree($this->vars->all ? 'smobile_folders_all' : 'smobile_folders', array(
            'iterator' => $iterator,
            'render_type' => 'IMP_Tree_Jquerymobile'
        ))->getTree(true);
    }

    /**
     * Return the copy/move selection list.
     *
     * Variables used: NONE
     *
     * @return string  HTML to use for the folder tree.
     */
    public function copyMoveMailboxList()
    {
        $iterator = new IMP_Ftree_IteratorFilter($GLOBALS['injector']->getInstance('IMP_Ftree'));
        $iterator->add($iterator::REMOTE);

        return strval(new IMP_Ftree_Select(array(
            'heading' => _("This message to"),
            'iterator' => $iterator,
            'optgroup' => true,
            'inc_tasklists' => true,
            'inc_notepads' => true,
            'new_mbox' => true
        )));
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
            $sent_mbox = $identity->getValue(IMP_Mailbox::MBOX_SENT, $send_id);
            if ($sent_mbox && !$sent_mbox->readonly) {
                $this->vars->save_sent_mail = true;
                $this->vars->save_sent_mail_mbox = $sent_mbox->form_to;
            }
        }

        return $this->_base->callAction('sendMessage');
    }

}
