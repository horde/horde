<?php
/**
 * Handle SmartForward requests.
 *
 * Logic adapted from Z-Push, original copyright notices below.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
/**
 * Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL-2.0.
 * Consult COPYING file for details
 */
class Horde_ActiveSync_Request_SmartForward extends Horde_ActiveSync_Request_Base
{
    /**
     * Handle request
     *
     * @return boolean
     */
    protected function _handle()
    {
        $rfc822 = file_get_contents('php://input');
        $get = $this->_request->getGetVars();
        if (empty($get['ItemId'])) {
            $orig = false;
        } else {
            $orig = $get['ItemId'];
        }
        if (empty($get['CollectionId'])) {
            $parent = false;
        } else {
            $parent = $get['CollectionId'];
        }

        return $this->_driver->sendMail($rfc822, $orig, false, $parent);
    }
}