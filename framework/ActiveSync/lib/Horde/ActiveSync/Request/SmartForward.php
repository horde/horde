<?php
/**
 * Handle SmartForward requests.
 * 
 * Logic adapted from Z-Push, original copyright notices below.
 *
 * Copyright 2009 - 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
/**
 * Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL v2.
 * Consult LICENSE file for details
 */
class Horde_ActiveSync_Request_SmartForward extends Horde_ActiveSync_Request_Base
{
    /**
     * Handle request
     *
     * @return boolean
     */
    public function handle()
    {
        // SmartForward is a normal 'send' except that you should attach the
        // original message which is specified in the URL

        $rfc822 = $this->readStream();

        if (isset($_GET["ItemId"])) {
            $orig = $_GET["ItemId"];
        } else {
            $orig = false;
        }
        if (isset($_GET["CollectionId"])) {
            $parent = $_GET["CollectionId"];
        } else {
            $parent = false;
        }

        return $this->_driver->sendMail($rfc822, $orig, false, $parent);
    }
}