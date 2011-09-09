<?php
/**
 * Exception handler for the Horde_Imap_Client package.
 *
 * Copyright 2008-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_Exception extends Horde_Exception_Wrapped
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

    // Bad search request.
    const BADSEARCH = 16;

    // The user did not have permissions to carry out the operation.
    const NOPERM = 17;

    // The operation was not successful because another user is holding
    // a necessary resource. The operation may succeed if attempted later.
    const INUSE = 18;

    // The operation failed because data on the server was corrupt.
    const CORRUPTION = 19;

    // The operation failed because it exceeded some limit on the server.
    const LIMIT = 20;

    // The operation failed because the user is over their quota.
    const OVERQUOTA = 21;

    // The operation failed because the requested creation object already
    // exists.
    const ALREADYEXISTS = 22;

    // The operation failed because the requested deletion object did not
    // exist.
    const NONEXISTENT = 23;

    // Setting metadata failed because the size of its value is too large.
    // The maximum octet count the server is willing to accept will be
    // in the exception message string.
    const METADATA_MAXSIZE = 24;

    // Setting metadata failed because it does not support private
    // annotations on one of the specified mailboxes.
    const METADATA_TOOMANY = 24;

    // Setting metadata failed because the server does not support private
    // annotations on one of the specified mailboxes.
    const METADATA_NOPRIVATE = 24;


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


    // POP3 specific error codes

    // Temporary issue. Generally, there is no need to alarm the user for
    // errors of this type.
    const POP3_TEMP_ERROR = 300;

    // Permanent error indicated by server.
    const POP3_PERM_ERROR = 301;

}
