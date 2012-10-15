<?php
/**
 * Message listing action for AJAX application handler.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Ajax_Application_ListMessages
{
    /* String used to separate mailboxes/indexes in search mailboxes. */
    const IDX_SEP = "\0";

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
     *   - initial: (boolean) Is this the initial load of the view?
     *   - mbox: (string) The mailbox of the view.
     *   - qsearch: (string) The quicksearch search string.
     *   - qsearchfield: (string) The quicksearch search criteria.
     *   - qsearchmbox: (string) The mailbox to do the quicksearch in
     *                  (base64url encoded).
     *   - qsearchfilter: TODO
     *
     * @return array  TODO
     */
    public function listMessages($args)
    {
        global $injector;

        $initial = $args['initial'];
        $is_search = false;
        $mbox = IMP_Mailbox::get($args['mbox']);
        $sortpref = $mbox->getSort();

        /* Check for quicksearch request. */
        if (strlen($args['qsearchmbox'])) {
            $qsearch_mbox = IMP_Mailbox::formFrom($args['qsearchmbox']);

            if (strlen($args['qsearchfilter'])) {
                $injector->getInstance('IMP_Search')->applyFilter($args['qsearchfilter'], array($qsearch_mbox), $mbox);
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
                    $is_search = true;

                    switch ($args['qsearchfield']) {
                    case 'all':
                    case 'body':
                        $c_list[] = new IMP_Search_Element_Text(
                            $args['qsearch'],
                            ($args['qsearchfield'] == 'body')
                        );
                        break;

                    case 'from':
                    case 'subject':
                        $c_list[] = new IMP_Search_Element_Header(
                            $args['qsearch'],
                            $args['qsearchfield']
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
                        'mboxes' => array($qsearch_mbox),
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
        $msgcount = count($mailbox_list);

        /* Create the base object. */
        $result = $this->getBaseOb($mbox);
        $result->cacheid = $mbox->cacheid_date;
        if (!empty($args['requestid'])) {
            $result->requestid = intval($args['requestid']);
        }
        $result->totalrows = $msgcount;

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        /* Check for UIDVALIDITY expiration. It is the first element in the
         * cacheid returned from the browser. If it has changed, we need to
         * purge the cached items on the browser. */
        $parsed = null;
        if ($args['cacheid'] && $args['cache']) {
            $uid_expire = false;
            $parsed = $imp_imap->parseCacheId($args['cacheid']);

            if ($parsed['date'] != date('mdy')) {
                $uid_expire = true;
            } elseif (!$is_search) {
                try {
                    $status = $imp_imap->status($mbox, Horde_Imap_Client::STATUS_UIDVALIDITY);
                    $uid_expire = ($parsed['uidvalidity'] != $status['uidvalidity']);
                } catch (Horde_Imap_Cache_Exception $e) {
                    $uid_expire = true;
                }
            }

            if ($uid_expire) {
                $args['cache'] = array();
                $args['initial'] = true;
                $result->data_reset = $result->metadata_reset = 1;
            }
        }

        /* Mail-specific viewport information. */
        $md = &$result->metadata;
        if (($args['initial'] ||
             $args['delhide'] ||
             !is_null($args['sortby'])) &&
            $mbox->hideDeletedMsgs(true)) {
            $md->delhide = 1;
        }
        if ($args['initial'] ||
            !is_null($args['sortby']) ||
            !is_null($args['sortdir'])) {
            $md->sortby = intval($sortpref->sortby);
            $md->sortdir = intval($sortpref->sortdir);
        }

        /* Actions only done on 'initial' request. */
        if ($args['initial']) {
            if ($sortpref->sortby_locked) {
                $md->sortbylock = 1;
            }
            if ($sortpref->sortdir_locked) {
                $md->sortdirlock = 1;
            }
            if (!$mbox->access_sortthread) {
                $md->nothread = 1;
            }
            if ($mbox->special_outgoing) {
                $md->special = 1;
                if ($mbox->drafts) {
                    $md->drafts = 1;
                } elseif ($mbox->templates) {
                    $md->templates = 1;
                }
            } elseif ($mbox->spam) {
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

            if (!empty($args['change'])) {
                unset($result->data, $result->rowlist, $result->totalrows);
                $result->data_reset = $result->rowlist_reset = 1;
            }

            return $result;
        }

        /* TODO: This can potentially be optimized for arrival time sort - if
         * the cache ID changes, we know the changes must occur at end of
         * mailbox. */
        if (!isset($result->data_reset) && !empty($args['change'])) {
            $result->rowlist_reset = 1;
        }

        /* Get the cached list. */
        $cached = array();
        if (!empty($args['cache'])) {
            foreach ($imp_imap->getUtils()->fromSequenceString($args['cache']) as $key => $uids) {
                $key = IMP_Mailbox::formFrom($key);

                foreach ($uids as $val) {
                    $cached[] = $is_search
                        ? $this->searchUid($key, $val)
                        : $val;
                }
            }
            $cached = array_flip($cached);
        }

        if (!empty($args['search_unseen'])) {
            /* Do an unseen search.  We know what messages the browser
             * doesn't have based on $cached. Thus, search for the first
             * unseen message not located in $cached. */
            $unseen_search = $mailbox_list->unseenMessages(Horde_Imap_Client::SEARCH_RESULTS_MATCH, true);
            if (!($uid_search = array_diff($unseen_search['match']->ids, array_keys($cached)))) {
                return $result;
            }
            $rownum = $mailbox_list->getArrayIndex(reset($uid_search));
        } elseif (!empty($args['search_uid'])) {
            $rownum = $mailbox_list->getArrayIndex($args['search_uid'], $mbox);
        }

        /* If this is the initial request for a mailbox, figure out the
         * starting location based on user's preferences. */
        $rownum = (($initial && !isset($rownum)) || (isset($rownum) && is_null($rownum)))
                ? intval($mailbox_list->mailboxStart($msgcount))
                : (isset($rownum) ? ($rownum + 1) : null);

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
        foreach ($mailbox_list as $key => $val) {
            $uidlist[] = $is_search
                ? $this->searchUid($val['m'], $val['u'])
                : $val['u'];
        }

        /* If we are updating the rowlist on the browser, and we have cached
         * browser data information, we need to send a list of messages that
         * have 'disappeared'. */
        if (!empty($cached) && isset($result->rowlist_reset)) {
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
        if (!empty($cached) &&
            !$is_search &&
            !is_null($parsed) &&
            !empty($parsed['highestmodseq'])) {
            $status = $imp_imap->status($mbox, Horde_Imap_Client::STATUS_LASTMODSEQ | Horde_Imap_Client::STATUS_LASTMODSEQUIDS);
            if ($status['lastmodseq'] == $parsed['highestmodseq']) {
                /* QRESYNC already provided the updated list of flags - we can
                 * grab the updated UIDS through this STATUS call and save a
                 * FETCH. */
                $changed = array_flip($status['lastmodsequids']);
            } else {
                $query = new Horde_Imap_Client_Fetch_Query();
                $query->uid();

                try {
                    $changed = $imp_imap->fetch($mbox, $query, array(
                        'changedsince' => $parsed['highestmodseq'],
                        'ids' => $imp_imap->getIdsOb(array_keys($cached))
                    ));
                } catch (IMP_Imap_Exception $e) {}
            }
        }

        foreach (array_slice($uidlist, $slice_start - 1, $slice_end - $slice_start + 1, true) as $key => $uid) {
            $seq = ++$key;
            $msglist[$seq] = $mailbox_list[$seq]['u'];
            $rowlist[$uid] = $seq;
            /* Send browser message data if not already cached or if CONDSTORE
             * has indicated that data has changed. */
            if (!isset($cached[$uid]) || isset($changed[$uid])) {
                $data[$seq] = 1;
            }
        }
        $result->rowlist = $rowlist;

        /* Build the list for rangeslice information. */
        if ($args['rangeslice']) {
            $slice = new stdClass;
            $slice->rangelist = array_keys($rowlist);
            $slice->view = $mbox->form_to;

            return $slice;
        }

        /* Build the overview list. */
        $result->data = $this->_getOverviewData($mbox, array_keys($data));

        /* Get thread information. */
        if ($sortpref->sortby == Horde_Imap_Client::SORT_THREAD) {
            $thread = new stdClass;
            foreach ($msglist as $key => $val) {
                $tmp = $mailbox_list[$key]['t'];
                $thread->$val = $sortpref->sortdir
                    ? $tmp->reverse_raw
                    : $tmp->raw;
            }

            $md->thread = $thread;
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
                'mbox' => IMP_Mailbox::formTo($ob['mailbox']),
                'uid' => ($pop3 ? $ob['uid'] : intval($ob['uid']))
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
                    'personal' => $ob['envelope']->to
                ));

                foreach ($flag_parse as $val) {
                    $msg['flag'][] = $val->id;
                }
            }

            /* Format size information. */
            $msg['size'] = $imp_ui->getSize($ob['size']);

            /* Format the Date: Header. */
            $msg['date'] = $imp_ui->getDate($ob['envelope']->date);

            /* Format the From: Header. */
            $getfrom = $imp_ui->getFrom($ob['envelope']);
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
                $msgs[$this->searchUid($ob['mailbox'], $ob['uid'])] = $msg;
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
        $ob->label = $mbox->label;
        $ob->metadata = new stdClass;
        $ob->rowlist = array();
        $ob->totalrows = 0;
        $ob->view = $mbox->form_to;

        return $ob;
    }

    /**
     * Generate the ViewPort UID to use for search mailboxes.
     *
     * @param string $mbox  Message mailbox.
     * @param string $uid   Message UID.
     *
     * @return string  ViewPort UID.
     */
    static public function searchUid($mbox, $uid)
    {
        return IMP::base64urlEncode(strval($mbox) . self::IDX_SEP . $uid);
    }

}
