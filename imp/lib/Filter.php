<?php
/**
 * The IMP_Filter:: class contains all functions related to handling
 * filtering messages in IMP.
 *
 * For full use, the following Horde API calls should be defined
 * (These API methods are not defined in IMP):
 *   - mail/applyFilters
 *   - mail/canApplyFilters
 *   - mail/showFilters
 *   - mail/blacklistFrom
 *   - mail/showBlacklist
 *   - mail/whitelistFrom
 *   - mail/showWhitelist
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Filter
{
    /**
     * Runs the filters if they are able to be applied manually.
     *
     * @param string $mbox  The mailbox to apply the filters to.
     * @throws Horde_Exception
     */
    public function filter($mbox)
    {
        if (!$GLOBALS['session']->get('imp', 'filteravail')) {
            return;
        }

        $imp_search = $GLOBALS['injector']->getInstance('IMP_Search');
        $mbox_list = $imp_search->isSearchMbox($mbox)
            ? $imp_search[$mbox]->mboxes
            : array($mbox);

        foreach ($mbox_list as $val) {
            $GLOBALS['registry']->call('mail/applyFilters', array(array('mailbox' => strval($val))));
        }
    }

    /**
     * Adds the From address from the message(s) to the blacklist and deletes
     * the message(s).
     *
     * @param IMP_Indices $indices  An indices object.
     * @param boolean $show_link    Show link to the blacklist management in
     *                              the notification message?
     *
     * @return boolean  True if the messages(s) were deleted.
     * @throws Horde_Exception
     */
    public function blacklistMessage($indices, $show_link = true)
    {
        if (!$this->_processBWlist($indices, _("your blacklist"), 'blacklistFrom', 'showBlacklist', $show_link) ||
            !($msg_count = $GLOBALS['injector']->getInstance('IMP_Message')->delete($indices))) {
            return false;
        }

        $GLOBALS['notification']->push(ngettext(_("The message has been deleted."), _("The messages have been deleted."), $msg_count), 'horde.message');

        return true;
    }

    /**
     * Adds the From address from the message(s) to the whitelist.
     *
     * @param IMP_Indices $indices  An indices object.
     * @param boolean $show_link    Show link to the whitelist management in
     *                              the notification message?
     *
     * @return boolean  True if the messages(s) were whitelisted.
     * @throws Horde_Exception
     */
    public function whitelistMessage($indices, $show_link = true)
    {
        return $this->_processBWlist($indices, _("your whitelist"), 'whitelistFrom', 'showWhitelist', $show_link);
    }

    /**
     * Internal function to handle adding addresses to [black|white]list.
     *
     * @param IMP_Indices $indices  An indices object.
     * @param string $descrip       The textual description to use.
     * @param string $reg1          The name of the mail/ registry call to use
     *                              for adding the addresses.
     * @param string $reg2          The name of the mail/ registry call to use
     *                              for linking to the filter management page.
     * @param boolean $link         Show link to the whitelist management in
     *                              the notification message?
     *
     * @return boolean  True on success.
     * @throws IMP_Exception
     */
    protected function _processBWlist($indices, $descrip, $reg1, $reg2, $link)
    {
        if (!count($indices)) {
            return false;
        }

        $addr = array();
        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();

        foreach ($indices as $ob) {
            $ob->mbox->uidvalid;

            foreach ($ob->uids as $idx) {
                /* Get the list of from addresses. */
                $addr[] = IMP::bareAddress($GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create($ob->mbox->getIndicesOb($idx))->getHeader()->getValue('from'));
            }
        }

        $GLOBALS['registry']->call('mail/' . $reg1, array($addr));

        /* Add link to filter management page. */
        if ($link && $GLOBALS['registry']->hasMethod('mail/' . $reg2)) {
            $manage_link = Horde::link(Horde::url($GLOBALS['registry']->link('mail/' . $reg2)), sprintf(_("Filters: %s management page"), $descrip)) . _("HERE") . '</a>';
            $GLOBALS['notification']->push(sprintf(_("Click %s to go to %s management page."), $manage_link, $descrip), 'horde.message', array('content.raw'));
        }

        return true;
    }

}
