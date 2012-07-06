<?php
/**
 * Base class for Horde_Imap_Client package. Defines common constants and
 * provides factory for creating an IMAP client object.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client
{
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
    /* Sort criteria defined in RFC 5957 */
    const SORT_DISPLAYFROM = 10;
    const SORT_DISPLAYTO = 11;
    /* SORT_SEQUENCE does a simple numerical sort on the returned
     * UIDs/sequence numbers. */
    const SORT_SEQUENCE = 12;
    /* Fuzzy sort criteria defined in RFC 6203 */
    const SORT_RELEVANCY = 13;

    /* Search results constants */
    const SEARCH_RESULTS_COUNT = 1;
    const SEARCH_RESULTS_MATCH = 2;
    const SEARCH_RESULTS_MAX = 3;
    const SEARCH_RESULTS_MIN = 4;
    const SEARCH_RESULTS_SAVE = 5;
    /* Fuzzy sort criteria defined in RFC 6203 */
    const SEARCH_RESULTS_RELEVANCY = 6;

    /* Constants for thread() */
    const THREAD_ORDEREDSUBJECT = 1;
    const THREAD_REFERENCES = 2;
    const THREAD_REFS = 3;

    /* Fetch criteria constants. */
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
    const FETCH_IMAPDATE = 11;
    const FETCH_SIZE = 12;
    const FETCH_UID = 13;
    const FETCH_SEQ = 14;
    const FETCH_MODSEQ = 15;

    /* IMAP data types (RFC 3501 [4]) */
    const DATA_ASTRING = 1;
    const DATA_ATOM = 2;
    const DATA_DATETIME = 3;
    const DATA_LISTMAILBOX = 4;
    const DATA_MAILBOX = 5;
    const DATA_NSTRING = 6;
    const DATA_NUMBER = 7;
    const DATA_STRING = 8;

    /* Namespace constants. */
    const NS_PERSONAL = 1;
    const NS_OTHER = 2;
    const NS_SHARED = 3;

    /* ACL constants (RFC 4314 [2.1]). */
    const ACL_LOOKUP = 'l';
    const ACL_READ = 'r';
    const ACL_SEEN = 's';
    const ACL_WRITE = 'w';
    const ACL_INSERT = 'i';
    const ACL_POST = 'p';
    const ACL_CREATEMBOX = 'k';
    const ACL_DELETEMBOX = 'x';
    const ACL_DELETEMSGS = 't';
    const ACL_EXPUNGE = 'e';
    const ACL_ADMINISTER = 'a';
    // Old constants (RFC 2086 [3]; RFC 4314 [2.1.1])
    const ACL_CREATE = 'c';
    const ACL_DELETE = 'd';

    /* System flags. */
    // RFC 3501 [2.3.2]
    const FLAG_ANSWERED = '\\answered';
    const FLAG_DELETED = '\\deleted';
    const FLAG_DRAFT = '\\draft';
    const FLAG_FLAGGED = '\\flagged';
    const FLAG_RECENT = '\\recent';
    const FLAG_SEEN = '\\seen';
    // RFC 3503 [3.3]
    const FLAG_MDNSENT = '$mdnsent';
    // RFC 5550 [2.8]
    const FLAG_FORWARDED = '$forwarded';
    // RFC 5788 registered keywords:
    // http://www.ietf.org/mail-archive/web/morg/current/msg00441.html
    const FLAG_JUNK = '$junk';
    const FLAG_NOTJUNK = '$notjunk';

    /* Special-use mailbox attributes (RFC 6154 [2]). */
    const SPECIALUSE_ALL = '\\All';
    const SPECIALUSE_ARCHIVE = '\\Archive';
    const SPECIALUSE_DRAFTS = '\\Drafts';
    const SPECIALUSE_FLAGGED = '\\Flagged';
    const SPECIALUSE_JUNK = '\\Junk';
    const SPECIALUSE_SENT = '\\Sent';
    const SPECIALUSE_TRASH = '\\Trash';

    /* Debugging constants. */
    const DEBUG_RAW = 0;
    const DEBUG_CLIENT = 1;
    const DEBUG_INFO = 2;
    const DEBUG_SERVER = 3;
    // Time, in seconds, for a slow command.
    const SLOW_COMMAND = 1;

    /**
     * Capability dependencies.
     *
     * @var array
     */
    static public $capability_deps = array(
        // RFC 5162 [1]
        'QRESYNC' => array(
            // QRESYNC requires CONDSTORE, but the latter is implied and is
            // not required to be listed.
            'ENABLE'
        ),
        // RFC 5182 [2.1]
        'SEARCHRES' => array(
            'ESEARCH'
        ),
        // RFC 5255 [3.1]
        'LANGUAGE' => array(
            'NAMESPACE'
        ),
        // RFC 5957 [1]
        'SORT=DISPLAY' => array(
            'SORT'
        )
    );

}
