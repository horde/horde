<?php
/**
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */

/**
 * Handles fetching changes using the HIGHESTMODSEQ value of a
 * QRESYNC/CONDSTORE enabled IMAP server.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Imap_Strategy_Modseq extends Horde_ActiveSync_Imap_Strategy_Base
{
    /**
     * Flag to indicate if the HIGHESTMODSEQ value returned in the STATUS call
     * is to be trusted.
     *
     * @var boolean
     */
    protected $_modseq_valid = true;

    /**
     * Const'r
     *
     * @param Horde_ActiveSync_Interface_ImapFactory $imap The IMAP factory.
     * @param array $status                         The IMAP status array.
     * @param Horde_ActiveSync_Folder_Base $folder  The folder object.
     * @param Horde_Log_Logger $logger              The logger.
     */
    public function __construct(
        Horde_ActiveSync_Interface_ImapFactory $imap,
        array $status,
        Horde_ActiveSync_Folder_Base $folder,
        $logger)
    {
        // If IMAP server reports invalid MODSEQ, this can lead to the client
        // no longer ever able to detect changes therefore never receiving new
        // email even if the value is restored at some point in the future.
        //
        // This can happen, e.g., if the IMAP server index files are lost or
        // otherwise corrupted. Normally this would be handled as a loss of
        // server state and handled by a complete resync, but a majority of
        // EAS clients do not properly handle the status codes that report this.
        parent::__construct($imap, $status, $folder, $logger);
        if ($folder->modseq > $this->_status[Horde_ActiveSync_Folder_Imap::HIGHESTMODSEQ]) {
            $this->_logger->err(sprintf(
                '[%s] IMAP Server error: Current HIGHESTMODSEQ is lower than previously reported.',
                 $this->_procid)
            );
            $this->_modseq_valid = false;
        }
    }

    /**
     * Return a folder object containing all IMAP server change information.
     *
     * @param array $options  An array of options.
     *        @see Horde_ActiveSync_Imap_Adapter::getMessageChanges
     *
     * @return Horde_ActiveSync_Folder_Base  The populated folder object.
     */
    public function getChanges(array $options)
    {
        $this->_logger->info(sprintf(
            '[%s] CONDSTORE and CHANGES',
            $this->_procid)
        );
        $current_modseq = $this->_status[Horde_ActiveSync_Folder_Imap::HIGHESTMODSEQ];
        $query = new Horde_Imap_Client_Search_Query();

        // Increment since $imap->search uses >= operator.
        if ($this->_modseq_valid) {
            $query->modseq($this->_folder->modseq() + 1);
        }

        if (!empty($options['sincedate'])) {
            $query->dateSearch(
                new Horde_Date($options['sincedate']),
                Horde_Imap_Client_Search_Query::DATE_SINCE
            );
        }

        $search_ret = $this->_imap_ob->search(
            $this->_mbox,
            $query,
            array('results' => array(Horde_Imap_Client::SEARCH_RESULTS_MATCH))
        );

        $search_uids = $search_ret['count']
            ? $search_ret['match']->ids
            : array();

        // Catch changes to FILTERTYPE.
        if (!empty($options['refreshfilter'])) {
            $this->_logger->info(sprintf(
                '[%s] Checking for additional messages within the new FilterType parameters.',
                $this->_procid)
            );
            $search_ret = $this->_searchQuery($options, false);
            if ($search_ret['count']) {
                $this->_logger->info(sprintf(
                    '[%s] Found %d messages that are now outside FilterType.',
                    $this->_procid, $search_ret['count'])
                );
                $search_uids = array_merge($search_uids, $search_ret['match']->ids);
            }
        }

        // Protect against very large change sets.
        $cnt = (count($search_uids) / Horde_ActiveSync_Imap_Adapter::MAX_FETCH) + 1;
        $query = new Horde_Imap_Client_Fetch_Query();
        if ($this->_modseq_valid) {
            $query->modseq();
        }
        $query->flags();
        $changes = array();
        $categories = array();
        for ($i = 0; $i <= $cnt; $i++) {
            $ids = new Horde_Imap_Client_Ids(
                array_slice(
                    $search_uids,
                    $i * Horde_ActiveSync_Imap_Adapter::MAX_FETCH, Horde_ActiveSync_Imap_Adapter::MAX_FETCH
                )
            );
            try {
                $fetch_ret = $this->_imap_ob->fetch(
                    $this->_mbox,
                    $query,
                    array('ids' => $ids)
                );
            } catch (Horde_Imap_Client_Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception($e);
            }
            $this->_buildModSeqChanges(
                $changes, $flags, $categories, $fetch_ret, $options, $current_modseq
            );
        }

        // Set the changes in the folder object.
        $this->_folder->setChanges(
            $changes,
            $flags,
            $categories,
            !empty($options['softdelete']) || !empty($options['refreshfilter'])
        );

        // Check for deleted messages.
        try {
            $deleted = $this->_imap_ob->vanished(
                $this->_mbox,
                $this->_folder->modseq(),
                array('ids' => new Horde_Imap_Client_Ids($this->_folder->messages())));
        } catch (Horde_Imap_Client_Excetion $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }
        $this->_folder->setRemoved($deleted->ids);
        $this->_logger->info(sprintf(
            '[%s] Found %d deleted messages.',
            $this->_procid, $deleted->count())
        );

        // Check for SOFTDELETE messages.
        if (!empty($options['sincedate']) &&
            (!empty($options['softdelete']) || !empty($options['refreshfilter']))) {
            $this->_logger->info(sprintf(
                '[%s] Polling for SOFTDELETE in %s before %d',
                $this->_procid, $this->_folder->serverid(), $options['sincedate'])
            );
            $search_ret = $this->_searchQuery($options, true);
            if ($search_ret['count']) {
                $this->_logger->info(sprintf(
                    '[%s] Found %d messages to SOFTDELETE.',
                    $this->_procid, count($search_ret['match']->ids))
                );
                $this->_folder->setSoftDeleted($search_ret['match']->ids);
            }
            $this->_folder->setSoftDeleteTimes($options['sincedate'], time());
        }

        return $this->_folder;
    }

    /**
     * Return message UIDs that are now within the cureent FILTERTYPE value.
     *
     * @param  array                        $options   Options array.
     * @param  boolean                      $is_delete If true, return messages
     *                                                 to SOFTDELETE.
     *
     * @return Horde_Imap_Client_Search_Results
     */
    protected function _searchQuery($options, $is_delete)
    {
        $query = new Horde_Imap_Client_Search_Query();
        $query->dateSearch(
            new Horde_Date($options['sincedate']),
            $is_delete
                ? Horde_Imap_Client_Search_Query::DATE_BEFORE
                : Horde_Imap_Client_Search_Query::DATE_SINCE
        );
        $query->ids(new Horde_Imap_Client_Ids($this->_folder->messages()), !$is_delete);
        try {
            return $this->_imap_ob->search(
                $this->_mbox,
                $query,
                array('results' => array(Horde_Imap_Client::SEARCH_RESULTS_MATCH)));
        } catch (Horde_Imap_Client_Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Populates the changes, flags, and categories arrays with data from
     * any messages added/changed on the IMAP server since the last poll.
     *
     * @param array &$changes                             Changes array.
     * @param array &$flags                               Flags array.
     * @param array &$categories                          Categories array.
     * @param Horde_Imap_Client_Fetch_Results $fetch_ret  Fetch results.
     * @param array $options                              Options array.
     * @param integer $modseq                             Current MODSEQ.
     */
    protected function _buildModSeqChanges(
        &$changes, &$flags, &$categories, $fetch_ret, $options, $modseq)
    {
        // Get custom flags to use as categories.
        $msgFlags = $this->_getMsgFlags();

        // Filter out any changes that we already know about.
        $fetch_keys = $fetch_ret->ids();
        $result_set = array_diff($fetch_keys, $changes);

        foreach ($result_set as $uid) {
            // Ensure no changes after the current modseq have been returned.
            $data = $fetch_ret[$uid];
            if ($data->getModSeq() <= $modseq) {
                $changes[] = $uid;
                $flags[$uid] = array(
                    'read' => (array_search(Horde_Imap_Client::FLAG_SEEN, $data->getFlags()) !== false) ? 1 : 0
                );
                if (($options['protocolversion']) > Horde_ActiveSync::VERSION_TWOFIVE) {
                    $flags[$uid]['flagged'] = (array_search(Horde_Imap_Client::FLAG_FLAGGED, $data->getFlags()) !== false) ? 1 : 0;
                }
                if ($options['protocolversion'] > Horde_ActiveSync::VERSION_TWELVEONE) {
                    $categories[$uid] = array();
                    foreach ($data->getFlags() as $flag) {
                        if (!empty($msgFlags[Horde_String::lower($flag)])) {
                            $categories[$uid][] = $msgFlags[Horde_String::lower($flag)];
                        }
                    }
                }
            }
        }
    }

}