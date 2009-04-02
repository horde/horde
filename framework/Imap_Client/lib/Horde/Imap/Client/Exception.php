<?php
/**
 * Exception handler for the Horde_Imap_Client class.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Horde_Imap_Client
 */
class Horde_Imap_Client_Exception extends Exception
{
    /* Error message codes. */
    // Unspecified error (default)
    const UNSPECIFIED = 0;

    // The given Horde_Imap_Client driver does not exist on the system.
    const DRIVER_NOT_FOUND = 1;

    // The function called is not supported in POP3.
    const POP3_NOTSUPPORTED = 2;

    // There was an unrecoverable error in UTF7IMAP -> UTF8 conversion.
    const UTF7IMAP_CONVERSION = 3;

    // The IMAP server ended the connection.
    const IMAP_DISCONNECT = 4;

    // The charset used in the search query is not supported on the server.
    const BADCHARSET = 5;

    // There were errors parsing the MIME/RFC 2822 header of the part.
    const PARSEERROR = 6;

    // The server could not decode the MIME part (see RFC 3516)
    const UNKNOWNCTE = 7;

    // The server does not support the IMAP extensions needed for this
    // operation
    const NOSUPPORTIMAPEXT = 8;

    // The comparator specified by setComparator() was not recognized by the
    // IMAP server
    const BADCOMPARATOR = 9;

    // RFC 4551 [3.1.2] - All mailboxes are not required to support
    // mod-sequences.
    const MBOXNOMODSEQ = 10;

    // Thrown if server denies the network connection.
    const SERVER_CONNECT = 11;

    // Thrown if read error for server response.
    const SERVER_READERROR = 12;

    /**
     * Define a callback function used to log the exception. Will be passed
     * a single parameter - a copy of this object.
     *
     * @var callback
     */
    static public $logCallback = null;

    /**
     * Constructor.
     */
    public function __construct($message = null, $code = 0)
    {
        parent::__construct($message, $code);

        /* Call log function. */
        if (!is_null(self::$logCallback)) {
            call_user_func(self::$logCallback, $this);
        }
    }
}
