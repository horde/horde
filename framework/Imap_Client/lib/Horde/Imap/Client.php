<?php
/**
 * Horde_Imap_Client:: provides an abstracted API interface to various IMAP
 * backends (RFC 3501).
 *
 * <pre>
 * Required Parameters:
 * --------------------
 * password - (string) The IMAP user password.
 * username - (string) The IMAP username.
 *
 * Optional Parameters:
 * --------------------
 * cache - (array) If set, caches data from fetch() calls. Requires
 *         Horde_Cache and Horde_Serialize to be installed. The array can
 *         contain the following keys (see Horde_Imap_Client_Cache:: for
 *         default values):
 *   cacheob - [REQUIRED] (Horde_Cache) The cache object to use.
 *   compress - [OPTIONAL] (string) Compression to use on the cached data.
 *                Either false, 'gzip' or 'lzf'.
 *   fields - [OPTIONAL] (array) The fetch criteria to cache. If not
 *              defined, all cacheable data is cached. The following is a list
 *              of criteria that can be cached:
 *                + Horde_Imap_Client::FETCH_DATE
 *                + Horde_Imap_Client::FETCH_ENVELOPE
 *                + Horde_Imap_Client::FETCH_FLAGS
 *                  Only if server supports CONDSTORE extension
 *                + Horde_Imap_Client::FETCH_HEADERS
 *                  Only for queries that specifically request caching
 *                + Horde_Imap_Client::FETCH_SIZE
 *                + Horde_Imap_Client::FETCH_STRUCTURE
 *   lifetime - [OPTIONAL] (integer) The lifetime of the cache data (in secs).
 *   slicesize - [OPTIONAL] (integer) The slicesize to use.
 * capability_ignore - (array) A list of IMAP capabilites to ignore, even if
 *                     they are supported on the server.
 *                     DEFAULT: No supported capabilities are ignored
 * comparator - (string) The search comparator to use instead of the default
 *              IMAP server comparator. See
 *              Horde_Imap_Client_Base::setComparator() for the format.
 *              DEFAULT: Use the server default
 * debug - (string) If set, will output debug information to the stream
 *         identified. The value can be any PHP supported wrapper that can
 *         be opened via fopen().
 *         DEFAULT: No debug output
 * hostspec - (string) The hostname or IP address of the server.
 *            DEFAULT: 'localhost'
 * id - (array) Send ID information to the IMAP server (only if server
 *      supports the ID extension). An array with the keys being the fields
 *      to send and the values being the associated values. See RFC 2971
 *      [3.3] for a list of defined field values.
 *      DEFAULT: No info sent to server
 * lang - (array) A list of languages (in priority order) to be used to
 *        display human readable messages.
 *        DEFAULT: Messages output in IMAP server default language
 * port - (integer) The server port to which we will connect.
 *         DEFAULT: 143 (imap or imap w/TLS) or 993 (imaps)
 * secure - (string) Use SSL or TLS to connect.
 *          VALUES: false, 'ssl', 'tls'.
 *          DEFAULT: No encryption
 * statuscache - (boolean) Cache STATUS responses?
 *               DEFAULT: False
 * timeout - (integer)  Connection timeout, in seconds.
 *           DEFAULT: 30 seconds
 * </pre>
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
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
    const MBOX_SUBSCRIBED_EXISTS = 2;
    const MBOX_UNSUBSCRIBED = 3;
    const MBOX_ALL = 4;

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
    const STATUS_LASTMODSEQ = 1024;
    const STATUS_LASTMODSEQUIDS = 2048;
    const STATUS_UIDNOTSTICKY = 4096;

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
    /* Sort criteria defined in draft-ietf-morg-sortdisplay-02 */
    const SORT_DISPLAYFROM = 10;
    const SORT_DISPLAYTO = 11;
    /* SORT_SEQUENCE does a simple numerical sort on the returned
     * UIDs/sequence numbers. */
    const SORT_SEQUENCE = 12;

    const SORT_RESULTS_COUNT = 1;
    const SORT_RESULTS_MATCH = 2;
    const SORT_RESULTS_MAX = 3;
    const SORT_RESULTS_MIN = 4;
    const SORT_RESULTS_SAVE = 5;

    /* Constants for thread() */
    const THREAD_ORDEREDSUBJECT = 1;
    const THREAD_REFERENCES = 2;
    const THREAD_REFS = 3;

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

    /* IMAP data types (RFC 3501 [4]) */
    const DATA_ASTRING = 1;
    const DATA_ATOM = 2;
    const DATA_LISTMAILBOX = 3;
    const DATA_MAILBOX = 4;
    const DATA_NSTRING = 5;
    const DATA_NUMBER = 6;
    const DATA_STRING = 7;

    /**
     * The key used to encrypt the password when serializing.
     *
     * @var string
     */
    static public $encryptKey = null;

    /**
     * Attempts to return a concrete Horde_Imap_Client instance based on
     * $driver.
     *
     * @param string $driver  The type of concrete Horde_Imap_Client subclass
     *                        to return.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return Horde_Imap_Client_Base  The newly created Horde_Imap_Client
     *                                 instance.
     * @throws Horde_Imap_Client_Exception
     */
    static public function factory($driver, $params = array())
    {
        $class = __CLASS__ . '_' . strtr(ucfirst(basename($driver)), '-', '_');
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Imap_Client_Exception('Driver ' . $driver . ' not found', Horde_Imap_Client_Exception::DRIVER_NOT_FOUND);
    }

}
