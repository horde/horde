<?php
/**
 * POP3 fetch data.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Imap_Client
 */
class Horde_Imap_Client_Data_Fetch_Pop3 extends Horde_Imap_Client_Data_Fetch
{
    /**
     * Set UID.
     *
     * @param string $uid  The message UID. Unlike IMAP, this UID does not
     *                     have to be an integer.
     */
    public function setUid($uid)
    {
        $this->_data[Horde_Imap_Client::FETCH_UID] = strval($uid);
    }

}
