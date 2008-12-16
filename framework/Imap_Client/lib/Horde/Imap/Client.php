<?php

require_once dirname(__FILE__) . '/Client/Base.php';
require_once dirname(__FILE__) . '/Client/Exception.php';
require_once dirname(__FILE__) . '/Client/Utf7imap.php';

/**
 * Horde_Imap_Client:: provides an abstracted API interface to various IMAP
 * backends (RFC 3501).

 * Required Parameters:
 *   password - (string) The IMAP user password.
 *   username - (string) The IMAP username.
 *
 * Optional Parameters:
 *   cache - (array) If set, caches data from fetch() calls. Requires
 *           Horde_Cache and Horde_Serialize to be installed. The array can
 *           contain the following keys (see Horde_Imap_Client_Cache:: for
 *           default values):
 * <pre>
 * 'compress' - [OPTIONAL] (string) Compression to use on the cached data.
 *              Either false, 'gzip' or 'lzf'.
 * 'driver' - [REQUIRED] (string) The Horde_Cache driver to use.
 * 'driver_params' - [REQUIRED] (array) The params to pass to the Horde_Cache
 *                   driver.
 * 'fields' - [OPTIONAL] (array) The fetch criteria to cache. If not defined,
 *            all cacheable data is cached. The following is a list of
 *            criteria that can be cached:
 * <pre>
 * Horde_Imap_Client::FETCH_STRUCTURE
 * Horde_Imap_Client::FETCH_ENVELOPE
 * Horde_Imap_Client::FETCH_FLAGS (only if server supports CONDSTORE IMAP
 *                                 extension)
 * Horde_Imap_Client::FETCH_DATE
 * Horde_Imap_Client::FETCH_SIZE
 * </pre>
 * 'lifetime' - [OPTIONAL] (integer) The lifetime of the cache data (in secs).
 * 'slicesize' - [OPTIONAL] (integer) The slicesize to use.
 * </pre>
 *   comparator - (string) The search comparator to use instead of the default
 *                IMAP server comparator. See setComparator() for the format.
 *                DEFAULT: Use the server default
 *   debug - (string) If set, will output debug information to the stream
 *           identified. The value can be any PHP supported wrapper that can
 *           be opened via fopen().
 *           DEFAULT: No debug output
 *   hostspec - (string) The hostname or IP address of the server.
 *              DEFAULT: 'localhost'
 *   id - (array) Send ID information to the IMAP server (only if server
 *        supports the ID extension). An array with the keys being the fields
 *        to send and the values being the associated values. See RFC 2971
 *        [3.3] for a list of defined field values.
 *        DEFAULT: No info sent to server
 *   lang - (array) A list of languages (in priority order) to be used to
 *          display human readable messages.
 *          DEFAULT: Messages output in IMAP server default language
 *   port - (integer) The server port to which we will connect.
 *           DEFAULT: 143 (imap or imap w/TLS) or 993 (imaps)
 *   secure - (string) Use SSL or TLS to connect.
 *            VALUES: false, 'ssl', 'tls'.
 *            DEFAULT: No encryption
 *   timeout - (integer)  Connection timeout, in seconds.
 *             DEFAULT: 10 seconds
 *
 * Copyright 2008 The Horde Project (http://www.horde.org/)
 *
 * getBaseSubject() code adapted from imap-base-subject.c (Dovecot 1.2)
 *   Original code released under the LGPL v2.1
 *   Copyright (c) 2002-2008 Timo Sirainen <tss@iki.fi>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @package  Horde_Imap_Client
 */
class Horde_Imap_Client
{
    /* Global constants. */
    const USE_SEARCHRES = '*SEARCHRES*';

    /* Constants for openMailbox() */
    const OPEN_READONLY = 1;
    const OPEN_READWRITE = 2;
    const OPEN_AUTO = 3;

    /* Constants for listMailboxes() */
    const MBOX_SUBSCRIBED = 1;
    const MBOX_UNSUBSCRIBED = 2;
    const MBOX_ALL = 3;

    /* Constants for status() */
    const STATUS_MESSAGES = 1;
    const STATUS_RECENT = 2;
    const STATUS_UIDNEXT = 4;
    const STATUS_UIDVALIDITY = 8;
    const STATUS_UNSEEN = 16;
    const STATUS_ALL = 32;
    const STATUS_FIRSTUNSEEN = 64;
    const STATUS_FLAGS = 128;
    const STATUS_PERMFLAGS = 256;
    const STATUS_HIGHESTMODSEQ = 512;
    const STATUS_UIDNOTSTICKY = 1024;

