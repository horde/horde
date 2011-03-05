<?php
/**
 * Exception handler for the Horde_Imap_Client class.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Imap_Client
 */
class Horde_Imap_Client_Exception extends Horde_Exception_Prior
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

    // The server ended the connection.
    const DISCONNECT = 4;

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

    // Thrown on CATENATE if a bad IMAP URL is found.
    const CATENATE_BADURL = 13;

    // Thrown on CATENATE if the message was too big.
    const CATENATE_TOOBIG = 14;

    // Thrown on CREATE if special-use attribute is not supported.
    const USEATTR = 15;


    // Login failures

    // Could not start mandatory TLS connection.
    const LOGIN_TLSFAILURE = 100;

    // Could not find an available authentication method.
    const LOGIN_NOAUTHMETHOD = 101;

    // Generic authentication failure.
    const LOGIN_AUTHENTICATIONFAILED = 102;

    // Remote server is unavailable.
    const LOGIN_UNAVAILABLE = 103;

    // Authentication succeeded, but authorization failed.
    const LOGIN_AUTHORIZATIONFAILED = 104;

    // Authentication is no longer permitted with this passphrase.
    const LOGIN_EXPIRED = 105;

    // Login requires privacy.
    const LOGIN_PRIVACYREQUIRED = 106;


    // Mailbox access failures

    // Could not open/access mailbox
    const MAILBOX_NOOPEN = 200;

}
