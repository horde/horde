<?php
/**
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @author  Stuart Binge <omicron@mighty.co.za>
 * @package Kronolith
 */
class Kronolith_Event_Kolab extends Kronolith_Event
{
    /**
     * The type of the calender this event exists on.
     *
     * @var string
     */
    public $calendarType = 'internal';

    /**
     * Const'r
     *
     * @param Kronolith_Driver $driver  The backend driver that this event is
     *                                  stored in.
     * @param mixed $eventObject        Backend specific event object
     *                                  that this will represent.
     */
    public function __construct($driver, $eventObject = null)
    {
        static $alarm;

        /* Set default alarm value. */
        if (!isset($alarm) && isset($GLOBALS['prefs'])) {
            $alarm = $GLOBALS['prefs']->getValue('default_alarm');
        }

        $this->alarm = $alarm;

        parent::__construct($driver, $eventObject);
        $this->_tags = Kronolith::getTagger()->getTags($this->uid, 'event');
    }

    /**
     * Imports a backend specific event object.
     *
     * @param array $event  Backend specific event object that this object
     *                      will represent.
     */
    public function fromDriver($event)
    {
        $this->id = $event['uid'];
        $this->uid = $this->id;

        if (isset($event['summary'])) {
            $this->title = $event['summary'];
        }
        if (isset($event['body'])) {
            $this->description = $event['body'];
        }
        if (isset($event['location'])) {
            $this->location = $event['location'];
        }

        if (isset($event['sensitivity']) &&
            ($event['sensitivity'] == 'private' || $event['sensitivity'] == 'confidential')) {
            $this->private = true;
        }

        if (isset($event['organizer']['smtp-address'])) {
            if (Kronolith::isUserEmail($GLOBALS['registry']->getAuth(), $event['organizer']['smtp-address'])) {
                $this->creator = $GLOBALS['registry']->getAuth();
            } else {
                $this->creator = $event['organizer']['smtp-address'];
            }
        }

        if (isset($event['alarm'])) {
            $this->alarm = $event['alarm'];
        }

        $this->start = new Horde_Date($event['start-date']);
        $this->end = new Horde_Date($event['end-date']);
        $this->durMin = ($this->end->timestamp() - $this->start->timestamp()) / 60;

        if (isset($event['show-time-as'])) {
            switch ($event['show-time-as']) {
                case 'free':
                    $this->status = Kronolith::STATUS_FREE;
                    break;

                case 'tentative':
                    $this->status = Kronolith::STATUS_TENTATIVE;
                    break;

                case 'busy':
                case 'outofoffice':
                default:
                    $this->status = Kronolith::STATUS_CONFIRMED;
            }
        } else {
            $this->status = Kronolith::STATUS_CONFIRMED;
        }

        // Recurrence
        if (isset($event['recurrence'])) {
            $this->recurrence = new Horde_Date_Recurrence($this->start);
            $this->recurrence->fromHash($event['recurrence']);
        }

        // Attendees
        $attendee_count = 0;
        foreach($event['attendee'] as $attendee) {
            $name = $attendee['display-name'];
            $email = $attendee['smtp-address'];

            $role = $attendee['role'];
            switch ($role) {
            case 'optional':
                $role = Kronolith::PART_OPTIONAL;
                break;

            case 'resource':
                $role = Kronolith::PART_NONE;
                break;

            case 'required':
            default:
                $role = Kronolith::PART_REQUIRED;
                break;
            }

            $status = $attendee['status'];
            switch ($status) {
            case 'accepted':
                $status = Kronolith::RESPONSE_ACCEPTED;
                break;

            case 'declined':
                $status = Kronolith::RESPONSE_DECLINED;
                break;

            case 'tentative':
                $status = Kronolith::RESPONSE_TENTATIVE;
                break;

            case 'none':
            default:
                $status = Kronolith::RESPONSE_NONE;
                break;
            }

            // Attendees without an email address get added as incremented number
            if (empty($email)) {
                $email = $attendee_count;
                $attendee_count++;
            }

            $this->addAttendee($email, $role, $status, $name);
        }

        $this->initialized = true;
        $this->stored = true;
    }

    /**
     * Prepares this event to be saved to the backend.
     */
    public function toKolab()
    {
        $event = array();
        $event['uid'] = $this->uid;
        $event['summary'] = $this->title;
        $event['body']  = $this->description;
        $event['location'] = $this->location;
        $event['sensitivity'] = $this->private ? 'private' : 'public';

        // Only set organizer if this is a new event
        if ($this->_id == null) {
            $organizer = array(
                            'display-name' => Kronolith::getUserName($this->creator),
                            'smtp-address' => Kronolith::getUserEmail($this->creator)
                         );
            $event['organizer'] = $organizer;
        }

        if ($this->alarm != 0) {
            $event['alarm'] = $this->alarm;
        }

        $event['start-date'] = $this->start->timestamp();
        $event['end-date'] = $this->end->timestamp();
        $event['_is_all_day'] = $this->isAllDay();

        switch ($this->status) {
        case Kronolith::STATUS_FREE:
        case Kronolith::STATUS_CANCELLED:
            $event['show-time-as'] = 'free';
            break;

        case Kronolith::STATUS_TENTATIVE:
            $event['show-time-as'] = 'tentative';
            break;

        // No mapping for outofoffice
        case Kronolith::STATUS_CONFIRMED:
        default:
            $event['show-time-as'] = 'busy';
        }

        // Recurrence
        if ($this->recurs()) {
            $event['recurrence'] = $this->recurrence->toHash();
        } else {
            $event['recurrence'] = array();
        }


        // Attendees
        $event['attendee'] = array();
        foreach ($this->attendees as $email => $attendee) {
            $new_attendee = array();
            $new_attendee['display-name'] = $attendee['name'];

            // Attendee without an email address
            if (is_int($email)) {
                $new_attendee['smtp-address'] = '';
            } else {
                $new_attendee['smtp-address'] = $email;
            }

            switch ($attendee['attendance']) {
            case Kronolith::PART_OPTIONAL:
                $new_attendee['role'] = 'optional';
                break;

            case Kronolith::PART_NONE:
                $new_attendee['role'] = 'resource';
                break;

            case Kronolith::PART_REQUIRED:
            default:
                $new_attendee['role'] = 'required';
                break;
            }

            $new_attendee['request-response'] = '0';

            switch ($attendee['response']) {
            case Kronolith::RESPONSE_ACCEPTED:
                $new_attendee['status'] = 'accepted';
                break;

            case Kronolith::RESPONSE_DECLINED:
                $new_attendee['status'] = 'declined';
                break;

            case Kronolith::RESPONSE_TENTATIVE:
                $new_attendee['status'] = 'tentative';
                break;

            case Kronolith::RESPONSE_NONE:
            default:
                $new_attendee['status'] = 'none';
                break;
            }

            $event['attendee'][] = $new_attendee;
        }

        return $event;
    }

}
