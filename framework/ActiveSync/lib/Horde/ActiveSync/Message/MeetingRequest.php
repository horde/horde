<?php
/**
 * Horde_ActiveSync_Message_MeetingRequest
 *
 * Portions of this class were ported from the Z-Push project:
 *   File      :   wbxml.php
 *   Project   :   Z-Push
 *   Descr     :   WBXML mapping file
 *
 *   Created   :   01.10.2007
 *
 *   � Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_MeetingRequest
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Message_MeetingRequest extends Horde_ActiveSync_Message_Base
{
    protected $_mapping = array (
        Horde_ActiveSync_Message_Mail::POOMMAIL_ALLDAYEVENT => array(self::KEY_ATTRIBUTE => "alldayevent"),
        Horde_ActiveSync_Message_Mail::POOMMAIL_STARTTIME => array(self::KEY_ATTRIBUTE => "starttime", self::KEY_TYPE => self::TYPE_DATE_DASHES),
        Horde_ActiveSync_Message_Mail::POOMMAIL_DTSTAMP => array(self::KEY_ATTRIBUTE => "dtstamp", self::KEY_TYPE => self::TYPE_DATE_DASHES),
        Horde_ActiveSync_Message_Mail::POOMMAIL_ENDTIME => array(self::KEY_ATTRIBUTE => "endtime", self::KEY_TYPE => self::TYPE_DATE_DASHES),
        Horde_ActiveSync_Message_Mail::POOMMAIL_INSTANCETYPE => array(self::KEY_ATTRIBUTE => "instancetype"),
        Horde_ActiveSync_Message_Mail::POOMMAIL_LOCATION => array(self::KEY_ATTRIBUTE => "location"),
        Horde_ActiveSync_Message_Mail::POOMMAIL_ORGANIZER => array(self::KEY_ATTRIBUTE => "organizer"),
        Horde_ActiveSync_Message_Mail::POOMMAIL_RECURRENCEID => array(self::KEY_ATTRIBUTE => "recurrenceid", self::KEY_TYPE => self::TYPE_DATE_DASHES),
        Horde_ActiveSync_Message_Mail::POOMMAIL_REMINDER => array(self::KEY_ATTRIBUTE => "reminder"),
        Horde_ActiveSync_Message_Mail::POOMMAIL_RESPONSEREQUESTED => array(self::KEY_ATTRIBUTE => "responserequested"),
        Horde_ActiveSync_Message_Mail::POOMMAIL_RECURRENCES => array(self::KEY_ATTRIBUTE => "recurrences", self::KEY_TYPE => 'Horde_ActiveSync_Message_MeetingRequestRecurrence', self::KEY_VALUES => Horde_ActiveSync_Message_Mail::POOMMAIL_RECURRENCE),
        Horde_ActiveSync_Message_Mail::POOMMAIL_SENSITIVITY => array(self::KEY_ATTRIBUTE => "sensitivity"),
        Horde_ActiveSync_Message_Mail::POOMMAIL_BUSYSTATUS => array(self::KEY_ATTRIBUTE => "busystatus"),
        Horde_ActiveSync_Message_Mail::POOMMAIL_TIMEZONE => array(self::KEY_ATTRIBUTE => "timezone"),
        Horde_ActiveSync_Message_Mail::POOMMAIL_GLOBALOBJID => array(self::KEY_ATTRIBUTE => "globalobjid"),
    );

    protected $_properties = array(
        'alldayevent' => false,
        'starttime' => false,
        'dtstamp' => false,
        'endtime' => false,
        'instancetype' => false,
        'location' => false,
        'organizer' => false,
        'recurrenceid' => false,
        'reminder' => false,
        'responserequested' => false,
        'recurrences' => array(),
        'sensitivity' => false,
        'busystatus' => false,
        'timezone' => false,
        'globalobjid' => false
    );

}