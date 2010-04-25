<?php
/**
 * Registry connector for Horde backend. Provides the communication between
 * the Horde Registry on the local machine and the ActiveSync Horde driver.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
class Horde_ActiveSync_Driver_Horde_Connector_Registry
{
    /**
     * Horde registry
     *
     * @var Horde_Registry
     */
    private $_registry;

    /**
     * Const'r
     *
     * @param array $params  Configuration parameters. Requires:
     * <pre>
     *   'registry' - An instance of Horde_Registry
     * </pre>
     *
     * @return Horde_ActiveSync_Driver_Horde_Connector_Registry
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
     *
     * @return array
     */
    public function calendar_listEvents($startstamp, $endstamp)
    {
        $result = $this->_registry->calendar->listEvents(
                $startstamp,   // Start
                $endstamp,     // End
                null,          // Calendar
                false,         // Recurrence
                false,         // Alarms only
                false,         // Show remote
                true,          // Hide exception events
                false);        // Don't return multi-day events on *each* day
        return $result;
    }

    /**
     * Get a list of event uids that have had $action happen since $from_ts.
     *
     * @param string $action      The action to check for (add, modify, delete)
     * @param timestamp $from_ts  The timestamp to start checking from
     *
     * @return array  An array of event uids
     */
    public function calendar_listBy($action, $from_ts)
    {
        return $this->_registry->calendar->listBy($action, (int)$from_ts);
    }

    /**
     * Export the specified event as an ActiveSync message
     *
     * @param string $uid          The calendar id
     *
     * @return The iCalendar representation of the calendar
     */
    public function calendar_export($uid)
    {
        return $this->_registry->calendar->export($uid, 'activesync');
    }

    /**
     * Import an event into Horde's calendar store.
     *
     * @param Horde_ActiveSync_Message_Appointment $content  The event content
     * @param string $calendar                               The calendar to import event into
     *
     * @return string  The event's UID
     */
    public function calendar_import($content)
    {
        return $this->_registry->calendar->import($content, 'activesync');
    }

    /**
     * Replace the event with new data
     *
     * @param string $uid                                    The UID of the event to replace
     * @param Horde_ActiveSync_Message_Appointment $content  The new event content
     *
     * @return boolean
     */
    public function calendar_replace($uid, $content)
    {
        return $this->_registry->calendar->replace($uid, $content, 'activesync');
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
        return $this->_registry->calendar->delete($uid);
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
        return $this->_registry->calendar->getActionTimestamp($uid, $action);
    }

    /**
     * Get a list of all contacts a user can see
     *
     * @return array of contact UIDs
     */
    public function contacts_list()
    {
        return $this->_registry->contacts->listContacts();
    }

    /**
     * Export the specified contact from Horde's contacts storage
     *
     * @param string $uid          The contact's UID
     *
     * @return array The contact hash
     */
    public function contacts_export($uid)
    {
        return $this->_registry->contacts->export($uid, 'activesync');
    }

    /**
     * Import the provided contact data into Horde's contacts storage
     *
     * @param string $content      The contact data
     * @param string $source       The contact source to import to
     *
     * @return boolean
     */
    public function contacts_import($content, $import_source = null)
    {
        return $this->_registry->contacts->import($content, 'activesync', $import_source);
    }

    /**
     * Replace the specified contact with the data provided.
     *
     * @param string $uid          The UID of the contact to replace
     * @param string $content      The contact data
     * @param string $sources      The sources where UID will be replaced
     *
     * @return boolean
     */
    public function contacts_replace($uid, $content, $sources = null)
    {
        return $this->_registry->contacts->replace($uid, $content, 'activesync', $sources);
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
        return $this->_registry->contacts->delete($uid);
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
        return $this->_registry->contacts->getActionTimestamp($uid, $action);
    }

    /**
     * Get a list of contact uids that have had $action happen since $from_ts.
     *
     * @param string $action      The action to check for (add, modify, delete)
     * @param timestamp $from_ts  The timestamp to start checking from
     *
     * @return array  An array of event uids
     */
    public function contacts_listBy($action, $from_ts)
    {
        return $this->_registry->contacts->listBy($action, (int)$from_ts);
    }

    /**
     * List all tasks in the user's default tasklist.
     *
     * @return array  An array of task uids.
     */
    public function tasks_listTasks()
    {
        $app = $this->horde_hasInterface('tasks');
        $tasklist = $this->horde_getPref($app, 'default_tasklist');
        return $this->_registry->tasks->listTaskUids($tasklist);
    }

    public function tasks_listTaskLists()
    {
        return $this->_registry->tasks->listTaskLists();
    }

    /**
     * Export a single task from the backend.
     *
     * @param string $uid  The task uid
     *
     * @return Horde_ActiveSync_Message_Task  The task message object
     */
    public function tasks_export($uid)
    {
        return $this->_registry->tasks->export($uid, 'activesync');
    }

    /**
     * Importa a single task into the backend.
     *
     * @param Horde_ActiveSync_Message_Task $message  The task message object
     *
     * @return string  The newly added task's uid.
     */
    public function tasks_import($message)
    {
        return $this->_registry->tasks->import($message, 'activesync');
    }

    /**
     * Replace an existing task with the provided task.
     *
     * @param string $uid  The existing tasks's uid
     * @param Horde_ActiveSync_Message_Task $message  The task object
     *
     * @return boolean
     */
    public function tasks_replace($uid, $message)
    {
        return $this->_registry->tasks->replace($uid, $message, 'activesync');
    }

    /**
     * Delete a task from the backend.
     *
     * @param string $id  The task's uid
     *
     * @return boolean
     */
    public function tasks_delete($id)
    {
        return $this->_registry->tasks->delete($id);
    }

    /**
     * Return the timestamp for the last time $action was performed.
     *
     * @param string $uid     The UID of the task we are interested in.
     * @param string $action  The action we are interested in (add, modify...)
     *
     * @return timestamp
     */
    public function tasks_getActionTimestamp($uid, $action)
    {
        return $this->_registry->tasks->getActionTimestamp($uid, $action);
    }

    /**
     * Get a list of task uids that have had $action happen since $from_ts.
     *
     * @param string $action      The action to check for (add, modify, delete)
     * @param timestamp $from_ts  The timestamp to start checking from
     *
     * @return array  An array of event uids
     */
    public function tasks_listBy($action, $from_ts)
    {
        return $this->_registry->tasks->listBy($action, (int)$from_ts);
    }

    /**
     * Return all active api interfaces.
     *
     * @return array  An array of interface names.
     */
    public function horde_listApis()
    {
        return $this->_registry->horde->listAPIs();
    }

    /**
     * Obtain a user's preference setting.
     *
     * @param string $app  The Horde application providing the setting.
     * @param string $pref The name of the preference setting.
     *
     * @return mixed  The preference value
     */
    public function horde_getPref($app, $pref)
    {
        return $this->_registry->horde->getPreference($app, $pref);
    }

    /**
     * Obtain the name of the Horde application that provides the specified api
     * interface.
     *
     * @param string $api  The interface name
     *
     * @return string  The application name.
     */
    public function horde_hasInterface($api)
    {
        return $this->_registry->hasInterface($api);
    }

}