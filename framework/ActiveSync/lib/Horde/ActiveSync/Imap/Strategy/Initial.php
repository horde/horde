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
 * Handles fetching initial set of message changes for the first sync.
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
class Horde_ActiveSync_Imap_Strategy_Initial
extends Horde_ActiveSync_Imap_Strategy_Base
{
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
            '[%s] INITIAL SYNC',
             $this->_procid)
        );

        $query = new Horde_Imap_Client_Search_Query();
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

        if ($this->_status[Horde_ActiveSync_Folder_Imap::HIGHESTMODSEQ] &&
            !$this->_folder->haveInitialSync) {

            $this->_logger->info(sprintf(
                '[%s] Priming IMAP folder object.',
                $this->_procid)
            );
            $this->_folder->primeFolder($search_ret['match']->ids);
        } elseif (count($search_ret['match']->ids)) {
            // No modseq.
            $query = new Horde_Imap_Client_Fetch_Query();
            $query->flags();
            $cnt = ($search_ret['count'] / Horde_ActiveSync_Imap_Adapter::MAX_FETCH) + 1;
            $flags = array();
            for ($i = 0; $i <= $cnt; $i++) {
                $ids = new Horde_Imap_Client_Ids(
                    array_slice(
                        $search_ret['match']->ids,
                        $i * Horde_ActiveSync_Imap_Adapter::MAX_FETCH,
                        Horde_ActiveSync_Imap_Adapter::MAX_FETCH
                    )
                );
                $fetch_ret = $this->_imap_ob->fetch(
                    $this->_mbox,
                    $query,
                    array('ids' => $ids)
                );
                foreach ($fetch_ret as $uid => $data) {
                    $flags[$uid] = array(
                        'read' => (array_search(Horde_Imap_Client::FLAG_SEEN, $data->getFlags()) !== false) ? 1 : 0
                    );
                    if (($options['protocolversion']) > Horde_ActiveSync::VERSION_TWOFIVE) {
                        $flags[$uid]['flagged'] = (array_search(Horde_Imap_Client::FLAG_FLAGGED, $data->getFlags()) !== false) ? 1 : 0;
                    }
                }
            }
            $this->_folder->setChanges($search_ret['match']->ids, $flags);
        }

        return $this->_folder;
    }

}