<?php
/**
 * Horde_ActiveSync_Message_Exception::
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
 * Horde_ActiveSync_Message_Exception::
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
class Horde_ActiveSync_Message_Exception extends Horde_ActiveSync_Message_Appointment
{
    protected $_mapping = array(
        Horde_ActiveSync_Message_Appointment::POOMCAL_TIMEZONE           => array(self::KEY_ATTRIBUTE => 'timezone'),
        Horde_ActiveSync_Message_Appointment::POOMCAL_DTSTAMP            => array(self::KEY_ATTRIBUTE => 'dtstamp', self::KEY_TYPE => self::TYPE_DATE),
        Horde_ActiveSync_Message_Appointment::POOMCAL_STARTTIME          => array(self::KEY_ATTRIBUTE => 'starttime', self::KEY_TYPE => self::TYPE_DATE),
        Horde_ActiveSync_Message_Appointment::POOMCAL_SUBJECT            => array(self::KEY_ATTRIBUTE => 'subject'),
        Horde_ActiveSync_Message_Appointment::POOMCAL_ORGANIZERNAME      => array(self::KEY_ATTRIBUTE => 'organizername'),
        Horde_ActiveSync_Message_Appointment::POOMCAL_LOCATION           => array(self::KEY_ATTRIBUTE => 'location'),
        Horde_ActiveSync_Message_Appointment::POOMCAL_ENDTIME            => array(self::KEY_ATTRIBUTE => 'endtime', self::KEY_TYPE => self::TYPE_DATE),
        Horde_ActiveSync_Message_Appointment::POOMCAL_SENSITIVITY        => array(self::KEY_ATTRIBUTE => 'sensitivity'),
        Horde_ActiveSync_Message_Appointment::POOMCAL_BUSYSTATUS         => array(self::KEY_ATTRIBUTE => 'busystatus'),
        Horde_ActiveSync_Message_Appointment::POOMCAL_ALLDAYEVENT        => array(self::KEY_ATTRIBUTE => 'alldayevent'),
        Horde_ActiveSync_Message_Appointment::POOMCAL_REMINDER           => array(self::KEY_ATTRIBUTE => 'reminder'),
        Horde_ActiveSync_Message_Appointment::POOMCAL_MEETINGSTATUS      => array(self::KEY_ATTRIBUTE => 'meetingstatus'),
        Horde_ActiveSync_Message_Appointment::POOMCAL_ATTENDEES          => array(self::KEY_ATTRIBUTE => 'attendees', self::KEY_TYPE => 'Horde_ActiveSync_Message_Attendee', self::KEY_VALUES => Horde_ActiveSync_Message_Appointment::POOMCAL_ATTENDEE),
        Horde_ActiveSync_Message_Appointment::POOMCAL_CATEGORIES         => array(self::KEY_ATTRIBUTE => 'categories', self::KEY_VALUES => Horde_ActiveSync_Message_Appointment::POOMCAL_CATEGORY),
        Horde_ActiveSync_Message_Appointment::POOMCAL_EXCEPTIONSTARTTIME => array(self::KEY_ATTRIBUTE => 'exceptionstarttime', self::KEY_TYPE => self::TYPE_DATE),
        Horde_ActiveSync_Message_Appointment::POOMCAL_DELETED            => array(self::KEY_ATTRIBUTE => 'deleted'),
    );

    protected $_properties = array(
        'timezone'           => false,
        'dtstamp'            => false,
        'starttime'          => false,
        'subject'            => false,
        'organizername'      => false,
        'location'           => false,
        'endtime'            => false,
        'sensitivity'        => false,
        'busystatus'         => false,
        'alldayevent'        => false,
        'reminder'           => false,
        'meetingstatus'      => false,
        'exceptionstarttime' => false,
        'deleted'            => false,
    );

    /**
     * Sets the DELETED field on this exception
     *
     * @param boolean $flag
     */
    public function setDeletedFlag($flag)
    {
        $this->_properties['deleted'] = $flag;
    }

    /**
     * Exception start time. This field seems to have different usages depending
     * on if this is a command request from the client or from the server. If
     * it's part of a request from client, then it represents the date of the
     * exception that is to be deleted. If it is from server, it represents the
     * date of the *original* recurring event.
     *
     * @return Horde_Date  The exception's start time
     */
    public function getExceptionStartTime()
    {
        return $this->_getAttribute('exceptionstarttime');
    }

    public function setExceptionStartTime($date)
    {
        $this->exceptionstarttime = $date;
    }

}
