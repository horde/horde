<?php
/**
 * Function to sort a list of IMAP mailboxes.
 *
 * Copyright 2004-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_Sort
{
    /**
     * The delimiter character to use.
     *
     * @var string
     */
    private static $_delimiter = '.';

    /**
     * Should we sort with INBOX at the front of the list?
     *
     * @var boolean
     */
    private static $_sortinbox = true;

    /**
     * Sort a list of mailboxes.
     * $mbox will be sorted after running this function.
     *
     * @param array &$mbox    The list of mailboxes to sort.
     * @param array $options  Additional options:
     *   - delimiter: (string) The delimiter to use.
     *                DEFAULT: '.'
     *   - inbox: (boolean) Always put INBOX at the head of the list?
     *            DEFAULT: Yes
     *   - index: (boolean) If sorting by value ('keysort' is false), maintain
     *            key index association?
     *            DEFAULT: No
     *   - keysort: (boolean) Sort by $mbox's keys?
     *              DEFAULT: Sort by $mbox values.
     */
    public static final function sortMailboxes(&$mbox, $options = array())
    {
        if (isset($options['delimiter'])) {
            self::$_delimiter = $options['delimiter'];
        }

        if (isset($options['inbox']) && empty($options['inbox'])) {
            self::$_sortinbox = false;
        }

        $cmp = array('Horde_Imap_Client_Sort', 'mboxCompare');
        if (!empty($options['keysort'])) {
            uksort($mbox, $cmp);
        } elseif (!empty($options['index'])) {
            uasort($mbox, $cmp);
        } else {
            usort($mbox, $cmp);
        }
    }

    /**
     * Hierarchical folder sorting function (used with usort()).
     *
     * @param string $a  Comparison item 1.
     * @param string $b  Comparison item 2.
     *
     * @return integer  See usort().
     */
    public static final function mboxCompare($a, $b)
    {
        /* Always return INBOX as "smaller". */
        if (self::$_sortinbox) {
            if (strcasecmp($a, 'INBOX') == 0) {
                return -1;
            } elseif (strcasecmp($b, 'INBOX') == 0) {
                return 1;
            }
        }

        $a_parts = explode(self::$_delimiter, $a);
        $b_parts = explode(self::$_delimiter, $b);

        $a_count = count($a_parts);
        $b_count = count($b_parts);

        for ($i = 0, $iMax = min($a_count, $b_count); $i < $iMax; ++$i) {
            if ($a_parts[$i] != $b_parts[$i]) {
                /* If only one of the folders is under INBOX, return it as
                 * "smaller". */
                if (self::$_sortinbox && ($i == 0)) {
                    $a_base = (strcasecmp($a_parts[0], 'INBOX') == 0);
                    $b_base = (strcasecmp($b_parts[0], 'INBOX') == 0);
                    if ($a_base && !$b_base) {
                        return -1;
                    } elseif (!$a_base && $b_base) {
                        return 1;
                    }
                }
                $cmp = strnatcasecmp($a_parts[$i], $b_parts[$i]);
                return ($cmp == 0) ? strcmp($a_parts[$i], $b_parts[$i]) : $cmp;
            }
        }

        return ($a_count - $b_count);
    }
}
