<?php
/**
 * Utility functions for the Horde IMAP client.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * getBaseSubject() code adapted from imap-base-subject.c (Dovecot 1.2)
 *   Original code released under the LGPL-2.0.1
 *   Copyright (c) 2002-2008 Timo Sirainen <tss@iki.fi>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_Utils
{
    /**
     * Return the "base subject" defined in RFC 5256 [2.1].
     *
     * @param string $str     The original subject string.
     * @param array $options  Additional options:
     *   - keepblob: (boolean) Don't remove any "blob" information (i.e. text
     *               leading text between square brackets) from string.
     *
     * @return string  The cleaned up subject string.
     */
    public function getBaseSubject($str, $options = array())
    {
        // Rule 1a: MIME decode.
        $str = Horde_Mime::decode($str);

        // Rule 1b: Remove superfluous whitespace.
        $str = preg_replace("/[\t\r\n ]+/", ' ', $str);

        if (!strlen($str)) {
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
     * Parse a POP3 (RFC 2384) or IMAP (RFC 5092/5593) URL.
     *
     * Absolute IMAP URLs takes one of the following forms:
     *   - imap://<iserver>[/]
     *   - imap://<iserver>/<enc-mailbox>[<uidvalidity>][?<enc-search>]
     *   - imap://<iserver>/<enc-mailbox>[<uidvalidity>]<iuid>[<isection>][<ipartial>][<iurlauth>]
     *
     * POP URLs take one of the following forms:
     *   - pop://<user>;auth=<auth>@<host>:<port>
     *
     * @param string $url  A URL string.
     *
     * @return mixed  False if the URL is invalid.  If valid, an array with
     *                the following fields:
     *   - auth: (string) The authentication method to use.
     *   - hostspec: (string) The remote server. (Not present for relative
     *               URLs).
     *   - mailbox: (string) The IMAP mailbox.
     *   - partial: (string) A byte range for use with IMAP FETCH.
     *   - port: (integer) The remote port. (Not present for relative URLs).
     *   - relative: (boolean) True if this is a relative URL.
     *   - search: (string) A search query to be run with IMAP SEARCH.
     *   - section: (string) A MIME part ID.
     *   - type: (string) Either 'imap' or 'pop'. (Not present for relative
     *           URLs).
     *   - username: (string) The username to use on the remote server.
     *   - uid: (string) The IMAP UID.
     *   - uidvalidity: (integer) The IMAP UIDVALIDITY for the given mailbox.
     *   - urlauth: (string) URLAUTH info (not parsed).
     */
    public function parseUrl($url)
    {
        $data = parse_url(trim($url));

        if (isset($data['scheme'])) {
            $type = strtolower($data['scheme']);
            if (!in_array($type, array('imap', 'pop'))) {
                return false;
            }
            $relative = false;
        } else {
            $type = null;
            $relative = true;
        }

        $ret_array = array(
            'hostspec' => isset($data['host']) ? $data['host'] : null,
            'port' => isset($data['port']) ? $data['port'] : 143,
            'relative' => $relative,
            'type' => $type
        );

        /* Check for username/auth information. */
        if (isset($data['user'])) {
            if (($pos = stripos($data['user'], ';AUTH=')) !== false) {
                $auth = substr($data['user'], $pos + 6);
                if ($auth != '*') {
                    $ret_array['auth'] = $auth;
                }
                $data['user'] = substr($data['user'], 0, $pos);
            }

            if (strlen($data['user'])) {
                $ret_array['username'] = $data['user'];
            }
        }

        /* IMAP-only information. */
        if (!$type || ($type == 'imap')) {
            if (isset($data['path'])) {
                $data['path'] = ltrim($data['path'], '/');
                $parts = explode('/;', $data['path']);

                $mbox = array_shift($parts);
                if (($pos = stripos($mbox, ';UIDVALIDITY=')) !== false) {
                    $ret_array['uidvalidity'] = substr($mbox, $pos + 13);
                    $mbox = substr($mbox, 0, $pos);
                }
                $ret_array['mailbox'] = urldecode($mbox);

            }

            if (count($parts)) {
                foreach ($parts as $val) {
                    list($k, $v) = explode('=', $val);
                    $ret_array[strtolower($k)] = $v;
                }
            } elseif (isset($data['query'])) {
                $ret_array['search'] = urldecode($data['query']);
            }
        }

        return $ret_array;
    }

    /**
     * Create a POP3 (RFC 2384) or IMAP (RFC 5092/5593) URL.
     *
     * @param array $data  The data used to create the URL. See the return
     *                     value from parseUrl() for the available fields.
     *
     * @return string  A URL string.
     */
    public function createUrl($data)
    {
        $url = '';

        if (isset($data['type'])) {
            $url = $data['type'] . '://';

            if (isset($data['username'])) {
                $url .= $data['username'];
            }

            if (isset($data['auth'])) {
                $url .= ';AUTH=' . $data['auth'] . '@';
            } elseif (isset($data['username'])) {
                $url .= '@';
            }

            $url .= $data['hostspec'];

            if (isset($data['port']) && ($data['port'] != 143)) {
                $url .= ':' . $data['port'];
            }
        }

        $url .= '/';

        if (!isset($data['type']) || ($data['type'] == 'imap')) {
            $url .= urlencode($data['mailbox']);

            if (!empty($data['uidvalidity'])) {
                $url .= ';UIDVALIDITY=' . $data['uidvalidity'];
            }

            if (isset($data['search'])) {
                $url .= '?' . urlencode($data['search']);
            } else {
                if (isset($data['uid'])) {
                    $url .= '/;UID=' . $data['uid'];
                }

                if (isset($data['section'])) {
                    $url .= '/;SECTION=' . $data['section'];
                }

                if (isset($data['partial'])) {
                    $url .= '/;PARTIAL=' . $data['partial'];
                }

                if (isset($data['urlauth'])) {
                    $url .= '/;URLAUTH=' . $data['urlauth'];
                }
            }
        }


        return $url;
    }

    /* Internal methods. */

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

        if (!strlen($str)) {
            return $ret;
        }

        if ($len = strspn($str, " \t")) {
            $str = substr($str, $len);
            $ret = true;
        }

        $i = 0;

        if (!$keepblob) {
            while (isset($str[$i]) && ($str[$i] == '[')) {
                if (($i = $this->_removeBlob($str, $i)) === false) {
                    return $ret;
                }
            }
        }

        if (stripos($str, 're', $i) === 0) {
            $i += 2;
        } elseif (stripos($str, 'fwd', $i) === 0) {
            $i += 3;
        } elseif (stripos($str, 'fw', $i) === 0) {
            $i += 2;
        } else {
            return $ret;
        }

        $i += strspn($str, " \t", $i);

        if (!$keepblob) {
            while (isset($str[$i]) && ($str[$i] == '[')) {
                if (($i = $this->_removeBlob($str, $i)) === false) {
                    return $ret;
                }
            }
        }

        if (!isset($str[$i]) || ($str[$i] != ':')) {
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
