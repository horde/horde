<?php
/**
 * Dynamic (dimp) message list logic.
 *
 * Copyright 2005-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Views_ListMessages
{
    /**
     * Does the flags hook exist?
     *
     * @var boolean
     */
    protected $_flaghook = true;

    /**
     * Returns a list of messages for use with ViewPort.
     *
     * @var array $args  TODO
     *   - applyfilter: (boolean) If true, apply filters to mailbox.
     *   - change: (boolean)
     *   - initial: (boolean)
     *   - mbox: (string) The mailbox of the view.
     *   - qsearchmbox: (string) The mailbox to do the quicksearch in.
     *   - qsearchfilter
     *
     * @return array  TODO
     */
    public function listMessages($args)
    {
        global $injector;

        $is_search = false;
        $mbox = IMP_Mailbox::get($args['mbox']);
        $sortpref = $mbox->getSort();

        /* Check for quicksearch request. */
        if (strlen($args['qsearchmbox'])) {
            if (strlen($args['qsearchfilter'])) {
                $imp_search = $injector->getInstance('IMP_Search');
                $imp_search->applyFilter($args['qsearchfilter'], array($args['qsearchmbox']), $mbox);
                $is_search = true;
            } else {
                /* Create the search query. */
                $c_list = array();

                if (strlen($args['qsearchflag'])) {
                    $c_list[] = new IMP_Search_Element_Flag(
                        $args['qsearchflag'],
                        empty($args['qsearchflagnot'])
                    );

                    $is_search = true;
                } elseif (strlen($args['qsearch'])) {
                    $field = $GLOBALS['prefs']->getValue('dimp_qsearch_field');
                    $is_search = true;

                    switch ($field) {
                    case 'all':
                    case 'body':
                        $c_list[] = new IMP_Search_Element_Text(
                            $args['qsearch'],
                            ($field == 'body')
                        );
                        break;

                    case 'from':
                    case 'subject':
                        $c_list[] = new IMP_Search_Element_Header(
                            $args['qsearch'],
                            $field
                        );
                    break;

                    case 'recip':
                        $c_list[] = new IMP_Search_Element_Recipient(
                            $args['qsearch']
                        );
                        break;

                    default:
                        $is_search = false;
                        break;
                    }
                }

                /* Store the search in the session. */
                if ($is_search) {
                    $injector->getInstance('IMP_Search')->createQuery($c_list, array(
                        'id' => $mbox,
                        'mboxes' => array($args['qsearchmbox']),
                        'type' => IMP_Search::CREATE_QUERY
                    ));
                }
            }
        } else {
            $is_search = $mbox->search;
        }

        /* Set the current time zone. */
        $GLOBALS['registry']->setTimeZone();

        /* Run filters now. */
        if (!empty($args['applyfilter'])) {
            $mbox->filter();
        } elseif ($mbox->inbox) {
            $mbox->filterOnDisplay();
        }

        /* Generate the sorted mailbox list now. */
        $mailbox_list = $mbox->getListOb();
        $sorted_list = $mailbox_list->getSortedList();
        $msgcount = count($sorted_list['s']);

        /* Create the base object. */
        $result = $this->getBaseOb($mbox);
        $result->cacheid = $mbox->cacheid;
        if (!empty($args['requestid'])) {
            $result->requestid = intval($args['requestid']);
        }
        $result->totalrows = $msgcount;
        if (!$args['initial']) {
            unset($result->label);
        }

        /* Mail-specific viewport information. */
        $md = &$result->metadata;
        if ($mbox->hideDeletedMsgs(true)) {
            $md->delhide = 1;
        }
        if (!$mbox->access_sortthread) {
            $md->nothread = 1;
        }
        if ($args['initial'] || !is_null($args['sortby'])) {
            $md->sortby = intval($sortpref['by']);
        }
        if ($args['initial'] || !is_null($args['sortdir'])) {
            $md->sortdir = intval($sortpref['dir']);
        }

        /* Actions only done on 'initial' request. */
        if ($args['initial']) {
            if ($mbox->special_outgoing) {
                $md->special = 1;
                if ($mbox == IMP_Mailbox::getPref('drafts_folder')) {
                    $md->drafts = 1;
                }
            } elseif ($mbox == IMP_Mailbox::getPref('spam_folder')) {
                $md->spam = 1;
            }

            if ($is_search) {
                $md->search = 1;
            }

            /* Generate flag array. */
            $flaglist = $injector->getInstance('IMP_Flags')->getList(array(
                'imap' => true,
                'mailbox' => $is_search ? null : $mbox
            ));

            $md->flags = array();
            foreach ($flaglist as $val) {
                $md->flags[] = $val->imapflag;
            }
        }

        /* The search query may have changed. */
        if ($is_search &&
            ($args['initial'] || strlen($args['qsearchmbox']))) {
            $imp_search = $injector->getInstance('IMP_Search');

            if ($mbox->vfolder) {
                $md->slabel = $imp_search[$mbox]->label;
                $md->vfolder = 1;
                if (!$imp_search->isVFolder($mbox, true)) {
                    $md->noedit = 1;
                }
            } else {
                $md->slabel = $imp_search[$mbox]->querytext;
            }
        }

        /* These entries may change during a session, so always need to
         * update them. */
        $md->readonly = intval($mbox->readonly);
        if (!$md->readonly) {
            if (!$mbox->access_deletemsgs) {
                $md->nodelete = 1;
            }
            if (!$mbox->access_expunge) {
                $md->noexpunge = 1;
            }
        }

        /* Check for mailbox existence now. If there are no messages, there
         * is a chance that the mailbox doesn't exist. If there is at least
         * 1 message, we don't need this check. */
        if (empty($msgcount) && !$is_search) {
            if (!$mbox->exists) {
                $GLOBALS['notification']->push(sprintf(_("Mailbox %s does not exist."), $mbox->label), 'horde.error');
            }

            return $result;
        }

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        /* Check for UIDVALIDITY expiration. It is the first element in the
         * cacheid returned from the browser. If it has changed, we need to
         * purge the cached items on the browser (send 'reset' param to
         * ViewPort). */
        if (!$is_search &&
            !empty($args['cacheid']) &&
            !empty($args['cache'])) {
            $uid_expire = false;
            try {
                $status = $imp_imap->status($mbox, Horde_Imap_Client::STATUS_UIDVALIDITY);
                $parsed = $imp_imap->parseCacheId($args['cacheid']);
                $uid_expire = ($parsed['uidvalidity'] != $status['uidvalidity']);
            } catch (Horde_Imap_Cache_Exception $e) {
                $uid_expire = true;
            }

            if ($uid_expire) {
                $args['cache'] = array();
                $result->reset = $result->resetmd = 1;
            }
        }

        /* TODO: This can potentially be optimized for arrival time sort - if
         * the cache ID changes, we know the changes must occur at end of
         * mailbox. */
        if (!isset($result->reset) && !empty($args['change'])) {
            $result->update = 1;
        }

        /* Get the cached list. */
        $cached = array();
        if (!empty($args['cache'])) {
            $cached = $imp_imap->getUtils()->fromSequenceString($args['cache']);
            $cached = $is_search
                ? array_flip($cached)
                : array_flip(reset($cached));
        }

        if (!empty($args['search_unseen'])) {
            /* Do an unseen search.  We know what messages the browser
             * doesn't have based on $cached. Thus, search for the first
             * unseen message not located in $cached. */
            $unseen_search = $mailbox_list->unseenMessages(Horde_Imap_Client::SORT_RESULTS_MATCH, true);
            if (!($uid_search = array_diff($unseen_search['match']->ids, array_keys($cached)))) {
                return $result;
            }
            $rownum = array_search(reset($uid_search), $sorted_list['s']);
        } elseif (!empty($args['search_uid'])) {
            $rownum = 1;
            foreach (array_keys($sorted_list['s'], $args['search_uid']) as $val) {
                if (empty($sorted_list['m'][$val]) ||
                    ($sorted_list['m'][$val] == $args['search_mbox'])) {
                    $rownum = $val;
                    break;
                }
            }
        } else {
            /* If this is the initial request for a mailbox, figure out the
             * starting location based on user's preferences. */
            $rownum = $args['initial']
                ? intval($mailbox_list->mailboxStart($msgcount))
                : null;
        }

        /* Determine the row slice to process. */
        if (is_null($rownum)) {
            $slice_start = $args['slice_start'];
            $slice_end = $args['slice_end'];
        } else {
            $slice_start = $rownum - $args['before'];
            $slice_end = $rownum + $args['after'];
            if ($slice_start < 1) {
                $slice_end += abs($slice_start) + 1;
            } elseif ($slice_end > $msgcount) {
                $slice_start -= $slice_end - $msgcount;
            }

            $result->rownum = $rownum;
        }

        $slice_start = max(1, $slice_start);
        $slice_end = min($msgcount, $slice_end);

        /* Generate UID list. */
        $changed = $data = $msglist = $rowlist = $uidlist = array();
        for ($i = 1, $end = count($sorted_list['s']); $i <= $end; ++$i) {
            $uid = $sorted_list['s'][$i];
            if (isset($sorted_list['m'][$i])) {
                $uid = $sorted_list['m'][$i] . IMP_Dimp::IDX_SEP . $uid;
            }
            $uidlist[] = $uid;
        }

        /* If we are updating the rowlist on the browser, and we have cached
         * browser data information, we need to send a list of messages that
         * have 'disappeared'. */
        if (isset($result->update)) {
            $disappear = array();
            foreach (array_diff(array_keys($cached), $uidlist) as $uid) {
                $disappear[] = $uid;
                unset($cached[$uid]);
            }
            if (!empty($disappear)) {
                $result->disappear = $disappear;
            }
        }

        /* Check for cached entries marked as changed via CONDSTORE IMAP
         * extension. If changed, resend the entire entry to update the
         * browser cache (done below). */
        if (!$is_search && $args['change'] && $args['cacheid']) {
            if (!isset($parsed)) {
                $parsed = $imp_imap->parseCacheId($args['cacheid']);
            }
            if (!empty($parsed['highestmodseq'])) {
                $status = $imp_imap->status($mbox, Horde_Imap_Client::STATUS_LASTMODSEQ | Horde_Imap_Client::STATUS_LASTMODSEQUIDS);
                if ($status['lastmodseq'] == $parsed['highestmodseq']) {
                    /* QRESYNC already provided the updated list of flags -
                     * we can grab the updated UIDS through this STATUS call
                     * and save a FETCH. */
                    $changed = array_flip($status['lastmodsequids']);
                } else {
                    $query = new Horde_Imap_Client_Fetch_Query();
                    $query->uid();

                    try {
                        $changed = $imp_imap->fetch($mbox, $query, array(
                            'changedsince' => $parsed['highestmodseq'],
                            'ids' => new Horde_Imap_Client_Ids(array_keys($cached))
                        ));
                    } catch (IMP_Imap_Exception $e) {}
                }
            }
        }

        foreach (array_slice($uidlist, $slice_start - 1, $slice_end - $slice_start + 1, true) as $key => $uid) {
            $seq = ++$key;
            $msglist[$seq] = $sorted_list['s'][$seq];
            $rowlist[$uid] = $seq;
            /* Send browser message data if not already cached or if
             * CONDSTORE has indicated that data has changed. */
            if (!isset($cached[$uid]) || isset($changed[$uid])) {
                $data[$seq] = 1;
            }
        }
        $result->rowlist = $rowlist;

        /* Build the list for rangeslice information. */
        if ($args['rangeslice']) {
            $slice = new stdClass;
            $slice->rangelist = array_keys($rowlist);
            $slice->view = strval($mbox);

            return $slice;
        }

        /* Build the overview list. */
        $result->data = $this->_getOverviewData($mbox, array_keys($data));

        if ($is_search) {
            $result->search = 1;
        } elseif ($sortpref['by'] == Horde_Imap_Client::SORT_THREAD) {
            /* Get thread information. */
            $imp_thread = new IMP_Imap_Thread($mailbox_list->getThreadOb());
            $md->thread = (object)$imp_thread->getThreadTreeOb($msglist, $sortpref['dir']);
        }

        return $result;
    }

    /**
     * Obtains IMAP overview data for a given set of message UIDs.
     *
     * @param IMP_Mailbox $mbox  The current mailbox.
     * @param array $msglist     The list of message sequence numbers to
     *                           process.
     *
     * @return array  TODO
     * @throws Horde_Exception
     */
    private function _getOverviewData($mbox, $msglist)
    {
        $msgs = array();

        if (empty($msglist)) {
            return $msgs;
        }

        /* Get mailbox information. */
        $overview = $mbox->getListOb()->getMailboxArray($msglist, array(
            'headers' => true,
            'type' => $GLOBALS['prefs']->getValue('atc_flag')
        ));
        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();
        $imp_ui = new IMP_Ui_Mailbox($mbox);

        $flags = $imp_imap->access(IMP_Imap::ACCESS_FLAGS);
        $pop3 = $imp_imap->pop3;
        $search = $mbox->search;

        /* Display message information. */
        reset($overview['overview']);
        while (list(,$ob) = each($overview['overview'])) {
            /* Initialize the header fields. */
            $msg = array(
                'flag' => array(),
                'uid' => ($pop3 ? $ob['uid'] : intval($ob['uid'])),
                'view' => $ob['mailbox']
            );

            /* Get all the flag information. */
            if ($flags) {
                if ($this->_flaghook) {
                    try {
                        $ob['flags'] = array_merge($ob['flags'], Horde::callHook('msglist_flags', array($ob, 'dimp'), 'imp'));
                    } catch (Horde_Exception_HookNotSet $e) {
                        $this->_flaghook = false;
                    }
                }

                $flag_parse = $GLOBALS['injector']->getInstance('IMP_Flags')->parse(array(
                    'flags' => $ob['flags'],
                    'headers' => $ob['headers'],
                    'personal' => Horde_Mime_Address::getAddressesFromObject($ob['envelope']->to, array('charset' => 'UTF-8'))
                ));

                foreach ($flag_parse as $val) {
                    $msg['flag'][] = $val->id;
                }
            }

            /* Format size information. */
            $msg['size'] = htmlspecialchars($imp_ui->getSize($ob['size']), ENT_QUOTES, 'UTF-8');

            /* Format the Date: Header. */
            $msg['date'] = htmlspecialchars($imp_ui->getDate($ob['envelope']->date), ENT_QUOTES, 'UTF-8');

            /* Format the From: Header. */
            $getfrom = $imp_ui->getFrom($ob['envelope'], array('specialchars' => 'UTF-8'));
            $msg['from'] = $getfrom['from'];

            /* Format the Subject: Header. */
            $msg['subject'] = $imp_ui->getSubject($ob['envelope']->subject, true);

            /* Check to see if this is a list message. Namely, we want to
             * check for 'List-Post' information because that is the header
             * that gives the e-mail address to reply to, which is all we
             * care about. */
            if ($ob['headers']->getValue('list-post')) {
                $msg['listmsg'] = 1;
            }

            /* Need both mailbox and UID to create a unique ID string if
             * using a search mailbox.  Otherwise, use only the UID. */
            if ($search) {
                $msgs[$ob['mailbox'] . IMP_Dimp::IDX_SEP . $ob['uid']] = $msg;
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

    /**
     * Prepare the base object used by the ViewPort javascript class.
     *
     * @param IMP_Mailbox $mbox  The mailbox object.
     *
     * @return object  The base ViewPort object.
     */
    public function getBaseOb(IMP_Mailbox $mbox)
    {
        $ob = new stdClass;
        $ob->cacheid = 0;
        $ob->data = array();
        $ob->label = htmlspecialchars($mbox->label);
        $ob->metadata = new stdClass;
        $ob->rowlist = array();
        $ob->totalrows = 0;
        $ob->view = strval($mbox);

        return $ob;
    }

}
