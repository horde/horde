<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class Horde_ActiveSync_Driver_Horde_Connector_Registry
{
    /**
     * @var Horde_Registry
     */
    private $_registry;

    /**
     * Const'r
     *
     */
    public function __construct($params = array())
    {
        if (empty($params['registry'])) {
            throw new Horde_ActiveSync_Exception('Missing required Horde_Registry object.');
        }

        $this->_registry = $params['registry'];
    }

    /**
     * Get a list of events from horde's calendar api
     *
     * @param timestamp $startstamp    The start of time period.
     * @param timestamp $endstamp      The end of time period
     * @param string $calendar         The calendar(s) to get events for
     *
     * @return array
     */
    public function calendar_listEvents($startstamp, $endstamp, $calendar)
    {
        $result = $this->_registry->calendar->listEvents(
                $startstamp,   // Start
                $endstamp,     // End
                $calendar,     // Calendar
                false,         // Recurrence
                false,         // Alarms only
                false,         // Show remote
                true,          // Hide exception events
                false);        // Don't return multi-day events on *each* day
        return $result;
    }

    /**
     * Get a list of event uids that have had $action happen since $from_ts.
     * Optionally limits to a specific calendar.
     *
     * @param string $action      The action to check for (add, modify, delete)
     * @param timestamp $from_ts  The timestamp to start checking from
     * @param string $calendar
     */
    public function calendar_listBy($action, $from_ts, $calendar = null)
    {
        return $this->_registry->calendar->listBy($action, $from_ts, $calendar);
    }

    /**
     * Export the specified calendar in the specified content type
     *
     * @param string $uid          The calendar id
     * @param string $contentType  The content type specifier
     *
     * @return The iCalendar representation of the calendar
     */
    public function calendar_export($uid)
    {
        $result = $this->_registry->calendar->export($uid, 'activesync');
        return $result;
    }

    /**
     * Import an event into Horde's calendar store.
     *
     * @param Horde_ActiveSync_Message_Appointmetn $content  The event content
     * @param string $contentType                            The content type of $content
     * @param string $calendar                               The calendar to import event into
     *
     * @return string  The event's UID
     */
    public function calendar_import($content, $calendar = null)
    {
        return $this->_registry->calendar->import($content, 'activesync', $calendar);
    }

    /**
     * Replcae the event with new data
     *
     * @param string $uid          The UID of the event to replace
     * @param string $content      The new event content
     * @param string $contentType  The content type of $content
     *
     * @return boolean
     */
    public function calendar_replace($uid, $content)
    {
        $result = $this->_registry->calendar->replace($uid, $content, 'activesync');
        return $result;
    }

    /**
     * Delete an event from Horde's calendar storage
     *
     * @param string $uid  The UID of the event to delete
     *
     * @return boolean
     */
    public function calendar_delete($uid)
    {
        $result = $this->_registry->calendar->delete($uid);
        return $result;
    }

    /**
     * Return the timestamp for the last time $action was performed.
     *
     * @param string $uid     The UID of the event we are interested in.
     * @param string $action  The action we are interested in (add, modify...)
     *
     * @return timestamp
     */
    public function calendar_getActionTimestamp($uid, $action)
    {
        $result = $this->_registry->calendar->getActionTimestamp($uid, $action);
        return $result;
    }

    /**
     * Get a list of all contacts a user can see
     *
     * @return array of contact UIDs
     */
    public function contacts_list()
    {
        $result = $this->_registry->contacts->listContacts();
        return $result;
    }

    /**
     * Export the specified contact from Horde's contacts storage
     *
     * @param string $uid          The contact's UID
     * @param string $contentType  The content type to export in
     *                             (text/directory text/vcard text/x-vcard)
     *
     * @return the contact in the requested content type
     */
    public function contacts_export($uid, $contentType)
    {
        $result = $this->_registry->contacts->export($uid, $contentType);
        return $result;
    }

    /**
     * Import the provided contact data into Horde's contacts storage
     *
     * @param string $content      The contact data
     * @param string $contentType  The content type specifier of $content
     * @param string $source       The contact source to import to
     *
     * @return boolean
     */
    public function contacts_import($content, $contentType, $import_source = null)
    {
        $result = $this->_registry->contacts->import($content, $contentType, $import_source);
        return $result;
    }

    /**
     * Replace the specified contact with the data provided.
     *
     * @param string $uid          The UID of the contact to replace
     * @param string $content      The contact data
     * @param string $contentType  The content type of $content
     * @param string $sources      The sources where UID will be replaced
     *
     * @return boolean
     */
    public function contacts_replace($uid, $content, $contentType, $sources = null)
    {
        $result = $this->_registry->contacts->replace($uid, $content, $contentType, $sources);
        return $result;
    }

    /**
     * Delete the specified contact
     *
     * @param string $uid  The UID of the contact to remove
     *
     * @return bolean
     */
    public function contacts_delete($uid)
    {
        $result = $this->_registry->contacts->delete($uid);
        return $result;
    }

    /**
     * Get the timestamp of the most recent occurance of $action for the
     * specifed contact
     *
     * @param string $uid     The UID of the contact to search
     * @param string $action  The action to lookup
     *
     * @return timestamp
     */
    public function contacts_getActionTimestamp($uid, $action)
    {
        $result = $this->_registry->contacts->getActionTimestamp($uid, $action);
        return $result;
    }

    public function tasks_listTasks()
    {
        $app = $this->horde_hasInterface('tasks');
        $tasklist = $this->horde_getPref($app, 'default_tasklist');
        return $this->_registry->tasks->listTaskUids($tasklist);
    }

    public function tasks_export($uid)
    {
        return $this->_registry->tasks->export($uid, 'activesync');
    }

    public function tasks_import($message)
    {
        return $this->_registry->tasks->import($message, 'activesync');
    }

    public function tasks_replace($uid, $message)
    {
        return $this->_registry->tasks->replace($uid, $message, 'activesync');
    }

    public function tasks_delete($id)
    {
        return $this->_registry->tasks->delete($id);
    }

    public function tasks_getActionTimestamp($uid, $action)
    {
        return $this->_registry->tasks->getActionTimestamp($uid, $action);
    }

    public function horde_listApis()
    {
        return $this->_registry->horde->listAPIs();
    }

    public function horde_getPref($app, $pref)
    {
        return $this->_registry->horde->getPreference($app, $pref);
    }

    public function horde_hasInterface($api)
    {
        return $this->_registry->hasInterface($api);
    }

}