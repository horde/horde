<?php
/**
 * Object representation of an IMAP nstring (NIL or string) (RFC 3501 [4.5]).
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
class Horde_Imap_Client_Data_Format_Nstring extends Horde_Imap_Client_Data_Format_String
{
    /**
     */
    public function __construct($data = null)
    {
        parent::__construct($data);
    }

    /**
     */
    public function __toString()
    {
        return strval($this->_data);
    }

    /**
     */
    public function escape()
    {
        return strlen($this->_data)
            ? $this->_escape(true)
            : 'NIL';
    }

}
