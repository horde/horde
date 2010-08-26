<?php
/**
 * A wrapper for vEvent iCalender data.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */

/**
 * A wrapper for vEvent iCalender data.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 * Copyright 2004-2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * {@link http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL}.
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 *
 * @todo Clean this class up. Accessing private methods for copying the object
 * is not nice. Reconsider if an interface is really needed. See also PMD
 * report.
 */
class Horde_Itip_Event_Vevent
implements Horde_Itip_Event
{
    /**
     * The wrapped vEvent.
     *
     * @var Horde_Icalendar_Vevent
     */
    private $_vevent;

    /**
     * Constructor.
     *
     * @param Horde_Icalendar_Vevent $vevent The iCalendar object that will be
     *                                       wrapped by this instance.
     */
    public function __construct(Horde_Icalendar_Vevent $vevent)
    {
        $this->_vevent = $vevent;
    }

    /**
     * Returns the wrapped vEvent.
     *
     * @return Horde_Icalendar_Vevent The wrapped event.
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
        return $this->_vevent->getAttribute('UID');
    }

    /**
     * Return the summary for the event.
     *
     * @return string The summary.
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
     *
     * @todo Parse mailto using parse_url
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
            'mailto:' . $attendee,
            array(
                'CN' => $common_name,
                'PARTSTAT' => $status
            )
        );
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
     * Return the description for the event.
     *
     * @return string The description.
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
        try {
            $itip->setDescription($this->getDescription());
        } catch (Horde_Icalendar_Exception $e) {
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
     * @return string The duration of the event.
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
     * @return string The sequence.
     */
    private function getSequence()
    {
        return $this->_vevent->getAttribute('SEQUENCE');
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
        try {
            $itip->setSequence($this->getSequence());
        } catch (Horde_Icalendar_Exception $e) {
        }
    }

    /**
     * Return the location for the event.
     *
     * @return string The location.
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
     * @return string The organizer of the event.
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