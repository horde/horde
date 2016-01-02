<?php
/**
 * Horde_ActiveSync_Status::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2013-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Status:: Constants for common EAS status codes. Common codes
 * were introduced in EAS 14.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2013-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Status
{
    // EAS 12.1
    const INVALID_CONTENT                        = 101;
    const INVALID_WBXML                          = 102;
    const INVALID_XML                            = 103;
    const INVALID_DATETIME                       = 104;
    const INVALID_COMBINATIONOFIDS               = 105;
    const INVALID_IDS                            = 106; // was previously 400 or 500 (for SENDMAIL) in 12.0.
    const INVALID_MIME                           = 107;
    const INVALID_DEVICEID                       = 108;
    const INVALID_DEVICETYPE                     = 109;
    const SERVER_ERROR                           = 110; // was a general 500 error (server should not try again) in 12.0.
    const SERVER_ERROR_RETRY                     = 111; // was a 503 in 12.0
    const MAILBOX_QUOTA_EXCEEDED                 = 113;
    const MAILBOX_OFFLINE                        = 114;
    const SEND_QUOTA_EXCEEDED                    = 115;
    const RECIPIENT_UNRESOLVED                   = 116;
    const DUPLICATE_MESSAGE                      = 118; // @TODO
    const NO_RECIPIENT                           = 119;
    const MAIL_SUBMISSION_FAILED                 = 120;
    const MAIL_REPLY_FAILED                      = 121;
    const ATT_TOO_LARGE                          = 122;
    const NO_MAILBOX                             = 123;
    const SYNC_NOT_ALLOWED                       = 126;
    const DEVICE_BLOCKED_FOR_USER                = 129;
    const DENIED                                 = 130;
    const DISABLED                               = 131;
    const STATEFILE_NOT_FOUND                    = 132;  // was 500 in 12.0
    const STATEVERSION_INVALID                   = 136;
    const DEVICE_NOT_FULLY_PROVISIONABLE         = 139;  // Device uses version that doesn't support policies defined on server.
    const REMOTEWIPE_REQUESTED                   = 140;
    const LEGACY_DEVICE_STRICT_POLICY            = 141;
    const DEVICE_NOT_PROVISIONED                 = 142;
    const POLICY_REFRESH                         = 143;
    const INVALID_POLICY_KEY                     = 144;
    const EXTERNALLY_MANAGED_DEVICES_NOT_ALLOWED = 145;
    const UNEXPECTED_ITEM_CLASS                  = 147;
    const INVALID_STORED_REQUEST                 = 149;
    const ITEM_NOT_FOUND                         = 150;
    const TOO_MANY_FOLDERS                       = 151;
    const NO_FOLDERS_FOUND                       = 152;
    const ITEMS_LOST_AFTER_MOVE                  = 153;
    const FAILURE_IN_MOVE_OPERATION              = 154;
    const MOVE_INVALID_DESTINATION               = 156;
    // EAS 14.0
    const AVAILABILITY_TOO_MANY_RECIPIENTS       = 160;
    const AVAILABILITY_TRANSIENT_FAILURE         = 162;
    const AVAILABILITY_FAILURE                   = 163;
    const AVAILABILITY_SUCCESS                   = 1;
    // EAS 14.1
    const DEVICE_INFORMATION_REQUIRED            = 165;
    const INVALID_ACCOUNT_ID                     = 166;
    const IRM_DISABLED                           = 168;
    const PICTURE_SUCCESS                        = 1;
    const NO_PICTURE                             = 173;
    const PICTURE_TOO_LARGE                      = 174;
    const PICTURE_LIMIT_REACHED                  = 175;
    const BODYPART_CONVERSATION_TOO_LARGE        = 176;
    const MAXIMUM_DEVICES_REACHED                = 177;



}