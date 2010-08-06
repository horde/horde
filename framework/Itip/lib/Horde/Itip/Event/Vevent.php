<?php
/**
 * A wrapper for vEvent iCalender data.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * A wrapper for vEvent iCalender data.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 * Copyright 2004-2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Itip_Event_Vevent
implements Horde_Itip_Event
{
    /**
     * The wrapped vEvent.
     *
     * @var Horde_iCalendar_vevent
     */
    private $_vevent;

    /**
     * Constructor.
     *
     * @param Horde_iCalendar_vevent $vevent The iCalendar object that will be
     *                                       wrapped by this instance.
     */
    public function __construct(Horde_iCalendar_vevent $vevent)
    {
        $this->_vevent = $vevent;
    }

    /**
     * Returns the wrapped vEvent.
     *
     * @return Horde_iCalendar_vevent The wrapped event.
     */
    public function getVevent()
    {
        return $this->_vevent;
    }

    /**
     * Return the method of the iTip request.
     *
     * @return string The method of the request.
     */
    public function getMethod()
    {
        return $this->_vevent->getAttributeDefault('METHOD', 'REQUEST');
    }

    /**
     * Return the uid of the iTip event.
     *
     * @return string The uid of the event.
     */
    public function getUid()
    {
        return $this->_vevent->getAttributeDefault('UID', '');
    }

    /**
     * Return the summary for the event.
     *
     * @return string|PEAR_Error The summary.
     */
    public function getSummary()
    {
        return $this->_vevent->getAttributeDefault('SUMMARY', _("No summary available"));
    }

    /**
     * Return the start of the iTip event.
     *
     * @return string The start of the event.
     */
    public function getStart()
    {
        return $this->_vevent->getAttributeDefault('DTSTART', 0);
    }

    /**
     * Return the end of the iTip event.
     *
     * @return string The end of the event.
     */
    public function getEnd()
    {
        return $this->_vevent->getAttributeDefault('DTEND', 0);
    }

    /**
     * Return the organizer of the iTip event.
     *
     * @return string The organizer of the event.
     */
    public function getOrganizer()
    {
        return preg_replace('/^mailto:\s*/i', '', $this->_vevent->getAttributeDefault('ORGANIZER', ''));
    }

    /**
     * Copy the details from an event into this one.
     *
     * @param Horde_Itip_Event $event The event to copy from.
     *
     * @return NULL
     */
    public function copyEventInto(Horde_Itip_Event $event)
    {
        $this->copyUid($event);
        $this->copySummary($event);
        $this->copyDescription($event);
        $this->copyStart($event);
        $this->copyEndOrDuration($event);
        $this->copySequence($event);
        $this->copyLocation($event);
        $this->copyOrganizer($event);
    }

    /**
     * Set the attendee parameters.
     *
     * @param string $attendee    The mail address of the attendee.
     * @param string $common_name Common name of the attendee.
     * @param string $status      Attendee status (ACCPETED, DECLINED, TENTATIVE)
     *
     * @return NULL
     */
    public function setAttendee($attendee, $common_name, $status)
    {
        $this->_vevent->setAttribute(
            'ATTENDEE',
            'MAILTO:' . $attendee,
            array(
                'CN' => $common_name,
                'PARTSTAT' => $status
            )
        );
    }

    public function getKolabObject()
    {
        $object = array();
        $object['uid'] = $this->getUid();

        $org_params = $this->_vevent->getAttribute('ORGANIZER', true);
        if (!is_a( $org_params, 'PEAR_Error')) {
            if (!empty($org_params[0]['CN'])) {
                $object['organizer']['display-name'] = $org_params[0]['CN'];
            }
            $orgemail = $this->_vevent->getAttributeDefault('ORGANIZER', '');
            if (preg_match('/mailto:(.*)/i', $orgemail, $regs )) {
                $orgemail = $regs[1];
            }
            $object['organizer']['smtp-address'] = $orgemail;
        }
        $object['summary'] = $this->_vevent->getAttributeDefault('SUMMARY', '');
        $object['location'] = $this->_vevent->getAttributeDefault('LOCATION', '');
        $object['body'] = $this->_vevent->getAttributeDefault('DESCRIPTION', '');
        $dtend = $this->_vevent->getAttributeDefault('DTEND', '');
        if (is_array($dtend)) {
            $object['_is_all_day'] = true;
        }
        $start = new Horde_Kolab_Resource_Epoch($this->getStart());
        $object['start-date'] = $start->getEpoch();
        $end = new Horde_Kolab_Resource_Epoch($dtend);
        $object['end-date'] = $end->getEpoch();

        $attendees = $this->_vevent->getAttribute('ATTENDEE');
        if (!is_a( $attendees, 'PEAR_Error')) {
            $attendees_params = $this->_vevent->getAttribute('ATTENDEE', true);
            if (!is_array($attendees)) {
                $attendees = array($attendees);
            }
            if (!is_array($attendees_params)) {
                $attendees_params = array($attendees_params);
            }

            $object['attendee'] = array();
            for ($i = 0; $i < count($attendees); $i++) {
                $attendee = array();
                if (isset($attendees_params[$i]['CN'])) {
                    $attendee['display-name'] = $attendees_params[$i]['CN'];
                }

                $attendeeemail = $attendees[$i];
                if (preg_match('/mailto:(.*)/i', $attendeeemail, $regs)) {
                    $attendeeemail = $regs[1];
                }
                $attendee['smtp-address'] = $attendeeemail;

                if (!isset($attendees_params[$i]['RSVP'])
                    || $attendees_params[$i]['RSVP'] == 'FALSE') {
                    $attendee['request-response'] = false;
                } else {
                    $attendee['request-response'] = true;
                }

                if (isset($attendees_params[$i]['ROLE'])) {
                    $attendee['role'] = $attendees_params[$i]['ROLE'];
                }

                if (isset($attendees_params[$i]['PARTSTAT'])) {
                    $status = strtolower($attendees_params[$i]['PARTSTAT']);
                    switch ($status) {
                    case 'needs-action':
                    case 'delegated':
                        $attendee['status'] = 'none';
                        break;
                    default:
                        $attendee['status'] = $status;
                        break;
                    }
                }

                $object['attendee'][] = $attendee;
            }
        }

        // Alarm
        $valarm = $this->_vevent->findComponent('VALARM');
        if ($valarm) {
            $trigger = $valarm->getAttribute('TRIGGER');
            if (!is_a($trigger, 'PEAR_Error')) {
                $p = $valarm->getAttribute('TRIGGER', true);
                if ($trigger < 0) {
                    // All OK, enter the alarm into the XML
                    // NOTE: The Kolab XML format seems underspecified
                    // wrt. alarms currently...
                    $object['alarm'] = -$trigger / 60;
                }
            }
        }

        // Recurrence
        $rrule_str = $this->_vevent->getAttribute('RRULE');
        if (!is_a($rrule_str, 'PEAR_Error')) {
            require_once 'Horde/Date/Recurrence.php';
            $recurrence = new Horde_Date_Recurrence(time());
            $recurrence->fromRRule20($rrule_str);
            $object['recurrence'] = $recurrence->toHash();
        }

        return $object;
    }

    public function setAccepted($resource)
    {
        // Update our status within the iTip request and send the reply
        $this->_vevent->setAttribute('STATUS', 'CONFIRMED', array(), false);
        $attendees = $this->_vevent->getAttribute('ATTENDEE');
        if (!is_array($attendees)) {
            $attendees = array($attendees);
        }
        $attparams = $this->_vevent->getAttribute('ATTENDEE', true);
        foreach ($attendees as $i => $attendee) {
            $attendee = preg_replace('/^mailto:\s*/i', '', $attendee);
            if ($attendee != $resource) {
                continue;
            }

            $attparams[$i]['PARTSTAT'] = 'ACCEPTED';
            if (array_key_exists('RSVP', $attparams[$i])) {
                unset($attparams[$i]['RSVP']);
            }
        }

        // Re-add all the attendees to the event, using our updates status info
        $firstatt = array_pop($attendees);
        $firstattparams = array_pop($attparams);
        $this->_vevent->setAttribute('ATTENDEE', $firstatt, $firstattparams, false);
        foreach ($attendees as $i => $attendee) {
            $this->_vevent->setAttribute('ATTENDEE', $attendee, $attparams[$i]);
        }
    }

    /**
     * Set the uid of the iTip event.
     *
     * @param string $uid The uid of the event.
     *
     * @return NULL
     */
    private function setUid($uid)
    {
        $this->_vevent->setAttribute('UID', $uid);
    }

    /**
     * Copy the uid from the request into the provided iTip instance.
     *
     * @return NULL
     */
    private function copyUid(Horde_Itip_Event $itip)
    {
        $itip->setUid($this->getUid());
    }

    /**
     * Set the summary for the event.
     *
     * @param string $summary The summary.
     *
     * @return NULL
     */
    private function setSummary($summary)
    {
        $this->_vevent->setAttribute('SUMMARY', $summary);
    }

    /**
     * Copy the summary from the request into the provided iTip instance.
     *
     * @return NULL
     */
    private function copySummary(Horde_Itip_Event $itip)
    {
        $itip->setSummary($this->getSummary());
    }

    /**
     * Does the event have a description?
     *
     * @return boolean True if it has a description, false otherwise.
     */
    private function hasDescription()
    {
        return !($this->_vevent->getAttribute('DESCRIPTION') instanceOf PEAR_Error);
    }

    /**
     * Return the description for the event.
     *
     * @return string|PEAR_Error The description.
     */
    private function getDescription()
    {
        return $this->_vevent->getAttribute('DESCRIPTION');
    }

    /**
     * Set the description for the event.
     *
     * @param string $description The description.
     *
     * @return NULL
     */
    private function setDescription($description)
    {
        $this->_vevent->setAttribute('DESCRIPTION', $description);
    }

    /**
     * Copy the description from the request into the provided iTip instance.
     *
     * @return NULL
     */
    private function copyDescription(Horde_Itip_Event $itip)
    {
        if ($this->hasDescription()) {
            $itip->setDescription($this->getDescription());
        }
    }

    /**
     * Return the start parameters of the iTip event.
     *
     * @return array The start parameters of the event.
     */
    public function getStartParameters()
    {
        $parameters = $this->_vevent->getAttribute('DTSTART', true);
        return array_pop($parameters);
    }

    /**
     * Set the start of the iTip event.
     *
     * @param string $start      The start of the event.
     * @param array  $parameters Additional parameters.
     *
     * @return NULL
     */
    private function setStart($start, $parameters)
    {
        $this->_vevent->setAttribute('DTSTART', $start, $parameters);
    }

    /**
     * Copy the start time from the request into the provided iTip instance.
     *
     * @return NULL
     */
    private function copyStart(Horde_Itip_Event $itip)
    {
        $itip->setStart($this->getStart(), $this->getStartParameters());
    }

    /**
     * Return the end parameters of the iTip event.
     *
     * @return array The end parameters of the event.
     */
    private function getEndParameters()
    {
        $parameters = $this->_vevent->getAttribute('DTEND', true);
        return array_pop($parameters);
    }

    /**
     * Set the end of the iTip event.
     *
     * @param string $end        The end of the event.
     * @param array  $parameters Additional parameters.
     *
     * @return NULL
     */
    private function setEnd($end, $parameters)
    {
        $this->_vevent->setAttribute('DTEND', $end, $parameters);
    }

    /**
     * Return the duration for the event.
     *
     * @return string|PEAR_Error The duration of the event.
     */
    private function getDuration()
    {
        return $this->_vevent->getAttribute('DURATION');
    }

    /**
     * Return the duration parameters of the iTip event.
     *
     * @return array The duration parameters of the event.
     */
    private function getDurationParameters()
    {
        $parameters = $this->_vevent->getAttribute('DURATION', true);
        return array_pop($parameters);
    }

    /**
     * Set the duration of the iTip event.
     *
     * @param string $duration   The duration of the event.
     * @param array  $parameters Additional parameters.
     *
     * @return NULL
     */
    private function setDuration($duration, $parameters)
    {
        $this->_vevent->setAttribute('DURATION', $duration, $parameters);
    }

    /**
     * Copy the end time or event duration from the request into the provided
     * iTip instance.
     *
     * @return NULL
     */
    private function copyEndOrDuration(Horde_Itip_Event $itip)
    {
        try {
            $itip->setEnd($this->getEnd(), $this->getEndParameters());
        } catch (Horde_Icalendar_Exception $e) {
            $itip->setDuration($this->getDuration(), $this->getDurationParameters());
        }
    }

    /**
     * Return the sequence for the event.
     *
     * @return string|PEAR_Error The sequence.
     */
    private function getSequence()
    {
        return $this->_vevent->getAttributeDefault('SEQUENCE', 0);
    }

    /**
     * Set the sequence for the event.
     *
     * @param string $sequence The sequence.
     *
     * @return NULL
     */
    private function setSequence($sequence)
    {
        $this->_vevent->setAttribute('SEQUENCE', $sequence);
    }
    /**
     * Copy the sequence from the request into the provided iTip instance.
     *
     * @return NULL
     */
    private function copySequence(Horde_Itip_Event $itip)
    {
        $itip->setSequence($this->getSequence());
    }

    /**
     * Return the location for the event.
     *
     * @return string|PEAR_Error The location.
     */
    private function getLocation()
    {
        return $this->_vevent->getAttribute('LOCATION');
    }

    /**
     * Set the location for the event.
     *
     * @param string $location The location.
     *
     * @return NULL
     */
    private function setLocation($location)
    {
        $this->_vevent->setAttribute('LOCATION', $location);
    }

    /**
     * Copy the location from the request into the provided iTip instance.
     *
     * @return NULL
     */
    private function copyLocation(Horde_Itip_Event $itip)
    {
        try {
            $itip->setLocation($this->getLocation());
        } catch (Horde_Icalendar_Exception $e) {
        }
    }

    /**
     * Return the organizer for the event.
     *
     * @return string|PEAR_Error The organizer of the event.
     */
    private function getRawOrganizer()
    {
        return $this->_vevent->getAttribute('ORGANIZER');
    }

    /**
     * Return the organizer parameters of the iTip event.
     *
     * @return array The organizer parameters of the event.
     */
    private function getOrganizerParameters()
    {
        $parameters = $this->_vevent->getAttribute('ORGANIZER', true);
        return array_pop($parameters);
    }

    /**
     * Set the organizer of the iTip event.
     *
     * @param string $organizer  The organizer of the event.
     * @param array  $parameters Additional parameters.
     *
     * @return NULL
     */
    private function setOrganizer($organizer, $parameters)
    {
        $this->_vevent->setAttribute('ORGANIZER', $organizer, $parameters);
    }

    /**
     * Copy the organizer from the request into the provided iTip instance.
     *
     * @return NULL
     */
    private function copyOrganizer(Horde_Itip_Event $itip)
    {
        $itip->setOrganizer($this->getRawOrganizer(), $this->getOrganizerParameters());
    }
}