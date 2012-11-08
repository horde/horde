<?php
/**
 * Mailbox synchronization results.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 *
 * @property Horde_Imap_Client_Ids $flagsuids  List of messages with flag
 *                                             changes.
 * @property Horde_Imap_Client_Ids $newmsgsuids  List of new messages.
 * @property Horde_Imap_Client_Ids $vanisheduids  List of messages that have
 *                                                been deleted.
 */
class Horde_Imap_Client_Data_Sync
{
    /**
     * Are there messages that have had flag changes?
     *
     * @var Horde_Imap_Client_Ids
     */
    public $flags = null;

    /**
     * The synchronized mailbox.
     *
     * @var Horde_Imap_Client_Mailbox
     */
    public $mailbox;

    /**
     * Are there new messages?
     *
     * @var boolean
     */
    public $newmsgs = null;

    /**
     * The UIDs of messages that are guaranteed to have vanished. This list is
     * only guaranteed to be available if the server supports QRESYNC or a
     * list of known UIDs is passed to the sync() method.
     *
     * @var Horde_Imap_Client_Ids
     */
    public $vanished = null;

    /**
     * UIDs of messages that have had flag changes.
     *
     * @var Horde_Imap_Client_Ids
     */
    protected $_flagsuids;

    /**
     * UIDs of new messages.
     *
     * @var Horde_Imap_Client_Ids
     */
    protected $_newmsgsuids;

    /**
     * UIDs of messages that have vanished.
     *
     * @var Horde_Imap_Client_Ids
     */
    protected $_vanisheduids;

    /**
     * Constructor.
     *
     * @param Horde_Imap_Client_Base $base_ob  Base driver object.
     * @param mixed $mailbox                   Mailbox to sync.
     * @param array $sync                      Token sync data.
     * @param array $curr                      Current sync data.
     * @param integer $criteria                Mask of criteria to return.
     * @param Horde_Imap_Client_Ids $ids       List of known UIDs.
     *
     * @throws Horde_Imap_Client_Exception
     * @throws Horde_Imap_Client_Exception_Sync
     */
    public function __construct(Horde_Imap_Client_Base $base_ob, $mailbox,
                                $sync, $curr, $criteria, $ids)
    {
        /* Check uidvalidity. */
        if (!isset($sync['V']) || ($curr['V'] != $sync['V'])) {
            throw new Horde_Imap_Client_Exception_Sync('UIDs in cached mailbox have changed.', Horde_Imap_Client_Exception_Sync::UIDVALIDITY_CHANGED);
        }

        $this->mailbox = $mailbox;

        /* This was a UIDVALIDITY check only. */
        if (!$criteria) {
            return;
        }

        $sync_all = ($criteria & Horde_Imap_Client::SYNC_ALL);

        /* New messages. */
        if ($sync_all ||
            ($criteria & Horde_Imap_Client::SYNC_NEWMSGS) ||
            ($criteria & Horde_Imap_Client::SYNC_NEWMSGSUIDS)) {
            $this->newmsgs = empty($sync['U'])
                ? !empty($curr['U'])
                : (!empty($curr['U']) && ($curr['U'] > $sync['U']));

            if ($this->newmsgs &&
                ($sync_all ||
                 ($criteria & Horde_Imap_Client::SYNC_NEWMSGSUIDS))) {
                $new_ids = empty($sync['U'])
                    ? Horde_Imap_Client_Ids::ALL
                    : ($sync['U'] . ':' . $curr['U']);

                $squery = new Horde_Imap_Client_Search_Query();
                $squery->ids($new_ids);
                $sres = $base_ob->search($mailbox, $squery);

                $this->newmsgs = $sres['match'];
            }
        }

        /* Do single status call to get all necessary data. */
        if (isset($sync['H']) &&
            ($sync_all ||
             ($criteria & Horde_Imap_Client::SYNC_FLAGS) ||
             ($criteria & Horde_Imap_Client::SYNC_FLAGSUIDS) ||
             ($criteria & Horde_Imap_Client::SYNC_VANISHED) ||
             ($criteria & Horde_Imap_Client::SYNC_VANISHEDUIDS))) {
            $status_sync = $base_ob->status($mailbox, Horde_Imap_Client::STATUS_SYNCMODSEQ | Horde_Imap_Client::STATUS_SYNCFLAGUIDS | Horde_Imap_Client::STATUS_SYNCVANISHED);

            if (!is_null($ids)) {
                $ids = $base_ob->resolveIds($mailbox, $ids);
            }
        }

        /* Flag changes. */
        if ($sync_all || ($criteria & Horde_Imap_Client::SYNC_FLAGS)) {
            $this->flags = isset($sync['H'])
                ? ($sync['H'] != $curr['H'])
                : true;
        }

        if ($sync_all || ($criteria & Horde_Imap_Client::SYNC_FLAGSUIDS)) {
            if (isset($sync['H'])) {
                if ($sync['H'] == $status_sync['syncmodseq']) {
                    $this->flags = is_null($ids)
                        ? $status_sync['syncflaguids']
                        : $base_ob->getIdsOb(array_intersect($ids->ids, $status_sync['syncflaguids']->ids));
                } else {
                    $squery = new Horde_Imap_Client_Search_Query();
                    $squery->modseq($sync['H'] + 1);
                    $sres = $base_ob->search($mailbox, $squery, array(
                        'ids' => $ids
                    ));
                    $this->flags = $sres['match'];
                }
            } else {
                /* Without MODSEQ, need to mark all FLAGS as changed. */
                $this->flags = $base_ob->resolveIds($mailbox, is_null($ids) ? $base_ob->getIdsOb(Horde_Imap_Client_Ids::ALL) : $ids);
            }
        }

        /* Vanished messages. */
        if ($sync_all ||
            ($criteria & Horde_Imap_Client::SYNC_VANISHED) ||
            ($criteria & Horde_Imap_Client::SYNC_VANISHEDUIDS)) {
            if (isset($sync['H']) &&
                ($sync['H'] == $status_sync['syncmodseq'])) {
                $vanished = is_null($ids)
                    ? $status_sync['syncvanisheduids']
                    : $base_ob->getIdsOb(array_intersect($ids->ids, $status_sync['syncvanisheduids']->ids));
            } else {
                $vanished = $base_ob->vanished($mailbox, isset($sync['H']) ? $sync['H'] : 0, array(
                    'ids' => $ids
                ));
            }

            $this->vanished = (bool)count($vanished);
            $this->vanisheduids = $vanished;
        }
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'flagsuids':
        case 'newmsgsuids':
        case 'vanisheduids':
            $varname = '_' . $name;
            return empty($this->$varname)
                ? new Horde_Imap_Client_Ids()
                : $this->$varname;
        }
    }

}
