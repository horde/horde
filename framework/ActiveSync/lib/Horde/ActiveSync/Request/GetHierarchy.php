<?php
/**
 * Handle GetHierarchy requests from older activesync clients.
 * 
 * Logic adapted from Z-Push, original copyright notices below.
 *
 * Copyright 2009 - 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
/**
 * Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL v2.
 * Consult LICENSE file for details
 */
class Horde_ActiveSync_Request_GetHierarchy extends Horde_ActiveSync_Request_Base
{
    /**
     * Handle request
     *
     * @return boolean
     */
    public function handle()
    {
        $folders = $this->_driver->getHierarchy();
        if (!$folders) {
            return false;
        }

        /* save folder-ids for fourther syncing */
        $this->_stateMachine->setFolderData($this->_device, $folders);

        $this->_encoder->StartWBXML();
        $this->_encoder->startTag(self::FOLDERHIERARCHY_FOLDERS);

        foreach ($folders as $folder) {
            $this->_encoder->startTag(self::FOLDERHIERARCHY_FOLDER);
            $folder->encodeStream($this->_encoder);
            $this->_encoder->endTag();
        }
        $this->_encoder->endTag();

        return true;
    }
}