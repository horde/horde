<?php
/**
 * Horde_ActiveSync_Connector_Exporter_FolderSync::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2017 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Connector_Exporter_FolderSync:: Responsible for outputing
 * blocks of WBXML responses in FOLDER_SYNC responses.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2017 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Connector_Exporter_FolderSync extends Horde_ActiveSync_Connector_Exporter_Base
{

    /**
     * Array of folder objects that have changed.
     * Used when exporting folder structure changes since they are not streamed
     * from this object.
     *
     * @var array
     */
    public $changed = array();

    /**
     * Array of folder ids that have been deleted on the server.
     *
     * @var array
     */
    public $deleted = array();

     /**
     * Tracks the total number of folder changes
     *
     * @var integer
     */
    public $count = 0;

    /**
     * Sends the next change in the set to the client.
     *
     * @return boolean|Horde_Exception True if more changes can be sent false if
     *                                 all changes were sent, Horde_Exception if
     *                                 there was an error sending an item.
     */
    public function sendNextChange()
    {
        return $this->_sendNextFolderSyncChange();
    }

        /**
     * Sends the next folder change to the client.
     *
     * @return @see self::sendNextChange()
     */
    protected function _sendNextFolderSyncChange()
    {
        if ($this->_step < count($this->_changes)) {
            $change = $this->_changes[$this->_step];
            switch($change['type']) {
            case Horde_ActiveSync::CHANGE_TYPE_CHANGE:
                // Folder add/change.
                if ($folder = $this->_as->driver->getFolder($change['serverid'])) {
                    // @TODO BC HACK. Need to ensure we have a _serverid here.
                    // REMOVE IN H6.
                    if (empty($folder->_serverid)) {
                        $folder->_serverid = $folder->serverid;
                    }
                    $stat = $this->_as->driver->statFolder(
                        $change['id'],
                        $folder->parentid,
                        $folder->displayname,
                        $folder->_serverid,
                        $folder->type);
                    $this->folderChange($folder);
                } else {
                    $this->_logger->err(sprintf(
                        '[%s] Error stating %s: ignoring.',
                        $this->_procid, $change['id']));
                    $stat = array('id' => $change['id'], 'mod' => $change['id'], 0);
                }
                // Update the state.
                $this->_as->state->updateState(
                    Horde_ActiveSync::CHANGE_TYPE_FOLDERSYNC, $stat);
                break;

            case Horde_ActiveSync::CHANGE_TYPE_DELETE:
                $this->folderDeletion($change['id']);
                $this->_as->state->updateState(
                    Horde_ActiveSync::CHANGE_TYPE_DELETE, $change);
                break;
            }
            $this->_step++;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Add a folder change to the cache (used during FolderSync requests).
     *
     * @param Horde_ActiveSync_Message_Folder $folder
     */
    public function folderChange(Horde_ActiveSync_Message_Folder $folder)
    {
        $this->changed[] = $folder;
        $this->count++;
    }

    /**
     * Add a folder deletion to the cache (used during FolderSync Requests).
     *
     * @param string $id  The folder id
     */
    public function folderDeletion($id)
    {
        $this->deleted[] = $id;
        $this->count++;
    }

}