<?php
/**
 * Object representation of an IMAP mailbox string (RFC 3501 [9]).
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_Data_Format_Mailbox extends Horde_Imap_Client_Data_Format_Astring
{
    /**
     * @param mixed $data  Either a mailbox object or a UTF-8 mailbox name.
     */
    public function __construct($data)
    {
        $this->_data = Horde_Imap_Client_Mailbox::get($data);
    }

    /**
     */
    public function escape()
    {
        return $this->_escape(
            $this->_data->utf7imap,
            '/[\x00-\x1f\x7f\(\)\{\s%\*"\\\\]/'
        );
    }

    /**
     */
    public function requiresLiteral()
    {
        return $this->_requiresLiteral($this->_data->utf7imap);
    }

}
