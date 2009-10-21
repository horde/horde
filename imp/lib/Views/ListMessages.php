<?php
/**
 * Dynamic (dimp) message list logic.
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Views_ListMessages
{
    /**
     * Returns a list of messages for use with ViewPort.
     *
     * @var array $args  TODO (applyfilter, initial)
     *
     * @return array  TODO
     */
    public function listMessages($args)
    {
        $mbox = $args['mbox'];
        $is_search = false;

        $sortpref = IMP::getSort($mbox);

        /* Check for quicksearch request. */
        if (strlen($args['qsearchmbox'])) {
            /* Create the search query. */
            $query = new Horde_Imap_Client_Search_Query();

            if (strlen($args['qsearchflag'])) {
                $query->flag($args['qsearchflag'], empty($args['qsearchflagnot']));
                $is_search = true;
            } elseif (strlen($args['qsearch'])) {
                $field = $GLOBALS['prefs']->getValue('dimp_qsearch_field');
                switch ($field) {
                case 'body':
                    $query->text($args['qsearch'], true);
                    break;

                case 'from':
                case 'subject':
                    $query->headerText($field, $args['qsearch']);
                    break;

                case 'to':
                    $query2 = new Horde_Imap_Client_Search_Query();
                    $query2->headerText('cc', $args['qsearch']);

                    $query3 = new Horde_Imap_Client_Search_Query();
                    $query3->headerText('bcc', $args['qsearch']);

                    $query->headerText('to', $args['qsearch']);
                    $query->orSearch(array($query2, $query3));
                    break;

                case 'all':
                default:
                    $query->text($args['qsearch'], false);
                    break;
                }

                $is_search = true;
            }

            /* Set the search in the IMP session. */
            if ($is_search) {
                $GLOBALS['imp_search']->createSearchQuery($query, array($args['qsearchmbox']), array(), _("Search Results"), $mbox);
            }
        }

        $label = IMP::getLabel($mbox);

        /* Set the current time zone. */
        Horde_Nls::setTimeZone();

        /* Run filters now. */
        if (!$is_search &&
            !empty($_SESSION['imp']['filteravail']) &&
            !empty($args['applyfilter']) ||
            (($mbox == 'INBOX') &&
             $GLOBALS['prefs']->getValue('filter_on_display'))) {
            $imp_filter = new IMP_Filter();
            $imp_filter->filter($mbox);
        }

        /* Generate the sorted mailbox list now. */
        $imp_mailbox = IMP_Mailbox::singleton($mbox);
        $sorted_list = $imp_mailbox->getSortedList();
        $msgcount = count($sorted_list['s']);

        /* Create the base object. */
        $result = new stdClass;
        $result->view = $mbox;
        $result->totalrows = $msgcount;
        $result->label = $label;
        $result->cacheid = $imp_mailbox->getCacheID();

        /* If this is the initial request for a mailbox, figure out the
         * starting location based on user's preferences. */
        $rownum = $args['initial']
            ? intval($imp_mailbox->mailboxStart($msgcount))
            : null;

        /* Determine the row slice to process. */
        if (isset($args['search_uid']) || !is_null($rownum)) {
            if (is_null($rownum)) {
                $rownum = 1;
                foreach (array_keys($sorted_list['s'], $args['search_uid']) as $val) {
                    if (empty($sorted_list['m'][$val]) ||
                        ($sorted_list['m'][$val] == $args['search_mbox'])) {
                        $rownum = $val;
                        break;
                    }
                }
            }

            $slice_start = $rownum - $args['before'];
            $slice_end = $rownum + $args['after'];
            if ($slice_start < 1) {
                $slice_end += abs($slice_start) + 1;
            } elseif ($slice_end > $msgcount) {
                $slice_start -= $slice_end - $msgcount;
            }

            $result->rownum = $rownum;
        } else {
            $slice_start = $args['slice_start'];
            $slice_end = $args['slice_end'];
        }

        $slice_start = max(1, $slice_start);
        $slice_end = min($msgcount, $slice_end);

        /* Mail-specific viewport information. */
        $result->metadata = new stdClass;
        $md = &$result->metadata;
        if (!IMP::threadSortAvailable($mbox)) {
            $md->nothread = 1;
        }
        $md->sortby = intval($sortpref['by']);
        $md->sortdir = intval($sortpref['dir']);
        if ($sortpref['limit']) {
            $md->sortlimit = 1;
        }
        if (IMP::isSpecialFolder($mbox)) {
            $md->special = 1;
        }
        if ($is_search || $GLOBALS['imp_search']->isSearchMbox($mbox)) {
            $md->search = 1;
        }
        if ($GLOBALS['imp_imap']->isReadOnly($mbox)) {
            $md->readonly = 1;
        }

        /* Check for mailbox existence now. If there are no messages, there
         * is a chance that the mailbox doesn't exist. If there is at least
         * 1 message, we don't need this check. */
        if (empty($msgcount) && !isset($md->search)) {
            $imp_folder = IMP_Folder::singleton();
            if (!$imp_folder->exists($mbox)) {
                $GLOBALS['notification']->push(sprintf(_("Mailbox %s does not exist."), $label), 'horde.error');
            }

            $result->data = $result->rowlist = array();
            return $result;
        }

        /* Check for UIDVALIDITY expiration. It is the first element in the
         * cacheid returned from the browser. If it has changed, we need to
         * purge the cached items on the browser (send 'reset' param to
         * ViewPort). */
        if (!isset($md->search) &&
            !empty($args['cacheid']) &&
            !empty($args['cache'])) {
            $uid_expire = false;
            try {
                $status = $GLOBALS['imp_imap']->ob()->status($mbox, Horde_Imap_Client::STATUS_UIDVALIDITY);
                list($old_uidvalid,) = explode('|', $args['cacheid']);
                $uid_expire = ($old_uidvalid != $status['uidvalidity']);
            } catch (Horde_Imap_Cache_Exception $e) {
                $uid_expire = true;
            }

            if ($uid_expire) {
                $args['cache'] = array();
                $result->reset = $result->resetmd = 1;
            }
        }

        /* Get the cached list. */
        if (empty($args['cache'])) {
            $cached = array();
        } else {
            if (isset($md->search)) {
                $cached = Horde_Serialize::unserialize($args['cache'], Horde_Serialize::JSON);
            } else {
                $cached = $GLOBALS['imp_imap']->ob()->utils->fromSequenceString($args['cache']);
                $cached = reset($cached);
            }
            $cached = array_flip($cached);
        }

        /* Generate the message list and the UID -> rownumber list. */
        $data = $msglist = $rowlist = array();
        foreach (range($slice_start, $slice_end) as $key) {
            $uid = $sorted_list['s'][$key] .
                (isset($sorted_list['m'][$key])
                    ? IMP::IDX_SEP . $sorted_list['m'][$key]
                    : '');
            if ($uid) {
                $msglist[$key] = $sorted_list['s'][$key];
                $rowlist[$uid] = $key;
                if (!isset($cached[$uid])) {
                    $data[] = $key;
                }
            }
        }
        $result->rowlist = $rowlist;

        /* Build the list for rangeslice information. */
        if ($args['rangeslice']) {
            $slice = new stdClass;
            $slice->rangelist = array_keys($rowlist);
            $slice->view = $mbox;

            return $slice;
        }

        /* Build the overview list. */
        $result->data = $this->_getOverviewData($imp_mailbox, $mbox, $data, isset($md->search));

        /* Get unseen/thread information. */
        if (!isset($md->search)) {
            $imptree = IMP_Imap_Tree::singleton();
            $info = $imptree->getElementInfo($mbox);
            if (!empty($info)) {
                $md->unseen = intval($info['unseen']);
            }

            if ($sortpref['by'] == Horde_Imap_Client::SORT_THREAD) {
                $threadob = $imp_mailbox->getThreadOb();
                $imp_thread = new IMP_Imap_Thread($threadob);
                $md->thread = $imp_thread->getThreadTreeOb($msglist, $sortpref['dir']);
            }
        } else {
            $result->search = 1;
        }

        return $result;
    }

    /**
     * Obtains IMAP overview data for a given set of message UIDs.
     *
     * @param IMP_Mailbox $imp_mailbox  An IMP_Mailbox:: object.
     * @param string $folder            The current folder.
     * @param array $msglist            The list of message sequence numbers
     *                                  to process.
     * @param boolean $search           Is this a search mbox?
     *
     * @return array  TODO
     * @throws Horde_Exception
     */
    private function _getOverviewData($imp_mailbox, $folder, $msglist, $search)
    {
        $msgs = array();

        if (empty($msglist)) {
            return $msgs;
        }

        /* Get mailbox information. */
        $overview = $imp_mailbox->getMailboxArray($msglist, array('headers' => array('list-post', 'x-priority'), 'structure' => $GLOBALS['prefs']->getValue('atc_flag')));
        $charset = Horde_Nls::getCharset();
        $imp_ui = new IMP_UI_Mailbox($folder);
        $no_flags_hook = false;

        /* Display message information. */
        reset($overview['overview']);
        while (list(,$ob) = each($overview['overview'])) {
            /* Initialize the header fields. */
            $msg = array(
                'imapuid' => intval($ob['uid']),
                'menutype' => 'message',
                'view' => $ob['mailbox'],
            );

            /* Get all the flag information. */
            if (!$no_flags_hook) {
                try {
                    $ob['flags'] = array_merge($ob['flags'], Horde::callHook('msglist_flags', array($ob, 'dimp'), 'imp'));
                } catch (Horde_Exception_HookNotSet $e) {
                    $no_flags_hook = true;
                }
            }

            $imp_flags = IMP_Imap_Flags::singleton();
            $flag_parse = $imp_flags->parse(array(
                'atc' => isset($ob['structure']) ? $ob['structure'] : null,
                'flags' => $ob['flags'],
                'personal' => Horde_Mime_Address::getAddressesFromObject($ob['envelope']['to']),
                'priority' => $ob['headers']->getValue('x-priority')
            ));

            if (!empty($flag_parse)) {
                $msg['flag'] = array();
                foreach ($flag_parse as $val) {
                    $msg['flag'][] = $val['flag'];
                }
            }

            /* Specific flag checking. */
            if (in_array('\\draft', $ob['flags'])) {
                $msg['menutype'] = 'draft';
                $msg['draft'] = 1;
            }

            /* Format size information. */
            $msg['size'] = htmlspecialchars($imp_ui->getSize($ob['size']), ENT_QUOTES, $charset);

            /* Format the Date: Header. */
            $msg['date'] = htmlspecialchars($imp_ui->getDate($ob['envelope']['date']), ENT_QUOTES, $charset);

            /* Format the From: Header. */
            $getfrom = $imp_ui->getFrom($ob['envelope'], array('specialchars' => $charset));
            $msg['from'] = $getfrom['from'];

            /* Format the Subject: Header. */
            $msg['subject'] = $imp_ui->getSubject($ob['envelope']['subject'], true);

            /* Check to see if this is a list message. Namely, we want to
             * check for 'List-Post' information because that is the header
             * that gives the e-mail address to reply to, which is all we
             * care about. */
            if ($ob['headers']->getValue('list-post')) {
                $msg['listmsg'] = 1;
            }

            /* Need both UID and mailbox to create a unique ID string if
             * using a search mailbox.  Otherwise, use only the UID. */
            if ($search) {
                $msgs[$ob['uid'] . IMP::IDX_SEP . $ob['mailbox']] = $msg;
            } else {
                $msgs[$ob['uid']] = $msg;
            }
        }

        /* Allow user to alter template array. */
        try {
            $msgs = Horde::callHook('mailboxarray', array($msgs, 'dimp'), 'imp');
        } catch (Horde_Exception_HookNotSet $e) {}

        return $msgs;
    }

}
