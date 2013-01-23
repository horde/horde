<?php
/**
 * Mailbox page for minimal view.
 *
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Minimal_Mailbox extends IMP_Minimal_Base
{
    /**
     * URL Parameters:
     *   - a: (string) Action ID.
     *   - checkbox: TODO
     *   - mt: TODO
     *   - p: (integer) Page.
     *   - search: (sring) The search string
     *   - start: (integer) Start.
     */
    protected function _init()
    {
        global $injector, $notification, $prefs;

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
        $imp_search = $injector->getInstance('IMP_Search');

        /* Determine if mailbox is readonly. */
        $readonly = $this->indices->mailbox->readonly;

        /* Get the base URL for this page. */
        $mailbox_url = self::url(array('mailbox' => $this->indices->mailbox));

        /* Perform message actions (via advanced UI). */
        switch ($this->vars->checkbox) {
        // 'd' = delete message
        // 'u' = undelete message
        case 'd':
        case 'u':
            $imp_message = $injector->getInstance('IMP_Message');

            if ($this->vars->checkbox == 'd') {
                try {
                    $injector->getInstance('Horde_Token')->validate($this->vars->mt, 'imp.message-mimp');
                    $imp_message->delete($this->indices);
                } catch (Horde_Token_Exception $e) {
                    $notification->push($e);
                }
            } else {
                $imp_message->undelete($this->indices);
            }
            break;

        // 'rs' = report spam
        // 'ri' = report innocent
        case 'rs':
        case 'ri':
            IMP_Spam::reportSpam($this->indices, $this->vars->checkbox == 'rs' ? 'spam' : 'notspam');
            break;
        }

        /* Run through the action handlers. */
        switch ($this->vars->a) {
        // 'm' = message missing
        case 'm':
            $notification->push(_("There was an error viewing the requested message."), 'horde.error');
            break;

        // 'e' = expunge mailbox
        case 'e':
            $injector->getInstance('IMP_Message')->expungeMailbox(array(strval($this->indices->mailbox) => 1));
            break;

        // 'ds' = do search
        case 'ds':
            if (!empty($this->vars->search) &&
                $imp_imap->access(IMP_Imap::ACCESS_SEARCH)) {
                /* Create the search query and reset the global mailbox
                 * variable. */
                $q_ob = $imp_search->createQuery(array(new IMP_Search_Element_Text($this->vars->search, false)), array(
                    'mboxes' => array($this->indices->mailbox)
                ));

                /* Need to re-calculate these values. */
                $this->indices->mailbox = IMP_Mailbox::get($q_ob);
                $readonly = $this->indices->mailbox->readonly;
                $mailbox_url = self::url(array('mailbox' => $this->indices->mailbox));
            }
            break;
        }

        /* Build the list of messages in the mailbox. */
        $imp_mailbox = $this->indices->mailbox->list_ob;
        $pageOb = $imp_mailbox->buildMailboxPage($this->vars->p, $this->vars->start);

        /* Generate page title. */
        $this->title = $this->indices->mailbox->display;

        /* Modify title for display on page. */
        if ($pageOb['msgcount']) {
            $this->title .= ' (';
            if ($imp_imap->access(IMP_Imap::ACCESS_UNSEEN)) {
                $unseen = $imp_mailbox->unseenMessages(Horde_Imap_Client::SEARCH_RESULTS_COUNT);
                $this->title .= sprintf(_("%d unseen"), $unseen) . '/';
            }
            $this->title .= sprintf(_("%d total"), $pageOb['msgcount']) . ')';
        }
        if ($pageOb['pagecount'] > 1) {
            $this->title .= ' - ' . sprintf(_("%d of %d"), $pageOb['page'], $pageOb['pagecount']);
        }
        if ($readonly) {
            $this->title .= ' [' . _("Read-Only") . ']';
        }
        $this->view->title = $this->title;

        /* Build the array of message information. */
        $imp_ui = new IMP_Mailbox_Ui($this->indices->mailbox);
        $mbox_info = $imp_mailbox->getMailboxArray(range($pageOb['begin'], $pageOb['end']), array('headers' => true));
        $msgs = array();

        while (list(,$ob) = each($mbox_info['overview'])) {
            /* Initialize the header fields. */
            $msg = array(
                'buid' => $imp_mailbox->getBuid($ob['mailbox'], $ob['uid']),
                'status' => '',
                'subject' => trim($imp_ui->getSubject($ob['envelope']->subject))
            );

            /* Format the from header. */
            $getfrom = $imp_ui->getFrom($ob['envelope']);
            $msg['from'] = $getfrom['from'];

            /* Get flag information. */
            $flag_parse = $injector->getInstance('IMP_Flags')->parse(array(
                'flags' => $ob['flags'],
                'headers' => $ob['headers'],
                'personal' => $ob['envelope']->to
            ));

            foreach ($flag_parse as $val) {
                if ($abbrev = $val->abbreviation) {
                    $msg['status'] .= $abbrev;
                } elseif ($val instanceof IMP_Flag_User) {
                    $msg['subject'] = '*' . Horde_String::truncate($val->label, 8) . '* ' . $msg['subject'];
                }
            }

            /* Generate the target link. */
            if ($this->indices->mailbox->templates) {
                $compose = 't';
            } elseif ($this->indices->mailbox->draft ||
                      in_array(Horde_Imap_Client::FLAG_DRAFT, $ob['flags'])) {
                $compose = 'd';
            } else {
                $msg['target'] = IMP_Minimal_Message::url(array(
                    'buid' => $msg['buid'],
                    'mailbox' => $this->indices->mailbox
                ));
            }

            if (!isset($msg['target'])) {
                $msg['target'] = IMP::composeLink(array(), array(
                    'a' => $compose,
                    'buid' => $msg['buid'],
                    'bodypart' => 1,
                    'mailbox' => $this->indices->mailbox
                ));
            }

            $msgs[] = $msg;
        }
        $this->view->msgs = $msgs;

        $mailbox = $mailbox_url->copy()->add('p', $pageOb['page']);
        $menu = array(array(_("Refresh"), $mailbox));
        $search_mbox = $this->indices->mailbox->search;

        /* Determine if we are going to show the Purge Deleted link. */
        if (!$prefs->getValue('use_trash') &&
            !$this->indices->mailbox->vinbox &&
            $this->indices->mailbox->access_expunge) {
            $menu[] = array(_("Purge Deleted"), $mailbox->copy()->add('a', 'e'));
        }

        /* Add search link. */
        if ($imp_imap->access(IMP_Imap::ACCESS_SEARCH)) {
            if ($search_mbox) {
                $mboxes = $imp_search[strval($this->indices->mailbox)]->mboxes;
                $orig_mbox = IMP_Mailbox::get(reset($mboxes));
                $menu[] = array(sprintf(_("New Search in %s"), $orig_mbox->label), IMP_Minimal_Search::url(array('mailbox' => $orig_mbox)));
            } else {
                $menu[] = array(_("Search"), IMP_Minimal_Search::url(array('mailbox' => $this->indices->mailbox)));
            }
        }

        /* Generate page links. */
        if ($pageOb['page'] != 1) {
            $menu[] = array(_("First Page"), $mailbox_url->copy()->add('p', 1));
            $menu[] = array(_("Previous Page"), $mailbox_url->copy()->add('p', $pageOb['page'] - 1));
        }
        if ($pageOb['page'] != $pageOb['pagecount']) {
            $menu[] = array(_("Next Page"), $mailbox_url->copy()->add('p', $pageOb['page'] + 1));
            $menu[] = array(_("Last Page"), $mailbox_url->copy()->add('p', $pageOb['pagecount']));
        }

        $this->view->menu = $this->getMenu('mailbox', $menu);

        /* Activate advanced checkbox UI? */
        try {
            if (Horde::callHook('mimp_advanced', array('checkbox'), 'imp')) {
                $this->view->checkbox = $mailbox_url->copy()->add('p', $pageOb['page']);
                $this->view->delete = $this->indices->mailbox->access_deletemsgs;
                $this->view->mt = $injector->getInstance('Horde_Token')->get('imp.message-mimp');
            }
        } catch (Horde_Exception_HookNotSet $e) {}

        $this->_pages[] = 'mailbox';
        $this->_pages[] = 'menu';
    }

    /**
     * @param array $opts  Options:
     *   - mailbox: (string) The mailbox to link to.
     */
    static public function url(array $opts = array())
    {
        $opts = array_merge(array('mailbox' => 'INBOX'), $opts);

        return IMP_Mailbox::get($opts['mailbox'])->url('minimal.php')->add('page', 'mailbox');
    }

}
