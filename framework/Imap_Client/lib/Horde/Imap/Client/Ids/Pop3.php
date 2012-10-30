<?php
/**
 * Wrapper around Ids object that correctly handles POP3 UID strings.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_Ids_Pop3 extends Horde_Imap_Client_Ids
{
    /**
     * Create a POP3 message sequence string.
     *
     * Index Format: UID1[SPACE]UID2...
     *
     * @param boolean $sort  Not used in this class.
     *
     * @return string  The POP3 message sequence string.
     */
    protected function _toSequenceString($sort = true)
    {
        /* Use space as delimiter as it is the only printable ASCII character
         * that is not allowed as part of the UID (RFC 1939 [7]). */
        return implode(' ', array_keys(array_flip($this->_ids)));
    }

    /**
     * Parse a POP3 message sequence string into a list of indices.
     *
     * @param string $str  The POP3 message sequence string.
     *
     * @return array  An array of UIDs.
     */
    protected function _fromSequenceString($str)
    {
        return explode(' ', trim($str));
    }

}
