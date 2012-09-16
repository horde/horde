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
    public function __construct($data)
    {
        /* Store resource streams as-is (don't convert to string). */
        $this->_data = $data;
    }

    /**
     */
    public function escape()
    {
        /* IMAP strings MUST be quoted. */
        return $this->_escape($this->_data);
    }

    /**
     * Escape output via an IMAP quoted string (see RFC 3501 [4.3]). Note that
     * IMAP quoted strings support 7-bit characters only and can not contain
     * either CR or LF.
     *
     * @param mixed $data    Data to quote.
     * @param string $quote  If present, a regex that, if matched, will cause
     *                       string to be quoted.
     *
     * @return string  The escaped string.
     * @throws Horde_Imap_Client_Data_Format_Exception
     */
    protected function _escape($data, $quote = null)
    {
        if ($this->_requiresLiteral($data)) {
            throw new Horde_Imap_Client_Data_Format_Exception('String requires literal to output.');
        }

        if (!strlen($data)) {
            return '""';
        }

        $newstr = addcslashes($data, '"\\');

        return (!is_null($quote) && !preg_match($quote, $data) && ($data == $newstr))
            ? $data
            : '"' . $newstr . '"';
    }


    /**
     * Does this data item require literal string output?
     *
     * @return boolean  Does data require literal output?
     */
    public function requiresLiteral()
    {
        return $this->_requiresLiteral($this->_data);
    }

    /**
     * Does this data item require literal string output?
     *
     * @param mixed $data  Data to test.
     *
     * @return boolean  Does data require literal output?
     */
    protected function _requiresLiteral($data)
    {
        return (is_resource($data) ||
                (bool) preg_match('/[\x80-\xff\n\r]/', $data));
    }

}
