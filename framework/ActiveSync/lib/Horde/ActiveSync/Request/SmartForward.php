<?php
/**
 * Handler for SmartForward requests.
 *
 * Some code adapted from the Z-Push project. Original file header below.
 * File      :   diffbackend.php
 * Project   :   Z-Push
 * Descr     :   We do a standard differential
 *               change detection by sorting both
 *               lists of items by their unique id,
 *               and then traversing both arrays
 *               of items at once. Changes can be
 *               detected by comparing items at
 *               the same position in both arrays.
 *
 *  Created   :   01.10.2007
 *
 * © Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL-2.0.
 * Consult COPYING file for details
 *
 * @copyright 2009-2012 Horde LLC (http://www.horde.org/)
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
/**
 * ActiveSync Handler for SmartForward requests. The device only sends the reply
 * text, along with the message uid and collection id (mailbox). The server is
 * responsible for appending the original text.
 *
 * @copyright 2009-2012 Horde LLC (http://www.horde.org/)
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
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