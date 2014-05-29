<?php
/**
 * Horde_ActiveSync_Request_Ping::
 *
 * Portions of this class were ported from the Z-Push project:
 *   File      :   wbxml.php
 *   Project   :   Z-Push
 *   Descr     :   WBXML mapping file
 *
 *   Created   :   01.10.2007
 *
 *   � Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Handle PING requests.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @internal
 */
class Horde_ActiveSync_Request_Ping extends Horde_ActiveSync_Request_Base
{
    /* Status Constants */
    const STATUS_NOCHANGES      = 1;
    const STATUS_NEEDSYNC       = 2;
    const STATUS_MISSING        = 3;
    const STATUS_PROTERROR      = 4;
    const STATUS_HBOUTOFBOUNDS  = 5;
    const STATUS_MAXFOLDERS     = 6;
    const STATUS_FOLDERSYNCREQD = 7;
    const STATUS_SERVERERROR    = 8;

    /* PING Wbxml entities */
    const PING              = 'Ping:Ping';
    const STATUS            = 'Ping:Status';
    const HEARTBEATINTERVAL = 'Ping:HeartbeatInterval';
    const FOLDERS           = 'Ping:Folders';
    const FOLDER            = 'Ping:Folder';
    const SERVERENTRYID     = 'Ping:ServerEntryId';
    const FOLDERTYPE        = 'Ping:FolderType';
    const MAXFOLDERS        = 'Ping:MaxFolders';
    const VERSION           = 'Ping:Version';

    /**
     * The device's PING configuration (obtained from state)
     *
     * @var array
     */
    protected $_pingSettings;

    /**
     * Validate the configured/requested heartbeat
     * Will set self::_statusCode appropriately in case of an invalid interval.
     *
     * @param integer $lifetime  The heartbeat to verify
     *
     * @return integer  The valid heartbeat value to use.
     */
    protected function _checkHeartbeat($lifetime)
    {
        if (!empty($this->_pingSettings['forcedheartbeat'])) {
            return $this->_pingSettings['forcedheartbeat'];
        }
        if ($lifetime !== 0 && $lifetime < $this->_pingSettings['heartbeatmin']) {
            $this->_statusCode = self::STATUS_HBOUTOFBOUNDS;
            $lifetime = $this->_pingSettings['heartbeatmin'];
        } elseif ($lifetime > $this->_pingSettings['heartbeatmax']) {
            $this->_statusCode = self::STATUS_HBOUTOFBOUNDS;
            $lifetime = $this->_pingSettings['heartbeatmax'];
        }

        return $lifetime;
    }