    /* Constants for search() */
    const SORT_ARRIVAL = 1;
    const SORT_CC = 2;
    const SORT_DATE = 3;
    const SORT_FROM = 4;
    const SORT_REVERSE = 5;
    const SORT_SIZE = 6;
    const SORT_SUBJECT = 7;
    const SORT_TO = 8;
    /* SORT_THREAD provided for completeness - it is not a valid sort criteria
     * for search() (use thread() instead). */
    const SORT_THREAD = 9;

    const SORT_RESULTS_COUNT = 1;
    const SORT_RESULTS_MATCH = 2;
    const SORT_RESULTS_MAX = 3;
    const SORT_RESULTS_MIN = 4;
    const SORT_RESULTS_SAVE = 5;

    /* Constants for thread() */
    const THREAD_ORDEREDSUBJECT = 1;
    const THREAD_REFERENCES = 2;

    /* Constants for fetch() */
    const FETCH_STRUCTURE = 1;
    const FETCH_FULLMSG = 2;
    const FETCH_HEADERTEXT = 3;
    const FETCH_BODYTEXT = 4;
    const FETCH_MIMEHEADER = 5;
    const FETCH_BODYPART = 6;
    const FETCH_BODYPARTSIZE = 7;
    const FETCH_HEADERS = 8;
    const FETCH_ENVELOPE = 9;
    const FETCH_FLAGS = 10;
    const FETCH_DATE = 11;
    const FETCH_SIZE = 12;
    const FETCH_UID = 13;
    const FETCH_SEQ = 14;
    const FETCH_MODSEQ = 15;

    /**
     * The key used to encrypt the password when serializing.
     *
     * @var string
     */
    static public $encryptKey = null;

    /**
     * Autoload handler.
     */
    static public function autoload($classname)
    {
        $res = false;

        $old_error = error_reporting(0);
        switch ($classname) {
        case 'Horde_Mime':
            $res = require_once 'Horde/Mime.php';
            break;

        case 'Horde_Mime_Headers':
            $res = require_once 'Horde/Mime/Headers.php';
            break;

        case 'Horde_Mime_Part':
            $res = require_once 'Horde/Mime/Part.php';
            break;

        case 'Secret':
            $res = require_once 'Horde/Secret.php';
            break;
        }
        error_reporting($old_error);

        return $res;
    }

    /**
     * Attempts to return a concrete Horde_Imap_Client instance based on
     * $driver.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $driver  The type of concrete Horde_Imap_Client subclass
     *                        to return.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return mixed  The newly created concrete Horde_Imap_Client instance.
     */
    static final public function getInstance($driver, $params = array())
    {
        $class = 'Horde_Imap_Client_' . strtr(basename($driver), '-', '_');
        if (!class_exists($class)) {
            $fname = dirname(__FILE__) . '/Client/' . $driver . '.php';
            if (is_file($fname)) {
                require_once $fname;
            }
        }
        if (!class_exists($class)) {
            throw new Horde_Imap_Client_Exception('Driver ' . $driver . ' not found', Horde_Imap_Client_Exception::DRIVER_NOT_FOUND);
        }
        return new $class($params);
    }

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
    static final public function toSequenceString($ids, $options = array())
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
    static final public function fromSequenceString($str)
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
    static final public function removeBareNewlines($str)
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
    static final public function escape($str)
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
    static final public function getBaseSubject($str, $options = array())
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
                $found = self::_removeSubjLeader($str, !empty($options['keepblob']));

                /* (4) If there is prefix text of the subject that matches
                 * the subj-blob ABNF, and removing that prefix leaves a
                 * non-empty subj-base, then remove the prefix text. */
                $found = (empty($options['keepblob']) && self::_removeBlobWhenNonempty($str)) || $found;

                /* (5) Repeat (3) and (4) until no matches remain. */
            } while ($found);

            /* (6) If the resulting text begins with the subj-fwd-hdr ABNF and
             * ends with the subj-fwd-trl ABNF, remove the subj-fwd-hdr and
             * subj-fwd-trl and repeat from step (2). */
        } while (self::_removeSubjFwdHdr($str));

        return $str;
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
    static final protected function _removeSubjLeader(&$str, $keepblob = false)
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
                if (($i = self::_removeBlob($str, $i)) === false) {
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
                if (($i = self::_removeBlob($str, $i)) === false) {
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
    static final protected function _removeBlob($str, $i)
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
    static final protected function _removeBlobWhenNonempty(&$str)
    {
        if ($str &&
            ($str[0] == '[') &&
            (($i = self::_removeBlob($str, 0)) !== false) &&
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
    static final protected function _removeSubjFwdHdr(&$str)
    {
        if ((stripos($str, '[fwd:') !== 0) || (substr($str, -1) != ']')) {
            return false;
        }

        $str = substr($str, 5, -1);
        return true;
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
    static final public function parseImapURL($url)
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
}

spl_autoload_register(array('Horde_Imap_Client_Base', 'autoload'));
