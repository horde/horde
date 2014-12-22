<?php
/**
 * Copyright 2005-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2005-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Message listing action for AJAX application handler.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2005-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Application_ListMessages
{
    /**
     * Returns a list of messages for use with ViewPort.
     *
     * @var array $args  TODO
     *   - applyfilter: (boolean) If true, apply filters to mailbox.
     *   - change: (boolean) True if the cache value has changed.
     *   - initial: (boolean) Is this the initial load of the view?
     *   - mbox: (string) The mailbox of the view.
     *   - qsearch: (string) The quicksearch search string.
     *   - qsearchfield: (string) The quicksearch search criteria.
     *   - qsearchmbox: (string) The mailbox to do the quicksearch in
     *                  (base64url encoded).
     *   - qsearchfilter: TODO
     *
     * @return IMP_Ajax_Application_Viewport  Viewport data object.
     */
    public function listMessages($args)
    {
        global $injector, $notification;

        $is_search = false;
        $mbox = IMP_Mailbox::get($args['mbox']);
        $sortpref = $mbox->getSort(true);

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

        /* Run filters now. */
        if (!empty($args['applyfilter'])) {
            $mbox->filter();
        } elseif ($mbox->inbox) {
            $mbox->filterOnDisplay();
        }

        /* Optimization: saves at least a STATUS and an EXAMINE call since
         * we will eventually open mailbox READ-WRITE. */
        $imp_imap = $mbox->imp_imap;
        $imp_imap->openMailbox($mbox, Horde_Imap_Client::OPEN_READWRITE);

        /* Create the base object. */
        $result = new IMP_Ajax_Application_Viewport($mbox);
        $result->label = $mbox->label;

        if ($is_search) {
            /* For search mailboxes, we need to invalidate all browser data
             * and repopulate on force update, since BUIDs may have
             * changed (TODO: only do this if search mailbox has changed?). */
            if (!empty($args['change'])) {
                 $args['cache'] = array();
                 $args['change'] = true;
                 $result->data_reset = $result->rowlist_reset = true;
            }
        } elseif (!$args['initial'] && $args['cacheid'] && $args['cache']) {
            /* Check for UIDVALIDITY expiration. If it has changed, we need to
             * purge the cached items on the browser. */
            $parsed = $imp_imap->parseCacheId($args['cacheid']);
            $uid_expire = true;

            if ($parsed['date'] == date('z')) {
                try {
                    $imp_imap->sync($mbox, $parsed['token'], array(
                        'criteria' => Horde_Imap_Client::SYNC_UIDVALIDITY
                    ));
                    $uid_expire = false;
                } catch (Horde_Imap_Client_Exception_Sync $e) {}
            }

            if ($uid_expire) {
                $args['cache'] = array();
                $args['initial'] = true;
                $result->data_reset = $result->metadata_reset = true;
            }
        } else {
            $parsed = null;
        }

        /* Mail-specific viewport information. */
        if ($args['initial'] ||
            (isset($args['delhide']) && !is_null($args['delhide'])) ||
            !is_null($args['sortby']))  {
            $result->setMetadata('delhide', $mbox->hideDeletedMsgs(true));
        }
        if ($args['initial'] ||
            !is_null($args['sortby']) ||
            !is_null($args['sortdir'])) {
            $result->setMetadata('sortby', $sortpref->sortby);
            $result->setMetadata('sortdir', $sortpref->sortdir);
        }

        /* Actions only done on 'initial' request. */
        if ($args['initial']) {
            /* Load quota information on original request. */
            $injector->getInstance('IMP_Ajax_Queue')->quota($mbox, true);

            if (!$mbox->is_imap) {
                $result->setMetadata('pop3', 1);
            }
            if ($sortpref->sortby_locked) {
                $result->setMetadata('sortbylock', 1);
            }
            if ($sortpref->sortdir_locked) {
                $result->setMetadata('sortdirlock', 1);
            }
            if (!$mbox->access_sortthread) {
                $result->setMetadata('nothread', 1);
            }
            if ($mbox->special_outgoing) {
                $result->setMetadata('special', 1);
                if ($mbox->drafts) {
                    $result->setMetadata('drafts', 1);
                } elseif ($mbox->templates) {
                    $result->setMetadata('templates', 1);
                }
            } elseif ($mbox->spam) {
                $result->setMetadata('innocent_show', 1);
                if ($mbox->spam_show) {
                    $result->setMetadata('spam_show', 1);
                }
            } else {
                if ($mbox->innocent_show) {
                    $result->setMetadata('innocent_show', 1);
                }
                $result->setMetadata('spam_show', 1);
            }

            if ($is_search) {
                $result->setMetadata('innocent_show', 1);
                $result->setMetadata('search', 1);
                $result->setMetadata('spam_show', 1);
            }

            $result->addFlagMetadata();
        }

        /* The search query may have changed. */
        if ($is_search &&
            ($args['initial'] || strlen($args['qsearchmbox']))) {
            $imp_search = $injector->getInstance('IMP_Search');

            if ($mbox->vfolder) {
                $result->setMetadata('slabel', $imp_search[$mbox]->label);
                $result->setMetadata('vfolder', 1);
                if (!$imp_search->isVFolder($mbox, true)) {
                    $result->setMetadata('noedit', 1);
                }
            } else {
                $result->setMetadata('slabel', $imp_search[$mbox]->querytext);
            }
        }

        /* These entries may change during a session, so always need to
         * update them. */
        if ($mbox->readonly) {
            $result->setMetadata('readonly', 1);
            $result->setMetadata('nodelete', 1);
            $result->setMetadata('expunge', 1);
        } else {
            if (!$mbox->access_deletemsgs) {
                $result->setMetadata('nodelete', 1);
            }
            if (!$mbox->access_expunge) {
                $result->setMetadata('noexpunge', 1);
            }
        }

        /* Generate the sorted mailbox list now. */
        $mailbox_list = $mbox->list_ob;
        if ($is_search && (!empty($args['change']) || $args['initial'])) {
            $mailbox_list->rebuild(true);
        }

        $msgcount = count($mailbox_list);

        /* Check for mailbox existence now. If there are no messages, there
         * is a chance that the mailbox doesn't exist. If there is at least
         * 1 message, we don't need this check. */
        if (empty($msgcount) && !$is_search) {
            if (!$mbox->exists) {
                $notification->push(sprintf(_("Mailbox %s does not exist."), $mbox->label), 'horde.error');
            }

            if (!empty($args['change'])) {
                $result->data_reset = true;
                $result->rowlist_reset = true;
            }

            return $result;
        }

        $result->totalrows = $msgcount;

        /* TODO: This can potentially be optimized for arrival time sort - if
         * the cache ID changes, we know the changes must occur at end of
         * mailbox. */
        if (!$result->data_reset && !empty($args['change'])) {
            $result->rowlist_reset = true;
        }

        /* Get the cached list. */
        if (empty($args['cache'])) {
            $cached = array();
        } else {
            $cache_indices = new IMP_Indices($mbox, $args['cache']);
            $cache_uids = $cache_indices->getSingle(true);
            $cached = array_flip($cache_uids[1]);
        }

        if (!$is_search && !empty($args['search_unseen'])) {
            /* Do an unseen search.  We know what messages the browser
             * doesn't have based on $cached. Thus, search for the first
             * unseen message not located in $cached. */
            $unseen_search = $mailbox_list->unseenMessages(Horde_Imap_Client::SEARCH_RESULTS_MATCH, array('uids' => true));
            if (!($uid_search = array_diff($unseen_search['match']->ids, array_keys($cached)))) {
                return $result;
            }
            $rownum = $mailbox_list->getArrayIndex(reset($uid_search));
        } elseif (!empty($args['search_buid'])) {
            $search_buid = $mailbox_list->resolveBuid($args['search_buid']);
            $rownum = $mailbox_list->getArrayIndex($search_buid['u'], $search_buid['m']);
        }

        /* If this is the initial request for a mailbox, figure out the
         * starting location based on user's preferences. */
        $rownum = (($args['initial'] && !isset($rownum)) || (isset($rownum) && is_null($rownum)))
                ? intval($mailbox_list->mailboxStart($msgcount))
                : (isset($rownum) ? ($rownum + 1) : null);

        /* Determine the row slice to process. */
        if (is_null($rownum) || isset($args['slice_start'])) {
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
        }

        if (!is_null($rownum)) {
            $result->rownum = $rownum;
        }

        $slice_start = max(1, $slice_start);
        $slice_end = min($msgcount, $slice_end);

        /* Generate BUID list. */
        $buidlist = $changed = $data = $msglist = $rowlist = array();
        foreach ($mailbox_list as $val) {
            $buidlist[] = $mailbox_list->getBuid($val['m'], $val['u']);
        }

        /* If we are updating the rowlist on the browser, and we have cached
         * browser data information, we need to send a list of messages that
         * have 'disappeared'. */
        if (!empty($cached) && $result->rowlist_reset) {
            $disappear = array();
            foreach (array_diff(array_keys($cached), $buidlist) as $uid) {
                $disappear[] = $uid;
                unset($cached[$uid]);
            }
            if (!empty($disappear)) {
                $result->disappear = $disappear;
            }
        }

        /* Check for cached entries marked as changed. If changed, resend the
         * entire entry to update the browser cache (done below). */
        if (!empty($cached) && !$is_search && !is_null($parsed)) {
            $sync_ob = $imp_imap->sync($mbox, $parsed['token'], array(
                'criteria' => Horde_Imap_Client::SYNC_FLAGSUIDS,
                'ids' => $imp_imap->getIdsOb(array_keys($cached))
            ));
            $changed = array_flip($sync_ob->flagsuids->ids);
        }

        foreach (array_slice($buidlist, $slice_start - 1, $slice_end - $slice_start + 1, true) as $key => $uid) {
            $seq = ++$key;
            $msglist[$seq] = $mailbox_list[$seq]['u'];
            $rowlist[$uid] = $seq;
            /* Send browser message data if not already cached or if CONDSTORE
             * has indicated that data has changed. */
            if (!isset($cached[$uid]) || isset($changed[$uid])) {
                $data[$seq] = 1;
            }
        }

        /* Build the list for rangeslice information. */
        if ($args['rangeslice']) {
            $slice = new IMP_Ajax_Application_Viewport($mbox);
            $slice->rangelist = array_keys($rowlist);
            return $slice;
        }

        $result->rowlist = $rowlist;

        /* Build the overview list. */
        $result->data = $this->_getOverviewData($mbox, array_keys($data));

        /* Get thread information. */
        if ($sortpref->sortby == Horde_Imap_Client::SORT_THREAD) {
            $thread = new stdClass;
            foreach ($msglist as $key => $val) {
                $tmp = $mailbox_list->getThreadOb($key);
                $thread->$val = $sortpref->sortdir
                    ? $tmp->reverse_raw
                    : $tmp->raw;
            }

            $result->setMetadata('thread', $thread);
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
        global $injector, $prefs;

        $msgs = array();

        if (empty($msglist)) {
            return $msgs;
        }

        /* Get mailbox information. */
        $flags = $mbox->access_flags;
        $imp_flags = $injector->getInstance('IMP_Flags');
        $imp_ui = new IMP_Mailbox_Ui($mbox);
        $list_ob = $mbox->list_ob;
        $overview = $list_ob->getMailboxArray($msglist, array(
            'headers' => true,
            'type' => $prefs->getValue('atc_flag')
        ));

        /* Display message information. */
        reset($overview['overview']);
        while (list(,$ob) = each($overview['overview'])) {
            /* Get all the flag information. */
            $msg = array(
                'flag' => $flags
                    ? array_map('strval', $imp_flags->parse(array(
                          'flags' => $ob['flags'],
                          'headers' => $ob['headers'],
                          'runhook' => $ob,
                          'personal' => $ob['envelope']->to
                      )))
                    : array()
            );

            /* Format size information. */
            $msg['size'] = IMP::sizeFormat($ob['size']);

            /* Format the Date: Header. */
            $msg['date'] = strval(new IMP_Message_Date(
                isset($ob['envelope']->date) ? $ob['envelope']->date : null
            ));

            /* Format the From: Header. */
            $getfrom = $imp_ui->getFrom($ob['envelope']);
            $msg['from'] = $getfrom['from'];
            if ($getfrom['from'] !== $getfrom['from_addr']) {
                $msg['fromaddr'] = $getfrom['from_addr'];
            }

            /* Format the Subject: Header. */
            $msg['subject'] = $imp_ui->getSubject($ob['envelope']->subject);

            /* Check to see if this is a list message. Namely, we want to
             * check for 'List-Post' information because that is the header
             * that gives the e-mail address to reply to, which is all we
             * care about. */
            if (isset($ob['headers']['List-Post'])) {
                $msg['listmsg'] = 1;
            }

            $msgs[$list_ob->getBuid($ob['mailbox'], $ob['uid'])] = $msg;
        }

        return $msgs;
    }

}
