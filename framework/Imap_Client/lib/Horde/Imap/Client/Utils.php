<?php
/**
 * Horde_Imap_Client_Utils provides utility functions for the Horde IMAP client.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * getBaseSubject() code adapted from imap-base-subject.c (Dovecot 1.2)
 *   Original code released under the LGPL v2.1
 *   Copyright (c) 2002-2008 Timo Sirainen <tss@iki.fi>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Horde_Imap_Client
 */
class Horde_Imap_Client_Utils
{
    /**
     * Create an IMAP message sequence string from a list of indices.
     * Format: range_start:range_end,uid,uid2,range2_start:range2_end,...
     *
     * @param array $in  An array of indices.
     * @param array $options  Additional options:
     * <pre>
     * 'nosort' - (boolean) Do not numerically sort the IDs before creating
     *            the range?
     *            DEFAULT: IDs are sorted
     * </pre>
     *
     * @return string  The IMAP message sequence string.
     */
    public function toSequenceString($ids, $options = array())
    {
        if (empty($ids)) {
            return '';
        }

        // Make sure IDs are unique
        $ids = array_keys(array_flip($ids));

        if (empty($options['nosort'])) {
            sort($ids, SORT_NUMERIC);
        }

        $first = $last = array_shift($ids);
        $out = array();

        foreach ($ids as $val) {
            if ($last + 1 == $val) {
                $last = $val;
            } else {
                $out[] = $first . ($last == $first ? '' : (':' . $last));
                $first = $last = $val;
            }
        }
        $out[] = $first . ($last == $first ? '' : (':' . $last));

        return implode(',', $out);
    }

    /**
     * Parse an IMAP message sequence string into a list of indices.
     * Format: range_start:range_end,uid,uid2,range2_start:range2_end,...
     *
     * @param string $str  The IMAP message sequence string.
     *
     * @return array  An array of indices.
     */
    public function fromSequenceString($str)
    {
        $ids = array();
        $str = trim($str);

        $idarray = explode(',', $str);
        if (empty($idarray)) {
            $idarray = array($str);
        }

        foreach ($idarray as $val) {
            $range = array_map('intval', explode(':', $val));
            if (count($range) == 1) {
                $ids[] = $val;
            } else {
                list($low, $high) = ($range[0] < $range[1]) ? $range : array_reverse($range);
                $ids = array_merge($ids, range($low, $high));
            }
        }

        return $ids;
    }

    /**
     * Remove "bare newlines" from a string.
     *
     * @param string $str  The original string.
     *
     * @return string  The string with all bare newlines removed.
     */
    public function removeBareNewlines($str)
    {
        return str_replace(array("\r\n", "\n"), array("\n", "\r\n"), $str);
    }

    /**
     * Escape IMAP output via a quoted string (see RFC 3501 [4.3]).
     *
     * @param string $str  The unescaped string.
     *
     * @return string  The escaped string.
     */
    public function escape($str)
    {
        return '"' . addcslashes($str, '"\\') . '"';
    }

    /**
     * Return the "base subject" defined in RFC 5256 [2.1].
     *
     * @param string $str     The original subject string.
     * @param array $options  Additional options:
     * <pre>
     * 'keepblob' - (boolean) Don't remove any "blob" information (i.e. text
     *              leading text between square brackets) from string.
     * </pre>
     *
     * @return string  The cleaned up subject string.
     */
    public function getBaseSubject($str, $options = array())
    {
        // Rule 1a: MIME decode to UTF-8 (if possible).
        $str = Horde_Mime::decode($str, 'UTF-8');

        // Rule 1b: Remove superfluous whitespace.
        $str = preg_replace("/\s{2,}/", '', $str);

        if (!$str) {
            return '';
        }

        do {
            /* (2) Remove all trailing text of the subject that matches the
             * the subj-trailer ABNF, repeat until no more matches are
             * possible. */
            $str = preg_replace("/(?:\s*\(fwd\)\s*)+$/i", '', $str);

            do {
                /* (3) Remove all prefix text of the subject that matches the
                 * subj-leader ABNF. */
                $found = $this->_removeSubjLeader($str, !empty($options['keepblob']));

                /* (4) If there is prefix text of the subject that matches
                 * the subj-blob ABNF, and removing that prefix leaves a
                 * non-empty subj-base, then remove the prefix text. */
                $found = (empty($options['keepblob']) && $this->_removeBlobWhenNonempty($str)) || $found;

                /* (5) Repeat (3) and (4) until no matches remain. */
            } while ($found);

            /* (6) If the resulting text begins with the subj-fwd-hdr ABNF and
             * ends with the subj-fwd-trl ABNF, remove the subj-fwd-hdr and
             * subj-fwd-trl and repeat from step (2). */
        } while ($this->_removeSubjFwdHdr($str));

        return $str;
    }

