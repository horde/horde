<?php
/**
 * Object representation of an IMAP mailbox string used in a LIST command.
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
class Horde_Imap_Client_Data_Format_ListMailbox extends Horde_Imap_Client_Data_Format_Mailbox
{
    /**
     */
    public function escape()
    {
        /* Sane as astring, but don't escape % or * characters. */
        return $this->_escape(
            $this->_data->utf7imap,
            '/[\x00-\x1f\x7f\(\)\{\s"\\\\]/'
        );
    }

}
