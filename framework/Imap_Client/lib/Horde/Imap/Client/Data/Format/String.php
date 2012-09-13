<?php
/**
 * Object representation of an IMAP string (RFC 3501 [4.3]).
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
class Horde_Imap_Client_Data_Format_String extends Horde_Imap_Client_Data_Format
{
    /**
     */
    public function escape()
    {
        /* IMAP strings MUST be quoted. */
        return $this->_escape(true);
    }

    /**
     * Escape output via an IMAP quoted string (see RFC 3501 [4.3]). Note that
     * IMAP quoted strings support 7-bit characters only and can not contain
     * either CR or LF.
     *
     * @param boolean $force  Always add quotes?
     * @param string $str     The string to escape instead of $this->_data.
     *
     * @return string  The escaped string.
     */
    protected function _escape($force = false, $str = null)
    {
        $str = is_null($str)
            ? $this->_data
            : $str;

        if (!strlen($str)) {
            return '""';
        }

        $newstr = addcslashes($str, '"\\');
        return (!$force && ($str == $newstr))
            ? $str
            : '"' . $newstr . '"';
    }

}
