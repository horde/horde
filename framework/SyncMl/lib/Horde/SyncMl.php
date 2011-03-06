<?php
/**
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Karsten Fourmont <karsten@horde.org>
 * @package SyncMl
 */
class Horde_SyncMl
{
    const ALERT_DISPLAY = 100;

    const ALERT_TWO_WAY = 200;
    const ALERT_SLOW_SYNC = 201;
    const ALERT_ONE_WAY_FROM_CLIENT = 202;
    const ALERT_REFRESH_FROM_CLIENT = 203;
    const ALERT_ONE_WAY_FROM_SERVER = 204;
    const ALERT_REFRESH_FROM_SERVER = 205;

    // Not implemented.
    const ALERT_TWO_WAY_BY_SERVER = 206;
    const ALERT_ONE_WAY_FROM_CLIENT_BY_SERVER = 207;
    const ALERT_REFRESH_FROM_CLIENT_BY_SERVER = 208;
    const ALERT_ONE_WAY_FROM_SERVER_BY_SERVER = 209;
    const ALERT_REFRESH_FROM_SERVER_BY_SERVER = 210;

    const ALERT_RESULT_ALERT =   221;
    const ALERT_NEXT_MESSAGE =   222;
    const ALERT_NO_END_OF_DATA = 223;

    // Not (really) implemented.
    const ALERT_SUSPEND =        224; // New in SyncML 1.2
    const ALERT_RESUME =         225; // New in SyncML 1.2

    const MIME_SYNCML_XML = 'application/vnd.syncml+xml';
    const MIME_SYNCML_WBXML = 'application/vnd.syncml+wbxml';

    const MIME_SYNCML_DEVICE_INFO_XML = 'application/vnd.syncml-devinf+xml';
    const MIME_SYNCML_DEVICE_INFO_WBXML = 'application/vnd.syncml-devinf+wbxml';

    const MIME_TEXT_PLAIN = 'text/plain';
    const MIME_VCARD_V21 = 'text/x-vcard';
    const MIME_VCARD_V30 = 'text/vcard';

    const MIME_VCALENDAR = 'text/x-vcalendar';
    const MIME_ICALENDAR = 'text/calendar';
    const MIME_XML_ICALENDAR = 'application/vnd.syncml-xcal';

    const MIME_MESSAGE = 'text/message';

    const MIME_SYNCML_XML_EMAIL = 'application/vnd.syncml-xmsg';
    const MIME_SYNCML_XML_BOOKMARK = 'application/vnd.syncml-xbookmark';
    const MIME_SYNCML_RELATIONAL_OBJECT = 'application/vnd.syncml-xrelational';

    const RESPONSE_IN_PROGRESS = 101;

    const RESPONSE_OK = 200;
    const RESPONSE_ITEM_ADDED = 201;
    const RESPONSE_ACCEPTED_FOR_PROCESSING = 202;
    const RESPONSE_NONAUTHORIATATIVE_RESPONSE = 203;
    const RESPONSE_NO_CONTENT = 204;
    const RESPONSE_RESET_CONTENT = 205;
    const RESPONSE_PARTIAL_CONTENT = 206;
    const RESPONSE_CONFLICT_RESOLVED_WITH_MERGE = 207;
    const RESPONSE_CONFLICT_RESOLVED_WITH_CLIENT_WINNING = 208;
    const RESPONSE_CONFILCT_RESOLVED_WITH_DUPLICATE = 209;
    const RESPONSE_DELETE_WITHOUT_ARCHIVE = 210;
    const RESPONSE_ITEM_NO_DELETED = 211;
    const RESPONSE_AUTHENTICATION_ACCEPTED = 212;
    const RESPONSE_CHUNKED_ITEM_ACCEPTED_AND_BUFFERED = 213;
    const RESPONSE_OPERATION_CANCELLED = 214;
    const RESPONSE_NO_EXECUTED = 215;
    const RESPONSE_ATOMIC_ROLL_BACK_OK = 216;

    const RESPONSE_MULTIPLE_CHOICES = 300;
    // Need to change names.
    // const RESPONSE_MULTIPLE_CHOICES = 301;
    // const RESPONSE_MULTIPLE_CHOICES = 302;
    // const RESPONSE_MULTIPLE_CHOICES = 303;
    // const RESPONSE_MULTIPLE_CHOICES = 304;
    const RESPONSE_USE_PROXY = 305;

