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
 * Horde_ActiveSync_Imap_Adapter_Modseq handles fetching changes for servers
 * that do NOT support CONDSTORE/QRESYNC.
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
class Horde_ActiveSync_Imap_Strategy_Plain extends Horde_ActiveSync_Imap_Strategy_Base
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
            '[%s] NO CONDSTORE or per mailbox MODSEQ. minuid: %s, total_messages: %s',
            $this->_procid, $this->_folder->minuid(), $this->_status['messages'])
        );

        $query = new Horde_Imap_Client_Search_Query();
        if (!empty($options['sincedate'])) {
            $query->dateSearch(
                new Horde_Date($options['sincedate']),
                Horde_Imap_Client_Search_Query::DATE_SINCE
            );
        }

        try {
            $search_ret = $this->_imap_ob->search(
                $this->_mbox,
                $query,
                array('results' => array(Horde_Imap_Client::SEARCH_RESULTS_MATCH))
            );
        } catch (Horde_Imap_Client_Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }

        $cnt = ($search_ret['count'] / Horde_ActiveSync_Imap_Adapter::MAX_FETCH) + 1;
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->flags();
        for ($i = 0; $i <= $cnt; $i++) {
            $ids = new Horde_Imap_Client_Ids(
                array_slice(
                    $search_ret['match']->ids,
                    $i * Horde_ActiveSync_Imap_Adapter::MAX_FETCH,
                    Horde_ActiveSync_Imap_Adapter::MAX_FETCH
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
            foreach ($fetch_ret as $uid => $data) {
                $flags[$uid] = array(
                    'read' => (array_search(Horde_Imap_Client::FLAG_SEEN, $data->getFlags()) !== false) ? 1 : 0
                );
                if (($options['protocolversion']) > Horde_ActiveSync::VERSION_TWOFIVE) {
                    $flags[$uid]['flagged'] = (array_search(Horde_Imap_Client::FLAG_FLAGGED, $data->getFlags()) !== false) ? 1 : 0;
                }
            }
        }
        if (!empty($flags)) {
            $this->_folder->setChanges($search_ret['match']->ids, $flags);
        }
        $this->_folder->setRemoved(
            $this->_imap_ob->vanished($this->_mbox, null, array('ids' => new Horde_Imap_Client_Ids($this->_folder->messages())))->ids
        );

        return $this->_folder;
    }

}