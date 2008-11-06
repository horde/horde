<?php
/**
 * The IMP_Filter:: class contains all functions related to handling
 * filtering messages in IMP.
 *
 * For full use, the following Horde API calls should be defined
 * (These API methods are not defined in IMP):
 *   mail/applyFilters
 *   mail/canApplyFilters
 *   mail/showFilters
 *   mail/blacklistFrom
 *   mail/showBlacklist
 *   mail/whitelistFrom
 *   mail/showWhitelist
 *
 * $Horde: imp/lib/Filter.php,v 1.80 2008/10/13 20:51:51 slusarz Exp $
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Filter {

    /**
     * Runs the filters if they are able to be applied manually.
     *
     * @param string $mbox  The mailbox to apply the filters to.
     */
    function filter($mbox)
    {
        if (!empty($_SESSION['imp']['filteravail'])) {
            if (isset($GLOBALS['imp_search']) &&
                $GLOBALS['imp_search']->isSearchMbox($mbox)) {
                $mbox_list = $GLOBALS['imp_search']->getSearchFolders($mbox);
            } else {
                $mbox_list = array($mbox);
            }

            foreach ($mbox_list as $val) {
                // @todo ingo needs to be rewritten to use non-stream access
                $GLOBALS['registry']->call('mail/applyFilters', array(array('mailbox' => $val)));
            }
        }
    }

    /**
     * Adds the From address from the message(s) to the blacklist and deletes
     * the message(s).
     *
     * @param array $indices      See IMP::parseIndicesList().
     * @param boolean $show_link  Show link to the blacklist management in the
     *                            notification message?
     *
     * @return boolean  True if the messages(s) were delete.
     */
    function blacklistMessage($indices, $show_link = true)
    {
        $this->_processBWlist($indices, _("your blacklist"), 'blacklistFrom', 'showBlacklist', $show_link);

        $imp_message = &IMP_Message::singleton();
        $msg_count = $imp_message->delete($indices);
        if ($msg_count) {
            if ($msg_count == 1) {
                $GLOBALS['notification']->push(_("The message has been deleted."), 'horde.message');
            } else {
                $GLOBALS['notification']->push(_("The messages have been deleted."), 'horde.message');
            }
            return true;
        }

        return false;
    }

    /**
     * Adds the From address from the message(s) to the whitelist.
     *
     * @param array $indices      See IMP::parseIndicesList().
     * @param boolean $show_link  Show link to the whitelist management in the
     *                            notification message?
     */
    function whitelistMessage($indices, $show_link = true)
    {
        $this->_processBWlist($indices, _("your whitelist"), 'whitelistFrom', 'showWhitelist', $show_link);
    }

    /**
     * Internal function to handle adding addresses to [black|white]list.
     *
     * @access private
     *
     * @param array  $indices  See IMP::parseIndicesList().
     * @param string $descrip  The textual description to use.
     * @param string $reg1     The name of the mail/ registry call to use for
     *                         adding the addresses.
     * @param string $reg2     The name of the mail/ registry call to use for
     *                         linking to the filter management page.
     * @param boolean link     Show link to the whitelist management in the
     *                         notification message?
     */
    function _processBWlist($indices, $descrip, $reg1, $reg2, $link)
    {
        if (!($msgList = IMP::parseIndicesList($indices))) {
            return false;
        }

        /* Get the list of from addresses. */
        // TODO
        $addr = array();
        foreach ($msgList as $folder => $msgIndices) {
            $ob = $msg_cache->retrieve($folder, $msgIndices, 32);
            foreach ($msgIndices as $msg) {
                $addr[] = $ob[$msg]->header->getFromAddress();
            }
        }

        $GLOBALS['registry']->call('mail/' . $reg1, array($addr));

        /* Add link to filter management page. */
        if ($link && $GLOBALS['registry']->hasMethod('mail/' . $reg2)) {
            $manage_link = Horde::link(Horde::url($GLOBALS['registry']->link('mail/' . $reg2)), sprintf(_("Filters: %s management page"), $descrip)) . _("HERE") . '</a>';
            $GLOBALS['notification']->push(sprintf(_("Click %s to go to %s management page."), $manage_link, $descrip), 'horde.message', array('content.raw'));
        }
    }

}
