<?php
/**
 * Object representation of an IMAP atom (RFC 3501 [4.1]).
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
class Horde_Imap_Client_Data_Format_Atom extends Horde_Imap_Client_Data_Format
{
    /**
     */
    public function escape()
    {
        return strlen($this->_data)
            ? parent::escape()
            : '""';
    }

    /**
     */
    public function verify()
    {
        if (strlen($this->_data) != strlen($this->stripNonAtomCharacters())) {
            throw new Horde_Imap_Client_Data_Format_Exception('Illegal character in IMAP atom.');
        }
    }

    /**
     * Strip out any characters that are not allowed in an IMAP atom.
     *
     * @return string  The atom data disallowed characters removed.
     */
    public function stripNonAtomCharacters()
    {
        return str_replace(array('(', ')', '{', ' ', '%', '*', '"', '\\', ']'), '', preg_replace('/[\x00-\x1f\x7f]/', '', $this->_data));
    }

}