    const RESPONSE_BAD_REQUEST = 400;
    const RESPONSE_INVALID_CREDENTIALS = 401;
    // Need to change names.
    // const RESPONSE_INVALID_CREDENTIALS = 402;
    // const RESPONSE_INVALID_CREDENTIALS = 403;
    const RESPONSE_NOT_FOUND = 404;
    // Need to change names.
    // const RESPONSE_INVALID_CREDENTIALS = 405;
    // const RESPONSE_INVALID_CREDENTIALS = 406;
    const RESPONSE_CREDENTIALS_MISSING = 407;
    // const RESPONSE_INVALID_CREDENTIALS = 408;
    // const RESPONSE_INVALID_CREDENTIALS = 409;
    // const RESPONSE_INVALID_CREDENTIALS = 410;
    // const RESPONSE_INVALID_CREDENTIALS = 411;
    // const RESPONSE_INVALID_CREDENTIALS = 412;
    // const RESPONSE_INVALID_CREDENTIALS = 413;
    // const RESPONSE_INVALID_CREDENTIALS = 414;
    // const RESPONSE_INVALID_CREDENTIALS = 415;
    const RESPONSE_REQUEST_SIZE_TOO_BIG = 416;
    // Need to change names.
    // const RESPONSE_INVALID_CREDENTIALS = 417;
    // const RESPONSE_INVALID_CREDENTIALS = 418;
    // const RESPONSE_INVALID_CREDENTIALS = 419;
    // const RESPONSE_INVALID_CREDENTIALS = 420;
    // const RESPONSE_INVALID_CREDENTIALS = 421;
    // const RESPONSE_INVALID_CREDENTIALS = 422;
    // const RESPONSE_INVALID_CREDENTIALS = 423;
    const RESPONSE_SIZE_MISMATCH = 424;

    const RESPONSE_COMMAND_FAILED = 500;
    // Need to change names.
    // const RESPONSE_COMMAND_FAILED = 501;
    // const RESPONSE_COMMAND_FAILED = 502;
    // const RESPONSE_COMMAND_FAILED = 503;
    // const RESPONSE_COMMAND_FAILED = 504;
    // const RESPONSE_COMMAND_FAILED = 505;
    // const RESPONSE_COMMAND_FAILED = 506;
    // const RESPONSE_COMMAND_FAILED = 507;
    const RESPONSE_REFRESH_REQUIRED = 508;
    // const RESPONSE_COMMAND_FAILED = 509;
    // const RESPONSE_COMMAND_FAILED = 510;
    // const RESPONSE_COMMAND_FAILED = 511;
    // const RESPONSE_COMMAND_FAILED = 512;
    // const RESPONSE_COMMAND_FAILED = 513;
    // const RESPONSE_COMMAND_FAILED = 514;
    // const RESPONSE_COMMAND_FAILED = 515;
    // const RESPONSE_ATOMIC_ROLL_BACK_FAILED = 516;

    const NAME_SPACE_URI_SYNCML = 'syncml:syncml';
    const NAME_SPACE_URI_SYNCML_1_1 = 'syncml:syncml1.1';
    const NAME_SPACE_URI_SYNCML_1_2 = 'syncml:syncml1.2';
    const NAME_SPACE_URI_METINF = 'syncml:metinf';
    const NAME_SPACE_URI_METINF_1_1 = 'syncml:metinf';
    const NAME_SPACE_URI_METINF_1_2 = 'syncml:metinf';
    const NAME_SPACE_URI_DEVINF = 'syncml:devinf';
    const NAME_SPACE_URI_DEVINF_1_1 = 'syncml:devinf';
    const NAME_SPACE_URI_DEVINF_1_2 = 'syncml:devinf';

    /**
     * Maximum Size of a data object. Currently global for all databases.
     */
    const SERVER_MAXOBJSIZE = 1000000000;

    /**
     * Maximum size for one sync message as defined by SyncML protocol spec.
     */
    const SERVER_MAXMSGSIZE = 1000000000;

    /**
     * The "safety margin" for the closing tags when finishing a message.
     *
     * When exporting a data entry, we have to ensure that the size of the
     * complete message does not exceed MaxMsgSize sent by the client.
     */
    const MSG_TRAILER_LEN = 150;

    /**
     * Standard size for a complete but empty SyncML message. Used in estimating
     * the size for a message.
     */
    const MSG_DEFAULT_LEN = 1000;

    /**
     * If true the client uid<->server uid map will be deleted when a SlowSync
     * is requested.
     *
     * This produces duplicates if there are entries in the client and the
     * server.  This need to be true for the test conformance suite.
     */
    const CONFIG_DELETE_MAP_ON_REQUESTED_SLOWSYNC = true;

    /**
     * If true the client uid<->server uid map will be deleted when a SlowSync
     * is done due to an anchor mismatch. An anchor mismatch may happen if a
     * session terminates unexpectedly.
     */
    const CONFIG_DELETE_MAP_ON_ANCHOR_MISMATCH_SLOWSYNC = false;
}