    /**
     * Handle a PING command from the PIM. PING is sent periodically by the PIM
     * to tell the server what folders we are interested in monitoring for
     * changes. If no changes are detected by the server during the 'heartbeat'
     * interval, the server sends back a status of self::STATUS_NOCHANGES to
     * indicate heartbeat expired and the client should re-issue the PING
     * command. If a change has been found, the client is sent a
     * self::STATUS_NEEDSYNC and should issue a SYNC command.
     *
     * @return boolean
     */
    protected function _handle()
    {
        $now = time();
        $forceCacheSave = false;
        $this->_logger->info(sprintf(
            '[%s] Handling PING command received at timestamp: %s.',
            $this->_procid,
            $now));

        // Check global errors.
        if ($error = $this->_activeSync->checkGlobalError()) {
            $this->_statusCode = $error;
            $this->_handleGlobalError();
            return true;
        }

        // Initialize the collections handler.
        try {
            $collections = $this->_activeSync->getCollectionsObject();
        } catch (Horde_ActiveSync_Exception $e) {
            $this->_status = self::STATUS_SERVERERROR;
            $this->_handleGlobalError();
            return true;
        }

        // Get the current ping settings.
        $this->_pingSettings = $this->_driver->getHeartbeatConfig();
        $interval = $this->_pingSettings['waitinterval'];
        if (!$heartbeat = $collections->getHeartbeat()) {
            $heartbeat = !empty($this->_pingSettings['heartbeatdefault'])
                ? $this->_pingSettings['heartbeatdefault']
                : 60;
            $this->_logger->info(sprintf(
                '[%s] Cached heartbeat is %s',
                $this->_procid,
                $heartbeat));
        }
        $this->_statusCode = self::STATUS_NOCHANGES;

        // Either handle the empty request or decode a full request.
        if (!$this->_decoder->getElementStartTag(self::PING)) {
            $this->_logger->info(sprintf(
                '[%s] Empty PING request.',
                $this->_procid));
            $isEmpty = true;
            $collections->loadCollectionsFromCache();
            if ($collections->collectionCount() == 0 ||
                !$collections->havePingableCollections()) {
                $this->_logger->warn(sprintf(
                    '[%s] Empty PING request with no cached collections. Request full PING.',
                    $this->_procid));
                $this->_statusCode = self::STATUS_MISSING;
                $this->_handleGlobalError();
                return true;
            }
        } else {
            $isEmpty = false;
            if ($this->_decoder->getElementStartTag(self::HEARTBEATINTERVAL)) {
                if (!$heartbeat = $this->_checkHeartbeat($this->_decoder->getElementContent())) {
                    $heartbeat = $this->_pingSettings['heartbeatdefault'];
                }
                $collections->setHeartbeat(array('hbinterval' => $heartbeat));
                $forceCacheSave = true;
                $this->_decoder->getElementEndTag();
            }
            $this->_logger->info(sprintf(
                '[%s] Actual heartbeat value in use is %s.',
                $this->_procid, $heartbeat));
            if ($this->_decoder->getElementStartTag(self::FOLDERS)) {
                while ($this->_decoder->getElementStartTag(self::FOLDER)) {
                    $collection = array();
                    if ($this->_decoder->getElementStartTag(self::SERVERENTRYID)) {
                        $collection['id'] = $this->_decoder->getElementContent();
                        $this->_decoder->getElementEndTag();
                    }
                    if ($this->_decoder->getElementStartTag(self::FOLDERTYPE)) {
                        $collection['class'] = $this->_decoder->getElementContent();
                        $this->_decoder->getElementEndTag();
                    }
                    $this->_decoder->getElementEndTag();

                    try {
                        // Explicitly asked for a collection, make sure we have
                        // a key, but silently ignore the collection if we don't
                        // Otherwise, this can set up a PING loop in broken
                        // iOS clients that request collections in PING before
                        // they issue an initial SYNC for them.
                        $collections->addCollection($collection, true);
                    } catch (Horde_ActiveSync_Exception_StateGone $e) {
                    }
                }

                // Since PING sends all or none (no PARTIAL) we update the
                // pingable flags so we have it for an empty PING.
                $collections->validateFromCache();
                $collections->updatePingableFlag();

                if (!$this->_decoder->getElementEndTag()) {
                    throw new Horde_ActiveSync_Exception('Protocol Error');
                }
            } else {
                // No FOLDERS supplied, use the cache.
                $collections->loadCollectionsFromCache();
                if ($collections->collectionCount() == 0) {
                    $this->_logger->warn(sprintf(
                        '[%s] Empty PING request with no cached collections. Request full PING.',
                        $this->_procid));
                    $this->_statusCode = self::STATUS_MISSING;
                    $this->_handleGlobalError();
                    return true;
                }
            }
            if (!$this->_decoder->getElementEndTag()) {
                throw new Horde_ActiveSync_Exception('Protocol Error');
            }
        }

        // Start waiting for changes, but only if we don't have any errors
        if ($this->_statusCode == self::STATUS_NOCHANGES) {
            $changes = $collections->pollForChanges($heartbeat, $interval, array('pingable' => true));
            if ($changes !== true && $changes !== false) {
                // If we received a status indicating we need to issue a full
                // PING, but we already did, treat it as a status_needsync.
                if (!$isEmpty && $changes == Horde_ActiveSync_Collections::COLLECTION_ERR_PING_NEED_FULL) {
                    $changes = Horde_ActiveSync_Collections::COLLECTION_ERR_SYNC_REQUIRED;
                }
                switch ($changes) {
                case Horde_ActiveSync_Collections::COLLECTION_ERR_PING_NEED_FULL:
                    $this->_statusCode = self::STATUS_MISSING;
                    $this->_handleGlobalError();
                    return true;
                case Horde_ActiveSync_Collections::COLLECTION_ERR_STALE:
                    $this->_logger->info(sprintf(
                        '[%s] Changes in cache detected during PING, exiting here.',
                        $this->_procid));
                    return true;
                case Horde_ActiveSync_Collections::COLLECTION_ERR_FOLDERSYNC_REQUIRED;
                    $this->_statusCode = self::STATUS_FOLDERSYNCREQD;
                    $this->_handleGlobalError();
                    return true;
                case Horde_ActiveSync_Collections::COLLECTION_ERR_SYNC_REQUIRED;
                    $this->_statusCode = self::STATUS_NEEDSYNC;
                    break;
                default:
                    if ($this->_device->version < Horde_ActiveSync::VERSION_FOURTEEN) {
                        $this->_logger->warn(sprintf(
                            '[%s] Version is < 14.0, returning false since we have no PINGABLE collections.',
                            $this->_procid));
                        return false;
                    } else {
                        $this->_logger->warn(sprintf(
                            '[%s] Version is >= 14.0 returning status code 132 since we have no PINGABLE collections.',
                            $this->_procid));
                        $this->_statusCode = self::STATUS_NEEDSYNC;
                        $this->_handleGlobalError();
                        return true;
                    }
                }
            } elseif ($changes) {
                $collections->save();
                $this->_statusCode = self::STATUS_NEEDSYNC;
            } elseif ($forceCacheSave) {
                $collections->save();
            }
        }

        // Send response
        $this->_encoder->StartWBXML();
        $this->_encoder->startTag(self::PING);
        $this->_encoder->startTag(self::STATUS);
        $this->_encoder->content($this->_statusCode);
        $this->_encoder->endTag();
        if ($this->_statusCode == self::STATUS_HBOUTOFBOUNDS) {
            $this->_encoder->startTag(self::HEARTBEATINTERVAL);
            $this->_encoder->content($heartbeat);
            $this->_encoder->endTag();
        } elseif ($collections->collectionCount() && $this->_statusCode != self::STATUS_NOCHANGES) {
            $this->_encoder->startTag(self::FOLDERS);
            foreach ($collections as $id => $collection) {
                if ($collections->getChangesFlag($id)) {
                    $this->_encoder->startTag(self::FOLDER);
                    $this->_encoder->content($id);
                    $this->_encoder->endTag();
                }
            }
            $this->_encoder->endTag();
        }
        $this->_encoder->endTag();

        return true;
    }

    /**
     * Helper for sending error status results.
     *
     * @param boolean $limit  Send the SYNC_LIMIT error if true.
     */
    protected function _handleGlobalError()
    {
        $this->_encoder->StartWBXML();
        $this->_encoder->startTag(self::PING);
        $this->_encoder->startTag(self::STATUS);
        $this->_encoder->content($this->_statusCode);
        $this->_encoder->endTag();
        $this->_encoder->endTag();
    }

}
