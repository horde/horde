<?php
/**
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */

/**
 * Stub for testing the IMAP Socket ClientSort library.
 * Needed because we need to access protected methods.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @ignore
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_Stub_SocketClientSort extends Horde_Imap_Client_Socket_ClientSort
{
    public function clientSortProcess($res, $fetch_res, $sort)
    {
        return $this->_clientSortProcess($res, $fetch_res, $sort);
    }

}
