<?php
/**
 * This class implements a Horde CalDAV backend for SabreDAV.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * @package Sabre
 * @author  Jan Schneider <jan@horde.org>
 * @license @todo
 */
class Sabre_CalDAV_Backend_Horde extends Sabre_CalDAV_Backend_Abstract
{
    /**
     * @var Horde_Registry
     */
    protected $_registry;

    public function __construct(Horde_Registry $registry)
    {
        $this->_registry = $registry;
    }

    /**
     * Returns a list of calendars for a users' uri 
     *
     * The uri is not a full path, just the actual last part
     * 
     * @param string $userUri 
     * @return array 
     */
    public function getCalendarsForUser($userUri)
    {
        // If possible we should ressamble the existing WebDAV structure with
        // CalDAV. Listing just the calendars is not sufficient for this.
        $result = array();
        $owners = $this->_registry->calendar->browse('', array('name'));
        foreach (reset($owners) as $owner) {
            $calendars = $this->_registry->calendar->browse($owner['name'], array('name'));
            foreach ($calendars as $name => $calendar) {
                $result[] = substr($name, strrpos($name, '/'));
            }
        }
        return $result;

        // Alternative solution (without hierarchy):
        return $this->_registry->calendar->listCalendars();
    }

    /**
     * Creates a new calendar for a user
     *
     * The userUri and calendarUri are not full paths, just the 'basename'.
     *
     * @param string $userUri
     * @param string $calendarUri
     * @param string $displayName
     * @param string $description
     * @return void
     */
    public function createCalendar($userUri, $calendarUri, $displayName,
                                   $description)
    {
        // To be implemented. We can't create the Horde_Share directly,
        // because each application defines its own share namespace
        // (e.g. horde.shares.kronolith), but this namespace is unknown
        // outside of the application.
        // Why Uri? Is it anything different than the plain user name and
        // calendar ID?
        $this->_registry->calendar->createCalendar($userUri, $calendarUri, $displayName, $description);
    } 

    /**
     * Updates a calendar's basic information 
     * 
     * @param string $calendarId
     * @param string $displayName 
     * @param string $description 
     * @return void
     */
    public function updateCalendar($calendarId, $displayName, $description)
    {
        // To be implemented.
        // ID == calendar name in Horde.
        $this->_registry->calendar->updateCalendar($calendarId, $displayName, $description);
    }

    /**
     * Returns all calendar objects within a calendar object. 
     * 
     * @param string $calendarId 
     * @return array 
     */
    public function getCalendarObjects($calendarId)
    {
        // browse() assumes an intermediate owner directory at the moment.
        $owner = 'foo';
        $events = $this->_registry->calendar->browse($owner . '/' . $calendarId, array('name'));

        // Return format?
        return $events;
    }

    /**
     * Returns information from a single calendar object, based on it's object
     * uri.
     * 
     * @param mixed $calendarId 
     * @param string $objectUri 
     * @return array 
     */
    public function getCalendarObject($calendarId, $objectUri)
    {
        // browse() assumes an intermediate owner directory at the moment.
        $owner = 'foo';
        $event = $this->_registry->calendar->browse($owner . '/' . $calendarId . '/' . $objectUri);
        return array('calendardata' => $event['data'],
                     'lastmodified' => $event['mtime']);
        // What else to return? Mime type?
    }

    /**
     * Creates a new calendar object. 
     * 
     * @param mixed $calendarId 
     * @param string $objectUri 
     * @param string $calendarData 
     * @return void
     */
    public function createCalendarObject($calendarId, $objectUri, $calendarData)
    {
        // No Content-Type?
        // We don't accept object ids at the moment.
        $this->_registry->import($calendarData, 'text/calendar', $calendarId);
    }

    /**
     * Updates an existing calendarobject, based on it's uri. 
     * 
     * @param mixed $calendarId 
     * @param string $objectUri 
     * @param string $calendarData 
     * @return void
     */
    public function updateCalendarObject($calendarId, $objectUri, $calendarData)
    {
        // No Content-Type?
        // Object ID or UID?
        $this->_registry->import($objectUri, $calendarData, 'text/calendar');
    }

    /**
     * Deletes an existing calendar object. 
     * 
     * @param mixed $calendarId 
     * @param string $objectUri 
     * @return void
     */
    public function deleteCalendarObject($calendarId, $objectUri)
    {
        // Object ID or UID?
        $this->_registry->delete($objectUri);
    }

}
