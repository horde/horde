<?php
/**
 * Handles Itip data.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Handles Itip data.
 *
 * Copyright 2004-2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Resource_Itip
{

    /**
     * Reference to the iCalendar iTip object.
     *
     * @var Horde_iCalendar_vevent
     */
    private $_itip;

    /**
     * Constructor.
     *
     * @param Horde_iCalendar_vevent $itip Reference to the iCalendar iTip object.
     */
    public function __construct($itip)
    {
        $this->_itip = $itip;
    }

    public function __call($method, $args)
    {
        return call_user_func_array(array($this->_itip, $method), $args);
    }

    /**
     * Return the method of the iTip request.
     *
     * @return string The method of the request.
     */
    public function getMethod()
    {
        return $this->_itip->getAttributeDefault('METHOD', 'REQUEST');
    }

    /**
     * Return the uid of the iTip event.
     *
     * @return string The uid of the event.
     */
    public function getUid()
    {
        return $this->_itip->getAttributeDefault('UID', '');
    }

    /**
     * Return the organizer of the iTip event.
     *
     * @return string The organizer of the event.
     */
    public function getOrganizer()
    {
        return preg_replace('/^mailto:\s*/i', '', $this->_itip->getAttributeDefault('ORGANIZER', ''));
    }

    /**
     * Return the summary of the iTip event.
     *
     * @return string The summary of the event.
     */
    public function getSummary()
    {
        return $this->_itip->getAttributeDefault('SUMMARY', '');
    }

    /**
     * Return the start of the iTip event.
     *
     * @return string The start of the event.
     */
    public function getStart()
    {
        return $this->_itip->getAttributeDefault('DTSTART', 0);
    }

    /**
     * Return the end of the iTip event.
     *
     * @return string The end of the event.
     */
    public function getEnd()
    {
        return $this->_itip->getAttributeDefault('DTEND', 0);
    }

    public function getKolabObject()
    {
        $object = array();
        $object['uid'] = $this->_itip->getAttributeDefault('UID', '');

        $org_params = $this->_itip->getAttribute('ORGANIZER', true);
        if (!is_a( $org_params, 'PEAR_Error')) {
            if (!empty($org_params[0]['CN'])) {
                $object['organizer']['display-name'] = $org_params[0]['CN'];
            }
            $orgemail = $this->_itip->getAttributeDefault('ORGANIZER', '');
            if (preg_match('/mailto:(.*)/i', $orgemail, $regs )) {
                $orgemail = $regs[1];
            }
            $object['organizer']['smtp-address'] = $orgemail;
        }
        $object['summary'] = $this->_itip->getAttributeDefault('SUMMARY', '');
        $object['location'] = $this->_itip->getAttributeDefault('LOCATION', '');
        $object['body'] = $this->_itip->getAttributeDefault('DESCRIPTION', '');
        $dtend = $this->_itip->getAttributeDefault('DTEND', '');
        if (is_array($dtend)) {
            $object['_is_all_day'] = true;
        }
        $start = new Horde_Kolab_Resource_Epoch($this->getStart());
        $object['start-date'] = $start->getEpoch();
        $end = new Horde_Kolab_Resource_Epoch($dtend);
        $object['end-date'] = $end->getEpoch();

        $attendees = $this->_itip->getAttribute('ATTENDEE');
        if (!is_a( $attendees, 'PEAR_Error')) {
            $attendees_params = $this->_itip->getAttribute('ATTENDEE', true);
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
        $valarm = $this->_itip->findComponent('VALARM');
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
            } else {
                Horde::logMessage('No TRIGGER in VALARM. ' . $trigger->getMessage(), 'ERR');
            }
        }

        // Recurrence
        $rrule_str = $this->_itip->getAttribute('RRULE');
        if (!is_a($rrule_str, 'PEAR_Error')) {
            require_once 'Horde/Date/Recurrence.php';
            $recurrence = new Horde_Date_Recurrence(time());
            $recurrence->fromRRule20($rrule_str);
            $object['recurrence'] = $recurrence->toHash();
        }

        Horde::logMessage(sprintf('Assembled event object: %s',
                                  print_r($object, true)), 'DEBUG');

        return $object;
    }

    public function setAccepted($resource)
    {
        // Update our status within the iTip request and send the reply
        $this->_itip->setAttribute('STATUS', 'CONFIRMED', array(), false);
        $attendees = $this->_itip->getAttribute('ATTENDEE');
        if (!is_array($attendees)) {
            $attendees = array($attendees);
        }
        $attparams = $this->_itip->getAttribute('ATTENDEE', true);
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
        $this->_itip->setAttribute('ATTENDEE', $firstatt, $firstattparams, false);
        foreach ($attendees as $i => $attendee) {
            $this->_itip->setAttribute('ATTENDEE', $attendee, $attparams[$i]);
        }
    }

}