    /**
     * Parse an IMAP URL (RFC 5092).
     *
     * @param string $url  A IMAP URL string.
     *
     * @return mixed  False if the URL is invalid.  If valid, a URL with the
     *                following fields:
     * <pre>
     * 'auth' - (string) The authentication method to use.
     * 'port' - (integer) The remote port
     * 'hostspec' - (string) The remote server
     * 'username' - (string) The username to use on the remote server.
     * </pre>
     */
    public function parseImapUrl($url)
    {
        $url = trim($url);
        if (stripos($url, 'imap://') !== 0) {
            return false;
        }
        $url = substr($url, 7);

        /* At present, only support imap://<iserver>[/] style URLs. */
        if (($pos = strpos($url, '/')) !== false) {
            $url = substr($url, 0, $pos);
        }

        $ret_array = array();

        /* Check for username/auth information. */
        if (($pos = strpos($url, '@')) !== false) {
            if ((($apos = stripos($url, ';AUTH=')) !== false) &&
                ($apos < $pos)) {
                $auth = substr($url, $apos + 6, $pos - $apos - 6);
                if ($auth != '*') {
                    $ret_array['auth'] = $auth;
                }
                if ($apos) {
                    $ret_array['username'] = substr($url, 0, $apos);
                }
            }
            $url = substr($url, $pos + 1);
        }

        /* Check for port information. */
        if (($pos = strpos($url, ':')) !== false) {
            $ret_array['port'] = substr($url, $pos + 1);
            $url = substr($url, 0, $pos);
        }

        $ret_array['hostspec'] = $url;

        return $ret_array;
    }

    /**
     * Remove all prefix text of the subject that matches the subj-leader
     * ABNF.
     *
     * @param string &$str       The subject string.
     * @param boolean $keepblob  Remove blob information?
     *
     * @return boolean  True if string was altered.
     */
    protected function _removeSubjLeader(&$str, $keepblob = false)
    {
        $ret = false;

        if (!$str) {
            return $ret;
        }

        if ($str[0] == ' ') {
            $str = substr($str, 1);
            $ret = true;
        }

        $i = 0;

        if (!$keepblob) {
            while ($str[$i] == '[') {
                if (($i = $this->_removeBlob($str, $i)) === false) {
                    return $ret;
                }
            }
        }

        $cmp_str = substr($str, $i);
        if (stripos($cmp_str, 're') === 0) {
            $i += 2;
        } elseif (stripos($cmp_str, 'fwd') === 0) {
            $i += 3;
        } elseif (stripos($cmp_str, 'fw') === 0) {
            $i += 2;
        } else {
            return $ret;
        }

        if ($str[$i] == ' ') {
            ++$i;
        }

        if (!$keepblob) {
            while ($str[$i] == '[') {
                if (($i = $this->_removeBlob($str, $i)) === false) {
                    return $ret;
                }
            }
        }

        if ($str[$i] != ':') {
            return $ret;
        }

        $str = substr($str, ++$i);

        return true;
    }

    /**
     * Remove "[...]" text.
     *
     * @param string &$str  The subject string.
     *
     * @return boolean  True if string was altered.
     */
    protected function _removeBlob($str, $i)
    {
        if ($str[$i] != '[') {
            return false;
        }

        ++$i;

        for ($cnt = strlen($str); $i < $cnt; ++$i) {
            if ($str[$i] == ']') {
                break;
            }

            if ($str[$i] == '[') {
                return false;
            }
        }

        if ($i == ($cnt - 1)) {
            return false;
        }

        ++$i;

        if ($str[$i] == ' ') {
            ++$i;
        }

        return $i;
    }

    /**
     * Remove "[...]" text if it doesn't result in the subject becoming
     * empty.
     *
     * @param string &$str  The subject string.
     *
     * @return boolean  True if string was altered.
     */
    protected function _removeBlobWhenNonempty(&$str)
    {
        if ($str &&
            ($str[0] == '[') &&
            (($i = $this->_removeBlob($str, 0)) !== false) &&
            ($i != strlen($str))) {
            $str = substr($str, $i);
            return true;
        }

        return false;
    }

    /**
     * Remove a "[fwd: ... ]" string.
     *
     * @param string &$str  The subject string.
     *
     * @return boolean  True if string was altered.
     */
    protected function _removeSubjFwdHdr(&$str)
    {
        if ((stripos($str, '[fwd:') !== 0) || (substr($str, -1) != ']')) {
            return false;
        }

        $str = substr($str, 5, -1);
        return true;
    }

}
