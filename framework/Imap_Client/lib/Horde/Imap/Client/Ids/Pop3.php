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
     * Index Format: {P[length]}UID1{P[length]}UID2...
     *
     * @param boolean $sort  Not used in this class.
     *
     * @return string  The POP3 message sequence string.
     */
    protected function _toSequenceString($sort = true)
    {
        $in = $this->_ids;
        $str = '';

        // Make sure IDs are unique
        foreach (array_keys(array_flip($in)) as $val) {
            $str .= '{P' . strlen($val) . '}' . $val;
        }

        return $str;
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
        $ids = array();
        $str = trim($str);

        while ($str != '') {
            /* Check for valid beginning of UID. */
            if (substr($str, 0, 2) != '{P') {
                /* Assume this is the entire UID, if there is no other
                 * data. Otherwise, ignore garbage data. */
                if (empty($ids)) {
                    $ids[] = $str;
                }
                break;
            }

            $i = strpos($str, '}', 2);
            $size = intval(substr($str, 2, $i - 2));
            $ids[] = substr($str, $i + 1, $size);

            $str = substr($str, $i + 1 + $size);
        }

        return $ids;
    }

}
