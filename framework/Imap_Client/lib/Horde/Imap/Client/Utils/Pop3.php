<?php
/**
 * Utility functions for the Horde IMAP client - POP3 specific methods.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_Utils_Pop3 extends Horde_Imap_Client_Utils
{
    /**
     * Create a POP3 message sequence string from a list of UIDs.
     *
     * Index Format: {P[length]}UID1{P[length]}UID2...
     *
     * @param mixed $in       An array of UIDs (or a single UID).
     * @param array $options  Additional options. 'nosort' is not used in
     *                        this class.
     *
     * @return string  The POP3 message sequence string.
     */
    public function toSequenceString($in, $options = array())
    {
        if (!is_array($in)) {
            if (!strlen($in)) {
                return '';
            }
            $in = array($in);
        } elseif (!empty($options['mailbox'])) {
            $tmp = $in;
            $in = array();
            foreach ($tmp as $val) {
                $in = array_merge($in, $val);
            }
        }

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
    public function fromSequenceString($str)
    {
        $ids = array();
        $str = trim($str);

        while (strlen($str)) {
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
