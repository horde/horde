<?php
/**
 * Portions of this class were ported from the Z-Push project:
 *   File      :   wbxml.php
 *   Project   :   Z-Push
 *   Descr     :   WBXML mapping file
 *
 *   Created   :   01.10.2007
 *
 *   © Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2015 Horde LLC (http://www.horde.org)
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
 * @copyright 2010-2015 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 *
 * @property integer   $alldayevent
 * @property Horde_Date   $starttime
 * @property Horde_Date   $dtstamp
 * @property Horde_Date   $endtime
 * @property integer   $instancetype
 * @property string   $location
 * @property string   $organizer
 * @property string   $recurrenceid
 * @property integer   $reminder
 * @property integer   $responserequested
 * @property Horde_ActiveSync_Message_Recurrence   $recurrences (Not currently supported).
 * @property integer   $sensitivity
 * @property integer   $busystatus
 * @property string|Horde_Date timezone
 * @proprety string globalobjid
 */
class Horde_ActiveSync_Message_MeetingRequest extends Horde_ActiveSync_Message_Base
{
    /**
     * Property mapping.
     *
     * @var array
     */
    protected $_mapping = array (
        Horde_ActiveSync_Message_Mail::POOMMAIL_ALLDAYEVENT => array(self::KEY_ATTRIBUTE => 'alldayevent'),
        Horde_ActiveSync_Message_Mail::POOMMAIL_STARTTIME => array(self::KEY_ATTRIBUTE => 'starttime', self::KEY_TYPE => self::TYPE_DATE_DASHES),
        Horde_ActiveSync_Message_Mail::POOMMAIL_DTSTAMP => array(self::KEY_ATTRIBUTE => 'dtstamp', self::KEY_TYPE => self::TYPE_DATE_DASHES),
        Horde_ActiveSync_Message_Mail::POOMMAIL_ENDTIME => array(self::KEY_ATTRIBUTE => 'endtime', self::KEY_TYPE => self::TYPE_DATE_DASHES),
        Horde_ActiveSync_Message_Mail::POOMMAIL_INSTANCETYPE => array(self::KEY_ATTRIBUTE => 'instancetype'),
        Horde_ActiveSync_Message_Mail::POOMMAIL_LOCATION => array(self::KEY_ATTRIBUTE => 'location'),
        Horde_ActiveSync_Message_Mail::POOMMAIL_ORGANIZER => array(self::KEY_ATTRIBUTE => 'organizer'),
        Horde_ActiveSync_Message_Mail::POOMMAIL_RECURRENCEID => array(self::KEY_ATTRIBUTE => 'recurrenceid', self::KEY_TYPE => self::TYPE_DATE_DASHES),
        Horde_ActiveSync_Message_Mail::POOMMAIL_REMINDER => array(self::KEY_ATTRIBUTE => 'reminder'),
        Horde_ActiveSync_Message_Mail::POOMMAIL_RESPONSEREQUESTED => array(self::KEY_ATTRIBUTE => 'responserequested'),
        Horde_ActiveSync_Message_Mail::POOMMAIL_RECURRENCES => array(self::KEY_ATTRIBUTE => 'recurrences', self::KEY_TYPE => 'Horde_ActiveSync_Message_MeetingRequestRecurrence', self::KEY_VALUES => Horde_ActiveSync_Message_Mail::POOMMAIL_RECURRENCE),
        Horde_ActiveSync_Message_Mail::POOMMAIL_SENSITIVITY => array(self::KEY_ATTRIBUTE => 'sensitivity'),
        Horde_ActiveSync_Message_Mail::POOMMAIL_BUSYSTATUS => array(self::KEY_ATTRIBUTE => 'busystatus'),
        Horde_ActiveSync_Message_Mail::POOMMAIL_TIMEZONE => array(self::KEY_ATTRIBUTE => 'timezone'),
        Horde_ActiveSync_Message_Mail::POOMMAIL_GLOBALOBJID => array(self::KEY_ATTRIBUTE => 'globalobjid'),
    );

