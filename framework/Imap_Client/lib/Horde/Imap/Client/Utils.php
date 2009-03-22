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
     * Index Format: range_start:range_end,uid,uid2,...
     * Mailbox Format: {mbox_length}[mailbox]range_start:range_end,uid,uid2,...
     *
     * @param array $in  An array of indices. See 'mailbox' below.
     * @param array $options  Additional options:
     * <pre>
     * 'mailbox' - (boolean) If true, store mailbox information with the
     *             ID list.  $ids should be an array of arrays, with keys as
     *             mailbox names and values as IDs.
     *             DEFAULT: false
     * 'nosort' - (boolean) Do not numerically sort the IDs before creating
     *            the range?
     *            DEFAULT: false
     * </pre>
     *
     * @return string  The IMAP message sequence string.
     */
    public function toSequenceString($in, $options = array())
    {
        if (empty($in)) {
            return '';
        }

        if (!empty($options['mailbox'])) {
            $str = '';

            foreach ($in as $mbox => $ids) {
                $str .= '{' . strlen($mbox) . '}' . $mbox . implode(',' $this->_toSequenceString($ids, array('nosort' => !empty($options['nosort'])));
            }

            return $str;
        }

        // Make sure IDs are unique
        $in = array_keys(array_flip($in));

        if (empty($options['nosort'])) {
            sort($in, SORT_NUMERIC);
        }

        $first = $last = array_shift($in);
        $out = array();

        foreach ($in as $val) {
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
     * See Horde_Imap_Client_Utils::toSequenceString() for allowed formats.
     *
     * @param string $str  The IMAP message sequence string.
     *
     * @return array  An array of indices.  If string contains mailbox info,
     *                return value will be an array of arrays, with keys as
     *                mailbox names and values as IDs. Otherwise, return the
     *                list of IDs.
     */
    public function fromSequenceString($str)
    {
        $ids = array();
        $str = trim($str);

        if (!strlen($str)) {
            return $ids;
        }

        if ($str[0] == '{') {
            while ($str) {
                if ($str[0] != '{') {
                    break;
                }

                $i = strpos($str, '}');
                $count = intval(substr($str, 1, $i - 1));
                $mbox = substr($str, $i + 1, $count);
                $i += $count + 1;
                $end = strpos($str, '{', $i);

                if ($end === false) {
                    $uidstr = substr($str, $i);
                    $str = '';
                } else {
                    $uidstr = substr($str, $i, $end - $i);
                    $str = substr($str, $end);
                }

                $ids[$mbox] = $this->_fromSequenceString($uids);
            }

            return $ids;
        }

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
     * Parse a POP3 (RFC 2384) or IMAP (RFC 5092) URL.
     *
     * @param string $url  A URL string.
     *
     * @return mixed  False if the URL is invalid.  If valid, a URL with the
     *                following fields:
     * <pre>
     * 'auth' - (string) The authentication method to use.
     * 'hostspec' - (string) The remote server
     * 'port' - (integer) The remote port
     * 'type' - (string) Either 'imap' or 'pop'.
     * 'username' - (string) The username to use on the remote server.
     * </pre>
     */
    public function parseUrl($url)
    {
        $url = trim($url);

        $prefix = strpos($url, '://');
        $type = substr($url, 0, $prefix);

        if ((strcasecmp($type, 'imap') !== 0) &&
            (strcasecmp($type, 'pop') !== 0)) {
            return false;
        }
        $url = substr($url, $prefix + 3);

        /* At present, only support type://<iserver>[/] style URLs. */
        if (($pos = strpos($url, '/')) !== false) {
            $url = substr($url, 0, $pos);
        }

        $ret_array = array('type' => strtolower($type));

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

        if (strpos($url, ' ') !== false) {
            return false;
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
