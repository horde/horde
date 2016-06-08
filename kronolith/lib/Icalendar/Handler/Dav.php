<?php
/**
 * Copyright 2015-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 * @category Horde
 */

/**
 * Wraps logic responsible for importing iCalendar data via DAV taking into
 * account necessary steps to deal with recurrence series and exceptions.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 * @category Horde
 */
class Kronolith_Icalendar_Handler_Dav extends Kronolith_Icalendar_Handler_Base
{
    /**
     * The DAV storage driver.
     *
     * @var Horde_Dav_Storage_Base
     */
    protected $_dav;

    /**
     * The calendar id to be imported into.
     *
     * @var string
     */
    protected $_calendar;

    /**
     * Temporary cache of the existing copy of an event being replaced.
     *
     * @var Kronolith_Event
     */
    protected $_existingEvent;

    /**
     * List of attendees that should not be sent iTip notifications.
     *
     * @var  array
     */
    protected $_noItips = array();

    /**
     * List of attendess that have been previously invited. Used to detect if
     * attendees are removed and to send ITIP_CANCEL to these attendees.
     *
     * @var Kronolith_Attendee_List
     */
    protected $_oldAttendees;

    /**
     *
     * @param Horde_Icalendar  $iCal    The iCalendar data.
     * @param Kronolith_Driver $driver  The Kronolith driver.
     * @param array            $params  Any additional parameters needed for
     *                                  the importer. For this driver we
     *                                  require: 'object' - contains the DAV
     *                                  identifier for the (base) event.
     */
    public function __construct(
        Horde_Icalendar $iCal, Kronolith_Driver $driver, $params = array())
    {
        parent::__construct($iCal, $driver, $params);
        $this->_dav = $GLOBALS['injector']->getInstance('Horde_Dav_Storage');
        $this->_calendar = $this->_driver->calendar;
    }

    /**
     * Responsible for any logic needed before the event is saved. Called for
     * EVERY component in the iCalendar object. Returning false from this method
     * will cause the current component to be ignored. Returning true causes it
     * to be processed.
     *
     * @param  Horde_Icalendar $component  The iCalendar component.
     *
     * @return boolean  True to continue processing, false to ignore.
     */
    protected function _preSave($component)
    {
        // Short circuit if we know we don't pass the parent test.
        if (!parent::_preSave($component)) {
            return false;
        }

        // Ensure we start with a fresh state.
        $this->_existingEvent = null;
        $this->_oldAttendees = new Kronolith_Attendee_List();
        $this->_noItips = array();

        // Get the internal id of the existing copy of the event, if it exists.
        try {
            $existing_id = $this->_dav->getInternalObjectId($this->_params['object'], $this->_calendar)
                ?: preg_replace('/\.ics$/', '', $this->_params['object']);
        } catch (Horde_Dav_Exception $e) {
            $existing_id = $this->_params['object'];
        }

        // Check that we don't have newer information already on the server.
        try {
            // Exception event, so we can't compare timestamps using ids.
            // Instead look for baseid/recurrence-id.
            $rid = $component->getAttribute('RECURRENCE-ID');
            $uid = $component->getAttribute('UID');
            if (!empty($rid) && !empty($uid)) {
                $search = new stdClass();
                $search->baseid = $uid;
                $search->recurrenceid = $rid;
                $results = $this->_driver->search($search);
                foreach ($results as $days) {
                    foreach ($days as $exception) {
                        // Should only be one...
                        $modified = $exception->modified
                            ?: $exception->created;
                    }
                }
            }
        } catch (Horde_Icalendar_Exception $e) {
            // Base event or event with no recurrence.
           try {
                $this->_existingEvent = $this->_driver->getEvent($existing_id);
                $this->_existingEvent->loadHistory();
                $modified = $this->_existingEvent->modified
                    ?: $this->_existingEvent->created;

                // Get list of existing attendees.
                $this->_oldAttendees = $this->_existingEvent->attendees;
            } catch (Horde_Exception_NotFound $e) {
                $this->_existingEvent = null;
            }
        }

        try {
            if (!empty($modified) &&
                $component->getAttribute('LAST-MODIFIED') < $modified->timestamp()) {
                 /* LAST-MODIFIED timestamp of existing entry is newer:
                 * don't replace it. */
                return false;
            }
        } catch (Horde_Icalendar_Exception $e) {}

        try {
            $organizer = $component->getAttribute('ORGANIZER');
            $organizer_params = $component->getAttribute('ORGANIZER', true);
            if (!empty($organizer_params[0]['SCHEDULE-AGENT']) &&
                $organizer_params[0]['SCHEDULE-AGENT'] == 'CLIENT' ||
                $organizer_params[0]['SCHEDULE-AGENT'] == 'NONE') {
                $tmp = str_replace(array('MAILTO:', 'mailto:'), '', $organizer);
                $tmp = new Horde_Mail_Rfc822_Address($tmp);
                $this->_noItips[] = $tmp->bare_address;
            }
        } catch (Horde_Icalendar_Exception $e) {}
        try {
            $attendee = $component->getAttribute('ATTENDEE');
            $params = $component->getAttribute('ATTENDEE', true);
            for ($i = 0; $i < count($attendee); ++$i) {
                if (!empty($params[$i]['SCHEDULE-AGENT']) &&
                    $params[$i]['SCHEDULE-AGENT'] == 'CLIENT' ||
                    $params[$i]['SCHEDULE-AGENT'] == 'NONE') {
                    $tmp = str_replace(array('MAILTO:', 'mailto:'), '', $attendee[$i]);
                    $tmp = new Horde_Mail_Rfc822_Address($tmp);
                    $this->_noItips[] = $tmp->bare_address;
                }
            }
        } catch (Horde_Icalendar_Exception $e) {}

        return true;
    }

    protected function _postSave(Kronolith_Event $event)
    {
        global $registry;

        if (!$this->_dav->getInternalObjectId($this->_params['object'], $this->_calendar)) {
            $this->_dav->addObjectMap($event->id, $this->_params['object'], $this->_calendar);
        }

        // Send iTip messages if necessary.
	    $type = Kronolith::ITIP_REQUEST;
        if ($event->organizer && !Kronolith::isUserEmail($event->creator, $event->organizer)) {
            $type = Kronolith::ITIP_REPLY;
        }
        $event_copy = clone($event);
        $event_copy->attendees = $event->attendees->without($this->_noItips);
        $notification = new Horde_Notification_Handler(new Horde_Notification_Storage_Object());
        Kronolith::sendITipNotifications(
            $event_copy,
            $notification,
            $type
        );

        // Send ITIP_CANCEL to any attendee that was removed, but only if this
        // is the ORGANZIER's copy of the event.
        if (empty($event->organizer) ||
            ($registry->getAuth() == $event->creator &&
             Kronolith::isUserEmail($event->creator, $event->organizer))) {

            $removed_attendees = new Kronolith_Attendee_List();
            foreach ($this->_oldAttendees as $old_attendee) {
                if (!$event->attendees->has($old_attendee)) {
                    $removed_attendees->add($old_attendee);
                }
            }
            if (count($removed_attendees)) {
                $cancelEvent = clone $event;
                Kronolith::sendITipNotifications(
                    $cancelEvent,
                    $notification,
                    Kronolith::ITIP_CANCEL,
                    null,
                    null,
                    $removed_attendees
                );
            }
        }
    }

}