    /**
     * Property values.
     *
     * @var array
     */
    protected $_properties = array(
        'alldayevent' => '0',
        'starttime' => false,
        'dtstamp' => false,
        'endtime' => false,
        'instancetype' => '0', // For now, no recurring meeting request support.
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

    /**
     * Copy of the vEvent object.
     *
     * @var Horde_Icalendar_Vevent
     */
    protected $_vEvent;

    /**
     * Const'r
     *
     * @see Horde_ActiveSync_Message_Base::__construct()
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);
        if ($this->_version > Horde_ActiveSync::VERSION_FOURTEEN) {
            $this->_mapping += array(
                Horde_ActiveSync_Message_Mail::POOMMAIL2_MEETINGMESSAGETYPE  => array(self::KEY_ATTRIBUTE => 'meetingmessagetype')
            );
            $this->_properties += array(
                'meetingmessagetype' => false
            );
        }
    }

    /**
     * Create a meeting request from a vEvent.
     *
     * @param Horde_Icalendar_Vevent $vCal  The vEvent.
     *
     * @throws Horde_ActiveSync_Exception
     */
    public function fromvEvent($vCal)
    {
        try {
            $method = $vCal->getAttribute('METHOD');
        } catch (Horde_Icalendar_Exception $e) {
            throw new Horde_ActiveSync_Exception('Unable to parse vEvent');
        }
        foreach ($vCal->getComponents() as $component) {
            switch ($component->getType()) {
            case 'vEvent':
                $this->_vEvent = $component;
                $this->_vEvent($component, $method);
                break;

            case 'vTimeZone':
            // Not sure what to do with Timezone yet/how to get it into
            // a TZ structure etc... For now, defaults to default timezone (as the
            // specs say it should for iCal without tz specified).
            default:
                break;
            }
        }

        // Ensure we actually have a vEvent to parse.
        if (empty($this->_vEvent)) {
            return;
        }

        $tz = new Horde_Mapi_Timezone();
        try {
            $this->timezone = $tz->getSyncTZFromOffsets(
                $tz->getOffsetsFromDate(new Horde_Date($this->_vEvent->getAttribute('DTSTART')))
            );
        } catch (Horde_Icalendar_Exception $e) {}
        $this->alldayevent = (int)$this->_isAllDay();
    }

    /**
     * Return the vEvent object the request/response is based on.
     *
     * @return Horde_Icalendar_vEvent
     */
    public function getvEvent()
    {
        return $this->_vEvent;
    }

    /**
     * Parses a vEvent into the message properties.
     *
     * @param Horde_Icalendar_Vevent $vevent  The vEvent to parse.
     * @param string $method                  The method (e.g., 'REQUEST').
     *
     * @throws Horde_ActiveSync_Exception
     */
    protected function _vEvent($vevent, $method = 'REQUEST')
    {
        if ($method == 'REQUEST') {
            $this->responserequested = '1';
        } else {
            $this->responserequested = '0';
        }
        try {
            $organizer = parse_url($vevent->getAttribute('ORGANIZER'));
            $this->organizer = $organizer['path'];
        } catch (Horde_Icalendar_Exception $e) {}

        try {
            $this->globalobjid = Horde_Mapi::createGoid($vevent->getAttribute('UID'));
            $this->starttime = new Horde_Date($vevent->getAttribute('DTSTART'));
            $this->endtime = new Horde_Date($vevent->getAttribute('DTEND'));
        } catch (Horde_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }

        try {
            $this->dtstamp = new Horde_Date($vevent->getAttribute('DTSTAMP'));
        } catch (Horde_Exception $e) {}

        try {
            $this->location = Horde_String::truncate($vevent->getAttribute('LOCATION'), 255);
        } catch (Horde_Icalendar_Exception $e) {}

        try {
            $class = $vevent->getAttribute('CLASS');
            if (!is_array($class)) {
                $this->sensitivity = $class == 'PRIVATE'
                    ? Horde_ActiveSync_Message_Appointment::SENSITIVITY_PRIVATE
                    : ($class == 'CONFIDENTIAL' ? Horde_ActiveSync_Message_Appointment::SENSITIVITY_CONFIDENTIAL
                        : ($class == 'PERSONAL' ? Horde_ActiveSync_Message_Appointment::SENSITIVITY_PERSONAL
                            : Horde_ActiveSync_Message_Appointment::SENSITIVITY_NORMAL));
            }
        } catch (Horde_Icalendar_Exception $e) {}

        try {
            $status = $vevent->getAttribute('STATUS');
            if (!is_array($status)) {
                $status = Horde_String::upper($status);
                $this->busystatus = $status == 'TENTATIVE' ? Horde_ActiveSync_Message_Appointment::BUSYSTATUS_TENTATIVE
                    : ($status == 'CONFIRMED' ? Horde_ActiveSync_Message_Appointment::BUSYSTATUS_BUSY
                        : Horde_ActiveSync_Message_Appointment::BUSYSTATUS_FREE);
            }
        } catch (Horde_Icalendar_Exception $e) {}

        // vCalendar 1.0 alarms
        try {
            $alarm = $vevent->getAttribute('AALARM');
            if (!is_array($alarm) && intval($alarm)) {
                $this->reminder = intval($this->starttime->timestamp() - $alarm);
            }
        } catch (Horde_Icalendar_Exception $e) {}

        // vCalendar 2.0 alarms
        foreach ($vevent->getComponents() as $alarm) {
            if (!($alarm instanceof Horde_Icalendar_Valarm)) {
                continue;
            }
            try {
                $trigger = $alarm->getAttribute('TRIGGER');
                $triggerParams = $alarm->getAttribute('TRIGGER', true);
            } catch (Horde_Icalendar_Exception $e) {
                continue;
            }
            if (isset($triggerParams['VALUE']) &&
                $triggerParams['VALUE'] == 'DATE-TIME') {
                if (isset($triggerParams['RELATED']) &&
                    $triggerParams['RELATED'] == 'END') {
                    $this->reminder = intval($this->endtime->timestamp() - $trigger);
                } else {
                    $this->reminder = intval($this->starttime->timestamp() - $trigger);
                }
            } else {
                $this->reminder = -intval($trigger);
            }
        }

    }

    /**
     * Return if this is a request for an all day meeting.
     *
     * @return boolean
     */
    protected function _isAllDay()
    {
        return ($this->starttime->hour == 0 && $this->starttime->min == 0 && $this->starttime->sec == 0 &&
             (($this->endtime->hour == 23 && $this->endtime->min == 59) ||
              ($this->endtime->hour == 0 && $this->endtime->min == 0 && $this->endtime->sec == 0 &&
               ($this->endtime->mday > $this->starttime->mday ||
                $this->endtime->month > $this->starttime->month ||
                $this->endtime->year > $this->starttime->year))));
    }

}