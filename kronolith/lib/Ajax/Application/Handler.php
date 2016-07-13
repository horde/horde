<?php
/**
 * Defines the AJAX actions used in Kronolith.
 *
 * Copyright 2012-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @author   Gonçalo Queirós <mail@goncaloqueiros.net>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class Kronolith_Ajax_Application_Handler extends Horde_Core_Ajax_Application_Handler
{
    protected $_external = array('embed');

    /**
     * Just polls for alarm messages and keeps session fresh for now.
     */
    public function poll()
    {
        return false;
    }

    /**
     * Returns a list of all calendars.
     */
    public function listCalendars()
    {
        Kronolith::initialize();
        $all_external_calendars = $GLOBALS['calendar_manager']->get(Kronolith::ALL_EXTERNAL_CALENDARS);
        $result = new stdClass;
        $auth_name = $GLOBALS['registry']->getAuth();

        // Calendars. Do some twisting to sort own calendar before shared
        // calendars.
        foreach (array(true, false) as $my) {
            foreach ($GLOBALS['calendar_manager']->get(Kronolith::ALL_CALENDARS) as $id => $calendar) {
                $owner = ($auth_name && ($calendar->owner() == $auth_name));
                if (($my && $owner) || (!$my && !$owner)) {
                    $result->calendars['internal'][$id] = $calendar->toHash();
                }
            }

            // Tasklists
            if (Kronolith::hasApiPermission('tasks')) {
                foreach ($GLOBALS['registry']->tasks->listTasklists($my, Horde_Perms::SHOW, false) as $id => $tasklist) {
                    if (isset($all_external_calendars['tasks/' . $id])) {
                        $owner = ($auth_name &&
                                  ($tasklist->get('owner') == $auth_name));
                        if (($my && $owner) || (!$my && !$owner)) {
                            $result->calendars['tasklists']['tasks/' . $id] =
                                $all_external_calendars['tasks/' . $id]->toHash();
                        }
                    }
                }
            }
        }

        // Resources
        if (!empty($GLOBALS['conf']['resources']['enabled'])) {
            foreach (Kronolith::getDriver('Resource')->listResources() as $resource) {
                if ($resource->get('isgroup')) {
                    $rcal = new Kronolith_Calendar_ResourceGroup(array(
                        'resource' => $resource
                    ));
                    $result->calendars['resourcegroup'][$resource->getId()] = $rcal->toHash();
                } else {
                    $rcal = new Kronolith_Calendar_Resource(array(
                        'resource' => $resource
                    ));
                    $result->calendars['resource'][$resource->get('calendar')] = $rcal->toHash();
                }
            }
        }

        // Timeobjects
        foreach ($all_external_calendars as $id => $calendar) {
            if ($calendar->api() != 'tasks' && $calendar->display()) {
                $result->calendars['external'][$id] = $calendar->toHash();
            }
        }

        // Remote calendars
        foreach ($GLOBALS['calendar_manager']->get(Kronolith::ALL_REMOTE_CALENDARS) as $url => $calendar) {
            $result->calendars['remote'][$url] = $calendar->toHash();
        }

        // Holidays
        foreach ($GLOBALS['calendar_manager']->get(Kronolith::ALL_HOLIDAYS) as $id => $calendar) {
            $result->calendars['holiday'][$id] = $calendar->toHash();
        }

        return $result;
    }

    /**
     * TODO
     */
    public function listEvents()
    {
        global $session;

        $start = new Horde_Date($this->vars->start);
        $end   = new Horde_Date($this->vars->end);
        $result = $this->_signedResponse($this->vars->cal);
        if (!($kronolith_driver = $this->_getDriver($this->vars->cal))) {
            return $result;
        }
        try {
            $session->close();
            $events = $kronolith_driver->listEvents($start, $end, array(
                'show_recurrence' => true,
                'json' => true)
            );
            $session->start();
            if (count($events)) {
                $result->events = $events;
            }
        } catch (Exception $e) {
            $session->start();
            $GLOBALS['notification']->push($e, 'horde.error');
        }
        return $result;
    }

    /**
     * Returns a JSON object representing the requested event.
     *
     * Request variables used:
     *  - cal:  The calendar id
     *  - id:   The event id
     *  - date: The date of the event we are requesting [OPTIONAL]
     *  - rsd:  The event start date of the instance of a recurring event, if
     *          requesting a specific instance.
     *  - red:  The event end date of the instance of a recurring event, if
     *          requesting a specific instance.
     */
    public function getEvent()
    {
        $result = new stdClass;

        if (!($kronolith_driver = $this->_getDriver($this->vars->cal)) ||
            !isset($this->vars->id)) {
            return $result;
        }

        try {
            $event = $kronolith_driver->getEvent($this->vars->id, $this->vars->date);
            $event->setTimezone(true);
            $result->event = $event->toJson(array(
                'full' => true,
                'time_format' => $GLOBALS['prefs']->getValue('twentyFour') ? 'H:i' : 'h:i A',
                'history' => true)
            );
            // If recurring, we need to format the dates of this instance, since
            // Kronolith_Driver#getEvent will return the start/end dates of the
            // original event in the series.
            if ($event->recurs() && $this->vars->rsd) {
                $rs = new Horde_Date($this->vars->rsd);
                $result->event->rsd = $rs->strftime('%x');
                $re = new Horde_Date($this->vars->red);
                $result->event->red = $re->strftime('%x');
            }
        } catch (Horde_Exception_NotFound $e) {
            $GLOBALS['notification']->push(_("The requested event was not found."), 'horde.error');
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
        }

        return $result;
    }

    /**
     * Save a new or update an existing event from the AJAX event detail view.
     *
     * Request parameters used:
     * - event:          The event id.
     * - cal:            The calendar id.
     * - targetcalendar: If moving events, the targetcalendar to move to.
     * - as_new:         Save an existing event as a new event.
     * - recur_edit:     If editing an instance of a recurring event series,
     *                   how to apply the edit [current|future|all].
     * - rstart:         If editing an instance of a recurring event series,
     *                   the original start datetime of this instance.
     * - rend:           If editing an instance of a recurring event series,
     *                   the original ending datetime of this instance.
     * - sendupdates:    Should updates be sent to attendees?
     * - cstart:         Start time of the client cache.
     * - cend:           End time of the client cache.
     */
    public function saveEvent()
    {
        global $injector, $notification, $registry;

        $result = $this->_signedResponse($this->vars->targetcalendar);

        if (!($kronolith_driver = $this->_getDriver($this->vars->targetcalendar))) {
            return $result;
        }

        if ($this->vars->as_new) {
            unset($this->vars->event);
        }
        if (!$this->vars->event) {
            $perms = $injector->getInstance('Horde_Core_Perms');
            if ($perms->hasAppPermission('max_events') !== true &&
                $perms->hasAppPermission('max_events') <= Kronolith::countEvents()) {
                Horde::permissionDeniedError(
                    'kronolith',
                    'max_events',
                    sprintf(
                        _("You are not allowed to create more than %d events."),
                        $perms->hasAppPermission('max_events')
                    )
                );
                return $result;
            }
        }

        if ($this->vars->event &&
            $this->vars->cal &&
            $this->vars->cal != $this->vars->targetcalendar) {
            if (strpos($kronolith_driver->calendar, '\\')) {
                list($target, $user) = explode(
                    '\\', $kronolith_driver->calendar, 2
                );
            } else {
                $target = $kronolith_driver->calendar;
                $user = $registry->getAuth();
            }
            $kronolith_driver = $this->_getDriver($this->vars->cal);
            // Only delete the event from the source calendar if this user has
            // permissions to do so.
            try {
                $sourceShare = Kronolith::getInternalCalendar(
                    $kronolith_driver->calendar
                );
                $share = Kronolith::getInternalCalendar($target);
                if ($sourceShare->hasPermission($registry->getAuth(), Horde_Perms::DELETE) &&
                    (($user == $registry->getAuth() &&
                      $share->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) ||
                     ($user != $registry->getAuth() &&
                      $share->hasPermission($registry->getAuth(), Kronolith::PERMS_DELEGATE)))) {
                    $kronolith_driver->move($this->vars->event, $target);
                    $kronolith_driver = $this->_getDriver($this->vars->targetcalendar);
                }
            } catch (Exception $e) {
                $notification->push(
                    sprintf(
                        _("There was an error moving the event: %s"),
                        $e->getMessage()
                    ),
                    'horde.error'
                );
                return $result;
            }
        }
        if ($this->vars->as_new) {
            $event = $kronolith_driver->getEvent();
        } else {
            try {
                // Note that when this is a new event, $this->vars->event will
                // be empty, so this will create a new event.
                $event = $kronolith_driver->getEvent($this->vars->event);
            } catch (Horde_Exception_NotFound $e) {
                $notification->push(
                    _("The requested event was not found."),
                    'horde.error'
                );
                return $result;
            } catch (Exception $e) {
                $notification->push($e);
                return $result;
            }
        }

        if (!$event->hasPermission(Horde_Perms::EDIT)) {
            $notification->push(
                _("You do not have permission to edit this event."),
                'horde.warning'
            );
            return $result;
        }

        $removed_attendees = new Kronolith_Attendee_List();
        $old_attendees = new Kronolith_Attendee_List();
        if ($this->vars->recur_edit && $this->vars->recur_edit != 'all') {
            switch ($this->vars->recur_edit) {
            case 'current':
                $attributes = new stdClass();
                $attributes->rstart = $this->vars->rstart;
                $attributes->rend = $this->vars->rend;
                $this->_addException($event, $attributes);

                // Create a copy of the original event so we can read in the
                // new form values for the exception. We also MUST reset the
                // recurrence property even though we won't be using it, since
                // clone() does not do a deep copy. Otherwise, the original
                // event's recurrence will become corrupt.
                $newEvent = clone($event);
                $newEvent->recurrence = new Horde_Date_Recurrence($event->start);
                $newEvent->readForm($event);

                // Create an exception event from the new properties.
                $exception = $this->_copyEvent($event, $newEvent, $attributes);
                $exception->start = $newEvent->start;
                $exception->end = $newEvent->end;

                // Save the new exception.
                $attributes->cstart = $this->vars->cstart;
                $attributes->cend = $this->vars->cend;
                $result = $this->_saveEvent(
                    $exception,
                    $event,
                    $attributes);
                break;
            case 'future':
                $instance = new Horde_Date($this->vars->rstart, $event->timezone);
                $exception = clone($instance);
                $exception->mday--;
                if ($event->end->compareDate($exception) > 0) {
                    // Same as 'all' since this is the first recurrence.
                    $this->vars->recur_edit = 'all';
                    return $this->saveEvent();
                } else {
                    $event->recurrence->setRecurEnd($exception);
                    $newEvent = $kronolith_driver->getEvent();
                    $newEvent->readForm();
                    $newEvent->uid = null;
                    $result = $this->_saveEvent(
                        $newEvent, $event, $this->vars, true
                    );
                }

            }
        } else {
            $old_start = !empty($event->start) ? clone($event->start) : false;
            $old_end = !empty($event->end) ? clone($event->end) : false;
            $old_recurrence = !empty($event->recurrence) ? clone($event->recurrence) : false;
            try {
                $old_attendees = $event->attendees;
                $event->readForm();
                foreach ($old_attendees as $old_attendee) {
                    if (!$event->attendees->has($old_attendee)) {
                        $removed_attendees->add($old_attendee);
                    }
                }
                if ((!empty($old_start) && !empty($old_end) &&
                    $event->recurs() &&
                    ($old_start->compareTime($event->start) !== 0 ||
                     $old_end->compareTime($event->end) !== 0)) ||
                      ($old_recurrence && !$event->recurrence->isEqual($old_recurrence))) {
                    // Disconnect any existing exceptions when the
                    // start/end time changes still @todo this when the
                    // recurrence series type/properties change too.
                    $event->disconnectExceptions();
                }
                $result = $this->_saveEvent($event);
            } catch (Exception $e) {
                $notification->push($e);
                return $result;
            }
        }

        if ($this->vars->sendupdates) {
            if ($this->vars->attendance) {
                Kronolith::sendITipNotifications($event, $notification, Kronolith::ITIP_REPLY);
            }

            // Only the ORGANIZER's copy should trigger a REQUEST or CANCEL.
            if (empty($event->organizer)) {
                $type = $event->status == Kronolith::STATUS_CANCELLED
                    ? Kronolith::ITIP_CANCEL
                    : Kronolith::ITIP_REQUEST;
                Kronolith::sendITipNotifications($event, $notification, $type);
            }
        }

        // Send a CANCEL iTip for attendees that have been removed, but only if
        // the entire event isn't being marked as cancelled (which would be
        // caught above).
        if (empty($event->organizer) && count($removed_attendees)) {
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
        Kronolith::notifyOfResourceRejection($event);

        return $result;
    }

    /**
     * TODO
     */
    public function quickSaveEvent()
    {
        $cal = explode('|', $this->vars->cal, 2);
        try {
            $event = Kronolith::quickAdd($this->vars->text, $cal[1]);
            return $this->_saveEvent($event);
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
            $result = $this->_signedResponse($this->vars->cal);
            $result->error = true;
            return $result;
        }
    }

    /**
     * Update event details as a result of a drag/drop operation (which would
     * only affect the event's start/end times).
     *
     * Uses the following request variables:
     *<pre>
     *   -cal:  The calendar id.
     *   -id:   The event id.
     *   -att:  Attribute hash of changed values. Can contain:
     *      -start:     A new start datetime for the event.
     *      -end:       A new end datetime for the event.
     *      -offDays:   An offset of days to apply to the event.
     *      -offMins:   An offset of minutes to apply to the event.
     *      -rstart:    The orginal start datetime of a series instance.
     *      -rend:      The original end datetime of a series instance.
     *      -rday:      A new start value for a series instance (used when
     *                  dragging on the month view where only the date can
     *                  change, and not the start/end times).
     *      -u:         Send update to attendees.
     *</pre>
     */
    public function updateEvent()
    {
        $result = $this->_signedResponse($this->vars->cal);

        if (!($kronolith_driver = $this->_getDriver($this->vars->cal)) ||
            !isset($this->vars->id)) {
            return $result;
        }

        try {
            $oevent = $kronolith_driver->getEvent($this->vars->id);
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
            return $result;
        }
        if (!$oevent) {
            $GLOBALS['notification']->push(_("The requested event was not found."), 'horde.error');
            return $result;
        } elseif (!$oevent->hasPermission(Horde_Perms::EDIT)) {
            $GLOBALS['notification']->push(_("You do not have permission to edit this event."), 'horde.warning');
            return $result;
        }

        $attributes = Horde_Serialize::unserialize($this->vars->att, Horde_Serialize::JSON);

        // If this is a recurring event, need to create an exception.
        if ($oevent->recurs()) {
            $this->_addException($oevent, $attributes);
            $event = $this->_copyEvent($oevent, null, $attributes);
        } else {
            $event = clone($oevent);
        }

        foreach ($attributes as $attribute => $value) {
            switch ($attribute) {
            case 'start':
                $newDate = new Horde_Date($value);
                $newDate->setTimezone($event->start->timezone);
                $event->start = clone($newDate);
                break;

            case 'end':
                $newDate = new Horde_Date($value);
                $newDate->setTimezone($event->end->timezone);
                $event->end = clone($newDate);
                if ($event->end->hour == 23 &&
                    $event->end->min == 59 &&
                    $event->end->sec == 59) {
                    $event->end->mday++;
                    $event->end->hour = $event->end->min = $event->end->sec = 0;
                }
                break;

            case 'offDays':
                $event->start->mday += $value;
                $event->end->mday += $value;
                break;

            case 'offMins':
                $event->start->min += $value;
                $event->end->min += $value;
                break;
            }
        }

        $result = $this->_saveEvent($event, ($oevent->recurs() ? $oevent : null), $attributes);
        if ($this->vars->u) {
            Kronolith::sendITipNotifications($event, $GLOBALS['notification'], Kronolith::ITIP_REQUEST);
        }
        Kronolith::notifyOfResourceRejection($event);

        return $result;
    }

    /**
     * Deletes an event, or an instance of an event series from the backend.
     *
     * Uses the following request variables:
     *<pre>
     *   - cal:          The calendar id.
     *   - id:           The event id.
     *   - r:            If this is an event series, what type of deletion to
     *                   perform [future | current | all].
     *   - rstart:       The start time of the event instance being removed, if
     *                   this is a series instance.
     *   - cstart:       The start date of the client event cache.
     *   - cend:         The end date of the client event cache.
     *   - sendupdates:  Send cancellation notice to attendees?
     * </pre>
     */
    public function deleteEvent()
    {
        $result = new stdClass;
        $instance = null;

        if (!($kronolith_driver = $this->_getDriver($this->vars->cal)) ||
            !isset($this->vars->id)) {
            return $result;
        }

        try {
            $event = $kronolith_driver->getEvent($this->vars->id);
            if (!$event->hasPermission(Horde_Perms::DELETE)) {
                $GLOBALS['notification']->push(_("You do not have permission to delete this event."), 'horde.warning');
                return $result;
            }
            $range = null;
            if ($event->recurs() && $this->vars->r != 'all') {
                switch ($this->vars->r) {
                case 'future':
                    // Deleting all future instances.
                    // @TODO: Check if we need to find future exceptions
                    //        that are after $recurEnd and remove those as well.
                    $instance = new Horde_Date($this->vars->rstart, $event->timezone);
                    $recurEnd = clone($instance);
                    $recurEnd->hour = 0;
                    $recurEnd->min = 0;
                    $recurEnd->sec = 0;
                    $recurEnd->mday--;
                    if ($event->end->compareDate($recurEnd) > 0) {
                        $kronolith_driver->deleteEvent($event->id);
                        $result = $this->_signedResponse($this->vars->cal);
                        $result->events = array();
                    } else {
                        $event->recurrence->setRecurEnd($recurEnd);
                        $result = $this->_saveEvent($event, $event, $this->vars);
                    }
                    $range = Kronolith::RANGE_THISANDFUTURE;
                    break;
                case 'current':
                    // Deleting only the current instance.
                    $instance = new Horde_Date($this->vars->rstart, $event->timezone);
                    $event->recurrence->addException(
                        $instance->year, $instance->month, $instance->mday);
                    $result = $this->_saveEvent($event, $event, $this->vars);
                }
            } else {
                // Deleting an entire series, or this is a single event only.
                $kronolith_driver->deleteEvent($event->id);
                $result = $this->_signedResponse($this->vars->cal);
                $result->events = array();
                $result->uid = $event->uid;
            }

            if ($this->vars->sendupdates) {
                Kronolith::sendITipNotifications(
                    $event, $GLOBALS['notification'], Kronolith::ITIP_CANCEL, $instance, $range);
            }
            $result->deleted = true;
        } catch (Horde_Exception_NotFound $e) {
            $GLOBALS['notification']->push(_("The requested event was not found."), 'horde.error');
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
        }

        return $result;
    }

    /**
     * TODO
     */
    public function searchEvents()
    {
        $query = Horde_Serialize::unserialize($this->vars->query, Horde_Serialize::JSON);
        if (!isset($query->start)) {
            $query->start = new Horde_Date($_SERVER['REQUEST_TIME']);
        }
        if (!isset($query->end)) {
            $query->end = null;
        }
        switch ($this->vars->time) {
        case 'all':
            $query->start = null;
            $query->end = null;
            break;
        case 'future':
            $query->start = new Horde_Date($_SERVER['REQUEST_TIME']);
            $query->end = null;
            break;
        case 'past':
            $query->start = null;
            $query->end = new Horde_Date($_SERVER['REQUEST_TIME']);
            break;
        }

        $tagger = new Kronolith_Tagger();
        $cals = Horde_Serialize::unserialize($this->vars->cals, Horde_Serialize::JSON);
        $events = array();
        foreach ($cals as $cal) {
            if (!($kronolith_driver = $this->_getDriver($cal))) {
                continue;
            }
            try {
                $result = $kronolith_driver->search($query, true);
                if ($result) {
                    $events[$cal] = $result;
                }
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
            }
            $split = explode('|', $cal);
            if ($split[0] == 'internal') {
                $result = $tagger->search($query->title, array('type' => 'event', 'calendar' => $split[1]));
                foreach ($result['events'] as $uid) {
                    Kronolith::addSearchEvents($events[$cal], $kronolith_driver->getByUID($uid), $query, true);
                }
            }
        }

        $result = new stdClass;
        $result->view = 'search';
        $result->query = $this->vars->query;
        if ($events) {
            $result->events = $events;
        }

        return $result;
    }

    /**
     * TODO
     */
    public function listTasks()
    {
        if (!$GLOBALS['registry']->hasMethod('tasks/listTasks')) {
            return false;
        }

        $result = new stdClass;
        $result->list = $this->vars->list;
        $result->type = $this->vars->type;
        try {
            $tasks = $GLOBALS['registry']->tasks
                ->listTasks(array(
                    'tasklists' => $this->vars->list,
                    'completed' => $this->vars->type == 'incomplete' ? 'future_incomplete' : $this->vars->type,
                    'include_tags' => true,
                    'external' => false,
                    'json' => true
                ));
            if (count($tasks)) {
                $result->tasks = $tasks;
            }
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
        }

        return $result;
    }

    /**
     * TODO
     */
    public function getTask()
    {
        if (!$GLOBALS['registry']->hasMethod('tasks/getTask') ||
            !isset($this->vars->id) ||
            !isset($this->vars->list)) {
            return false;
        }

        $result = new stdClass;
        try {
            $task = $GLOBALS['registry']->tasks->getTask($this->vars->list, $this->vars->id);
            if ($task) {
                $result->task = $task->toJson(true, $GLOBALS['prefs']->getValue('twentyFour') ? 'H:i' : 'h:i A');
            } else {
                $GLOBALS['notification']->push(_("The requested task was not found."), 'horde.error');
            }
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
        }

        return $result;
    }

    /**
     * TODO
     */
    public function saveTask()
    {
        if (!$GLOBALS['registry']->hasMethod('tasks/updateTask') ||
            !$GLOBALS['registry']->hasMethod('tasks/addTask')) {
            return false;
        }

        $id = $this->vars->task_id;
        $list = $this->vars->old_tasklist;
        $task = $this->vars->task;
        $result = $this->_signedResponse('tasklists|tasks/' . $task['tasklist']);

        $due = trim($task['due_date'] . ' ' . $task['due_time']);
        if (!empty($due)) {
            try {
                $due = Kronolith::parseDate($due);
                $task['due'] = $due->timestamp();
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                return $result;
            }
        }

        if ($task['alarm']['on']) {
            $value = $task['alarm']['value'];
            $unit = $task['alarm']['unit'];
            if ($value == 0) {
                $value = $unit = 1;
            }
            $task['alarm'] = $value * $unit;
            if (isset($task['alarm_methods']) && isset($task['methods'])) {
                foreach (array_keys($task['methods']) as $method) {
                    if (!in_array($method, $task['alarm_methods'])) {
                        unset($task['methods'][$method]);
                    }
                }
                foreach ($task['alarm_methods'] as $method) {
                    if (!isset($task['methods'][$method])) {
                        $task['methods'][$method] = array();
                    }
                }
            } else {
                $task['methods'] = array();
            }
        } else {
            $task['alarm'] = 0;
            $task['methods'] = array();
        }
        unset($task['alarm_methods']);

        if (!isset($task['completed'])) {
            $task['completed'] = false;
        }

        if ($this->vars->recur && !empty($due)) {
            $task['recurrence'] = Kronolith_Event::readRecurrenceForm($due, 'UTC');
        }

        $task['tags'] = Horde_Util::getFormData('tags');

        try {
            $ids = ($id && $list)
                ? $GLOBALS['registry']->tasks->updateTask($list, $id, $task)
                : $GLOBALS['registry']->tasks->addTask($task);
            if (!$id) {
                $id = $ids[0];
            }
            $task = $GLOBALS['registry']->tasks->getTask($task['tasklist'], $id);
            $result->tasks = array($id => $task->toJson(false, $GLOBALS['prefs']->getValue('twentyFour') ? 'H:i' : 'h:i A'));
            $result->type = $task->completed ? 'complete' : 'incomplete';
            $result->list = $task->tasklist;
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
            return $result;
        }

        if ($due &&
            $kronolith_driver = $this->_getDriver('tasklists|tasks/' . $task->tasklist)) {
            try {
                $event = $kronolith_driver->getEvent('_tasks' . $id);
                $end = clone $due;
                $end->hour = 23;
                $end->min = $end->sec = 59;
                $start = clone $due;
                $start->hour = $start->min = $start->sec = 0;
                $events = array();
                Kronolith::addEvents($events, $event, $start, $end, true, true);
                if (count($events)) {
                    $result->events = $events;
                }
            } catch (Horde_Exception_NotFound $e) {
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
            }
        }

        return $result;
    }

    /**
     * TODO
     */
    public function quickSaveTask()
    {
        if (!$GLOBALS['registry']->hasMethod('tasks/quickAdd')) {
            return false;
        }

        $result = $this->_signedResponse(
            'tasklists|tasks/' . $this->vars->tasklist);

        try {
            $ids = $GLOBALS['registry']->tasks->quickAdd($this->vars->text);
            $result->type = 'incomplete';
            $result->list = $this->vars->tasklist;
            $result->tasks = array();
            foreach ($ids as $uid) {
                $task = $GLOBALS['registry']->tasks->export($uid, 'raw');
                $result->tasks[$task->id] = $task->toJson(
                    false,
                    $GLOBALS['prefs']->getValue('twentyFour') ? 'H:i' : 'h:i A'
                );
            }
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
        }

        return $result;
    }

    /**
     * TODO
     */
    public function deleteTask()
    {
        $result = new stdClass;

        if (!$GLOBALS['registry']->hasMethod('tasks/deleteTask') ||
            !isset($this->vars->id) ||
            !isset($this->vars->list)) {
            return $result;
        }

        try {
            $GLOBALS['registry']->tasks->deleteTask($this->vars->list, $this->vars->id);
            $result->deleted = true;
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
        }

        return $result;
    }

    /**
     * TODO
     */
    public function toggleCompletion()
    {
        $result = new stdClass;

        if (!$GLOBALS['registry']->hasMethod('tasks/toggleCompletion')) {
            return $result;
        }

        try {
            $result->toggled = $GLOBALS['registry']->tasks->toggleCompletion($this->vars->id, $this->vars->list);
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
        }

        return $result;
    }

    /**
     * Generate a list of most frequently used tags for the current user.
     */
    public function listTopTags()
    {
        $tagger = new Kronolith_Tagger();
        $result = new stdClass;
        $result->tags = array();
        $tags = $tagger->getCloud($GLOBALS['registry']->getAuth(), 10, true);
        foreach ($tags as $tag) {
            $result->tags[] = $tag['tag_name'];
        }
        return $result;
    }

    /**
     * Return fb information for the requested attendee or resource.
     *
     * Uses the following request parameters:
     *  - user:     The attendee's user name.
     *  - email:    The attendee's email address.
     *  - resource: The resource id.
     */
    public function getFreeBusy()
    {
        global $notification;

        $result = new stdClass;
        if ($this->vars->user) {
            try {
                $result->fb = Kronolith_FreeBusy::getForUser(
                    $this->vars->user,
                    array(
                        'json'  => true,
                        'start' => $this->vars->start,
                        'end'   => $this->vars->end
                    )
                );
            } catch (Exception $e) {
                $notification->push($e->getMessage(), 'horde.warning');
            }
        } elseif ($this->vars->email) {
            $rfc822 = new Horde_Mail_Rfc822();
            $res = $rfc822->parseAddressList($this->vars->email);
            if ($res[0] && $res[0]->host) {
                try {
                    $result->fb = Kronolith_FreeBusy::get($this->vars->email, true);
                } catch (Exception $e) {
                    $notification->push($e->getMessage(), 'horde.warning');
                }
            }
        } elseif ($this->vars->resource) {
            try {
                $resource = Kronolith::getDriver('Resource')
                    ->getResource($this->vars->resource);
                try {
                    $result->fb = $resource->getFreeBusy(null, null, true, true);
                } catch (Horde_Exception $e) {
                    // Resource groups can't provide FB information.
                    $result->fb = null;
                }
            } catch (Exception $e) {
                $notification->push($e->getMessage(), 'horde.warning');
            }
        }

        return $result;
    }

    /**
     * TODO
     */
    public function searchCalendars()
    {
        $result = new stdClass;
        $result->events = 'Searched for calendars: ' . $this->vars->title;
        return $result;
    }

    /**
     * TODO
     */
    public function saveCalendar()
    {
        global $calendar_manager, $injector, $notification, $prefs, $registry,
            $session;

        $calendar_id = $this->vars->calendar;
        $result = new stdClass;

        switch ($this->vars->type) {
        case 'internal':
            $info = array();
            foreach (array('name', 'color', 'description', 'tags', 'system') as $key) {
                $info[$key] = $this->vars->$key;
            }

            // Create a calendar.
            if (!$calendar_id) {
                if (!$registry->getAuth() ||
                    $prefs->isLocked('default_share')) {
                    return $result;
                }
                try {
                    $calendar = Kronolith::addShare($info);
                    Kronolith::readPermsForm($calendar);
                    if ($calendar->hasPermission($registry->getAuth(), Horde_Perms::SHOW)) {
                        $wrapper = new Kronolith_Calendar_Internal(array('share' => $calendar));
                        $result->saved = true;
                        $result->id = $calendar->getName();
                        $result->calendar = $wrapper->toHash();
                    }
                } catch (Exception $e) {
                    $notification->push($e, 'horde.error');
                    return $result;
                }
                $notification->push(sprintf(_("The calendar \"%s\" has been created."), $info['name']), 'horde.success');
                break;
            }

            // Update a calendar.
            try {
                $calendar = $injector->getInstance('Kronolith_Shares')->getShare($calendar_id);
                $original_name = $calendar->get('name');
                $original_owner = $calendar->get('owner');
                Kronolith::updateShare($calendar, $info);
                Kronolith::readPermsForm($calendar);
                if ((!$info['system'] &&
                     $calendar->get('owner') != $original_owner) ||
                    ($info['system'] && !is_null($original_owner))) {
                    $result->deleted = true;
                }
                if ($calendar->hasPermission($registry->getAuth(), Horde_Perms::SHOW) ||
                    (is_null($calendar->get('owner')) && $registry->isAdmin())) {
                    $wrapper = new Kronolith_Calendar_Internal(array('share' => $calendar));
                    $result->saved = true;
                    $result->id = $calendar->getName();
                    $result->calendar = $wrapper->toHash();
                }
            } catch (Exception $e) {
                $notification->push($e, 'horde.error');
                return $result;

            }
            if ($calendar->get('name') != $original_name) {
                $notification->push(sprintf(_("The calendar \"%s\" has been renamed to \"%s\"."), $original_name, $calendar->get('name')), 'horde.success');
            } else {
                $notification->push(sprintf(_("The calendar \"%s\" has been saved."), $original_name), 'horde.success');
            }
            break;

        case 'tasklists':
            $calendar = array();
            foreach (array('name', 'color', 'description') as $key) {
                $calendar[$key] = $this->vars->$key;
            }

            // Create a task list.
            if (!$calendar_id) {
                if (!$registry->getAuth() ||
                    $prefs->isLocked('default_share')) {
                    return $result;
                }
                try {
                    $tasklistId = $registry->tasks->addTasklist($calendar['name'], $calendar['description'], $calendar['color']);
                    $tasklists = $registry->tasks->listTasklists(true);
                    if (!isset($tasklists[$tasklistId])) {
                        $notification->push(_("Added task list not found."), 'horde.error');
                        return $result;
                    }
                    $tasklist = $tasklists[$tasklistId];
                    Kronolith::readPermsForm($tasklist);
                    if ($tasklist->hasPermission($registry->getAuth(), Horde_Perms::SHOW)) {
                        $wrapper = new Kronolith_Calendar_External_Tasks(array('api' => 'tasks', 'name' => $tasklistId, 'share' => $tasklist));

                        // Update external calendars caches.
                        $all_external = $session->get('kronolith', 'all_external_calendars');
                        $all_external[] = array('a' => 'tasks', 'n' => $tasklistId, 'd' => $tasklist->get('name'));
                        $session->set('kronolith', 'all_external_calendars', $all_external);
                        $display_external = $calendar_manager->get(Kronolith::DISPLAY_EXTERNAL_CALENDARS);
                        $display_external[] = 'tasks/' . $tasklistId;
                        $calendar_manager->set(Kronolith::DISPLAY_EXTERNAL_CALENDARS, $display_external);
                        $prefs->setValue('display_external_cals', serialize($display_external));
                        $all_external = $calendar_manager->get(Kronolith::ALL_EXTERNAL_CALENDARS);
                        $all_external['tasks/' . $tasklistId] = $wrapper;
                        $calendar_manager->set(Kronolith::ALL_EXTERNAL_CALENDARS, $all_external);

                        $result->saved = true;
                        $result->id = 'tasks/' . $tasklistId;
                        $result->calendar = $wrapper->toHash();
                    }
                } catch (Exception $e) {
                    $notification->push($e, 'horde.error');
                    return $result;
                }
                $notification->push(sprintf(_("The task list \"%s\" has been created."), $calendar['name']), 'horde.success');
                break;
            }

            // Update a task list.
            $calendar_id = substr($calendar_id, 6);
            try {
                $registry->tasks->updateTasklist($calendar_id, $calendar);
                $tasklists = $registry->tasks->listTasklists(true, Horde_Perms::EDIT);
                $tasklist = $tasklists[$calendar_id];
                $original_owner = $tasklist->get('owner');
                Kronolith::readPermsForm($tasklist);
                if ($tasklist->get('owner') != $original_owner) {
                    $result->deleted = true;
                }
                if ($tasklist->hasPermission($registry->getAuth(), Horde_Perms::SHOW)) {
                    $wrapper = new Kronolith_Calendar_External_Tasks(array('api' => 'tasks', 'name' => $calendar_id, 'share' => $tasklist));
                    $result->saved = true;
                    $result->calendar = $wrapper->toHash();
                }
            } catch (Exception $e) {
                $notification->push($e, 'horde.error');
                return $result;
            }
            if ($tasklist->get('name') != $calendar['name']) {
                $notification->push(sprintf(_("The task list \"%s\" has been renamed to \"%s\"."), $tasklist->get('name'), $calendar['name']), 'horde.success');
            } else {
                $notification->push(sprintf(_("The task list \"%s\" has been saved."), $tasklist->get('name')), 'horde.success');
            }
            break;

        case 'remote':
            $calendar = array();
            foreach (array('name', 'desc', 'url', 'color', 'user', 'password') as $key) {
                $calendar[$key] = $this->vars->$key;
            }
            try {
                Kronolith::subscribeRemoteCalendar($calendar, $calendar_id);
            } catch (Exception $e) {
                $notification->push($e, 'horde.error');
                return $result;
            }
            if ($calendar_id) {
                $notification->push(sprintf(_("The calendar \"%s\" has been saved."), $calendar['name']), 'horde.success');
            } else {
                $notification->push(sprintf(_("You have been subscribed to \"%s\" (%s)."), $calendar['name'], $calendar['url']), 'horde.success');
                $result->id = $calendar['url'];
            }
            $wrapper = new Kronolith_Calendar_Remote($calendar);
            $result->saved = true;
            $result->calendar = $wrapper->toHash();
            break;

        case 'resource':
            foreach (array('name', 'desc', 'response_type') as $key) {
                $info[$key] = $this->vars->$key;
            }

            if (!$calendar_id) {
                // New resource
                if (!$registry->isAdmin() &&
                    !$injector->getInstance('Horde_Core_Perms')->hasAppPermission('resource_management')) {
                    $notification->push(_("You are not allowed to create new resources."), 'horde.error');
                    return $result;
                }
                $resource = Kronolith_Resource::addResource($info);
                Kronolith::readPermsForm($resource);
                $resource->save();
            } else {
                try {
                    $rdriver = Kronolith::getDriver('Resource');
                    $resource = $rdriver->getResource($rdriver->getResourceIdByCalendar($calendar_id));
                    if (!($resource->hasPermission($registry->getAuth(), Horde_Perms::EDIT))) {
                        $notification->push(_("You are not allowed to edit this resource."), 'horde.error');
                        return $result;
                    }
                    foreach (array('name', 'desc', 'response_type', 'email') as $key) {
                        $resource->set($key, $this->vars->$key);
                    }
                    Kronolith::readPermsForm($resource);
                    $resource->save();
                } catch (Kronolith_Exception $e) {
                    $notification->push($e->getMessage(), 'horde.error');
                    return $result;
                }
            }
            $wrapper = new Kronolith_Calendar_Resource(array('resource' => $resource));
            $result->calendar = $wrapper->toHash();
            $result->saved = true;
            $result->id = $resource->get('calendar');
            $notification->push(sprintf(_("The resource \"%s\" has been saved."), $resource->get('name'), 'horde.success'));
            break;

        case 'resourcegroup':
            $info = array('group' => true);
            foreach (array('name', 'desc', 'members') as $key) {
                $info[$key] = $this->vars->$key;
            }

            if (empty($calendar_id)) {
                // New resource group.
                $resource = Kronolith_Resource::addResource($info);
            } else {
                $driver = Kronolith::getDriver('Resource');
                $resource = $driver->getResource($calendar_id);
                $resource->set('name', $this->vars->name);
                $resource->set('desc', $this->vars->description);
                $resource->set('members', $this->vars->members);
                $resource->save();
            }

            $wrapper = new Kronolith_Calendar_ResourceGroup(array('resource' => $resource));
            $result->calendar = $wrapper->toHash();
            $result->saved = true;
            $result->id = $resource->get('calendar');
            $notification->push(sprintf(_("The resource group \"%s\" has been saved."), $resource->get('name'), 'horde.success'));
            break;
        }

        return $result;
    }

    /**
     * TODO
     */
    public function deleteCalendar()
    {
        $calendar_id = $this->vars->calendar;
        $result = new stdClass;

        switch ($this->vars->type) {
        case 'internal':
            try {
                $calendar = $GLOBALS['injector']->getInstance('Kronolith_Shares')->getShare($calendar_id);
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                return $result;
            }
            try {
                Kronolith::deleteShare($calendar);
            } catch (Exception $e) {
                $GLOBALS['notification']->push(sprintf(_("Unable to delete \"%s\": %s"), $calendar->get('name'), $e->getMessage()), 'horde.error');
                return $result;
            }
            $GLOBALS['notification']->push(sprintf(_("The calendar \"%s\" has been deleted."), $calendar->get('name')), 'horde.success');
            break;

        case 'tasklists':
            $calendar_id = substr($calendar_id, 6);
            $tasklists = $GLOBALS['registry']->tasks->listTasklists(true);
            if (!isset($tasklists[$calendar_id])) {
                $GLOBALS['notification']->push(_("You are not allowed to delete this task list."), 'horde.error');
                return $result;
            }
            try {
                $GLOBALS['registry']->tasks->deleteTasklist($calendar_id);
            } catch (Exception $e) {
                $GLOBALS['notification']->push(sprintf(_("Unable to delete \"%s\": %s"), $tasklists[$calendar_id]->get('name'), $e->getMessage()), 'horde.error');
                return $result;
            }
            $GLOBALS['notification']->push(sprintf(_("The task list \"%s\" has been deleted."), $tasklists[$calendar_id]->get('name')), 'horde.success');
            break;

        case 'remote':
            try {
                $deleted = Kronolith::unsubscribeRemoteCalendar($calendar_id);
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                return $result;
            }
            $GLOBALS['notification']->push(sprintf(_("You have been unsubscribed from \"%s\" (%s)."), $deleted['name'], $deleted['url']), 'horde.success');
            break;

        case 'resource':
            try {
                $rdriver = Kronolith::getDriver('Resource');
                $resource = $rdriver->getResource($rdriver->getResourceIdByCalendar($calendar_id));
                if (!($resource->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE))) {
                    $GLOBALS['notification']->push(_("You are not allowed to delete this resource."), 'horde.error');
                    return $result;
                }
                $name = $resource->get('name');
                $rdriver->delete($resource);
            } catch (Kronolith_Exception $e) {
                $GLOBALS['notification']->push($e->getMessage(), 'horde.error');
                return $result;
            }

            $GLOBALS['notification']->push(sprintf(_("The resource \"%s\" has been deleted."), $name), 'horde.success');
            break;

        case 'resourcegroup':
            try {
                $rdriver = Kronolith::getDriver('Resource');
                $resource = $rdriver->getResource($calendar_id);
                if (!($resource->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE))) {
                    $GLOBALS['notification']->push(_("You are not allowed to delete this resource."), 'horde.error');
                    return $result;
                }
                $name = $resource->get('name');
                $rdriver->delete($resource);
            } catch (Kronolith_Exception $e) {
                $GLOBALS['notification']->push($e->getMessage(), 'horde.error');
                return $result;
            }
            $GLOBALS['notification']->push(sprintf(_("The resource \"%s\" has been deleted."), $name), 'horde.success');

        }
        $result->deleted = true;

        return $result;
    }

    /**
     * Returns the information for a shared internal calendar.
     */
    public function getCalendar()
    {
        $result = new stdClass;
        $all_calendars = $GLOBALS['calendar_manager']->get(Kronolith::ALL_CALENDARS);
        if (!isset($all_calendars[$this->vars->cal]) && !$GLOBALS['conf']['share']['hidden']) {
                $GLOBALS['notification']->push(_("You are not allowed to view this calendar."), 'horde.error');
                return $result;
        } elseif (!isset($all_calendars[$this->vars->cal])) {
            // Subscribing to a "hidden" share, check perms.
            $kronolith_shares = $GLOBALS['injector']->getInstance('Kronolith_Shares');
            $share = $kronolith_shares->getShare($this->vars->cal);
            if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
                $GLOBALS['notification']->push(_("You are not allowed to view this calendar."), 'horde.error');
                return $result;
            }
            $calendar = new Kronolith_Calendar_Internal(array('share' => $share));
        } else {
            $calendar = $all_calendars[$this->vars->cal];
        }

        $result->calendar = $calendar->toHash();
        return $result;
    }

    /**
     * TODO
     */
    public function getRemoteInfo()
    {
        $params = array('timeout' => 15);
        if ($user = $this->vars->user) {
            $params['user'] = $user;
            $params['password'] = $this->vars->password;
        }
        if (!empty($GLOBALS['conf']['http']['proxy']['proxy_host'])) {
            $params['proxy'] = $GLOBALS['conf']['http']['proxy'];
        }

        $result = new stdClass;
        try {
            $driver = $GLOBALS['injector']->getInstance('Kronolith_Factory_Driver')->create('Ical', $params);
            $driver->open($this->vars->url);
            if ($driver->isCalDAV()) {
                $result->success = true;
                // TODO: find out how to retrieve calendar information via CalDAV.
            } else {
                $ical = $driver->getRemoteCalendar(false);
                $result->success = true;
                try {
                    $name = $ical->getAttribute('X-WR-CALNAME');
                    $result->name = $name;
                } catch (Horde_Icalendar_Exception $e) {}
                try {
                    $desc = $ical->getAttribute('X-WR-CALDESC');
                    $result->desc = $desc;
                } catch (Horde_Icalendar_Exception $e) {}
            }
        } catch (Exception $e) {
            if ($e->getCode() == 401) {
                $result->auth = true;
            } else {
                $GLOBALS['notification']->push($e, 'horde.error');
            }
        }

        return $result;
    }

    /**
     * TODO
     */
    public function saveCalPref()
    {
        return false;
    }

    /**
     * Return a list of available resources.
     *
     * @return array  A hash of resource_id => resource sorted by resource name.
     */
    public function getResourceList()
    {
        $data = array();
        $resources = Kronolith::getDriver('Resource')
            ->listResources(Horde_Perms::READ, array(), 'name');
        foreach ($resources as $resource) {
            $data[] = $resource->toJson();
        }

        return $data;
    }

    /**
     * Handle output of the embedded widget: allows embedding calendar widgets
     * in external websites.
     *
     * The following arguments are required:
     *   - calendar: The share_name for the requested calendar.
     *   - container: The DOM node to populate with the widget.
     *   - view: The view (block) we want.
     *
     * The following are optional (and are not used for all views)
     *   - css
     *   - days
     *   - maxevents: The maximum number of events to show.
     *   - months: The number of months to include.
     */
    public function embed()
    {
        global $page_output, $registry;

        /* First, determine the type of view we are asking for */
        $view = $this->vars->view;

        /* The DOM container to put the HTML in on the remote site */
        $container = $this->vars->container;

        /* The share_name of the calendar to display */
        $calendar = $this->vars->calendar;

        /* Deault to showing only 1 month when we have a choice */
        $count_month = $this->vars->get('months', 1);

        /* Default to no limit for the number of events */
        $max_events = $this->vars->get('maxevents', 0);

        /* Default to one week */
        $count_days = $this->vars->get('days', 7);

        if ($this->vars->css == 'none') {
            $nocss = true;
        }

        /* Build the block parameters */
        $params = array(
            'calendar' => $calendar,
            'maxevents' => $max_events,
            'months' => $count_month,
            'days' => $count_days
        );

        /* Call the Horde_Block api to get the calendar HTML */
        $title = $registry->call('horde/blockTitle', array('kronolith', $view, $params));
        $results = $registry->call('horde/blockContent', array('kronolith', $view, $params));

        /* Some needed paths */
        $js_path = $registry->get('jsuri', 'kronolith');

        /* Local js */
        $jsurl = Horde::url($js_path . '/embed.js', true);

        /* Horde's js */
        $hjs_path = $registry->get('jsuri', 'horde');
        $hjsurl = Horde::url($hjs_path . '/tooltips.js', true);
        $pturl = Horde::url($hjs_path . '/prototype.js', true);

        /* CSS */
        if (empty($nocss)) {
            $page_output->addThemeStylesheet('embed.css');

            Horde::startBuffer();
            $page_output->includeStylesheetFiles(array('nobase' => true), true);
            $css = Horde::endBuffer();
        } else {
            $css = '';
        }

        /* Escape the text and put together the javascript to send back */
        $container = Horde_Serialize::serialize($container, Horde_Serialize::JSON);
        $results = Horde_Serialize::serialize('<div class="kronolith_embedded"><div class="title">' . $title . '</div>' . $results . '</div>', Horde_Serialize::JSON);

        $js = <<<EOT
if (typeof kronolith == 'undefined') {
    if (typeof Prototype == 'undefined') {
        document.write('<script type="text/javascript" src="$pturl"></script>');
    }
    if (typeof Horde_ToolTips == 'undefined') {
        Horde_ToolTips_Autoload = false;
        document.write('<script type="text/javascript" src="$hjsurl"></script>');
    }
    kronolith = new Object();
    kronolithNodes = new Array();
    document.write('<script type="text/javascript" src="$jsurl"></script>');
    document.write('$css');
}
kronolithNodes[kronolithNodes.length] = $container;
kronolith[$container] = $results;
EOT;

        return new Horde_Core_Ajax_Response_Raw($js, 'text/javascript');
    }

    public function toTimeslice()
    {
        $driver = $this->_getDriver($this->vars->cal);
        $event = $driver->getEvent($this->vars->e);

        try {
            Kronolith::toTimeslice($event, $this->vars->t, $this->vars->c);
        } catch (Kronolith_Exception $e) {
            $GLOBALS['notification']->push(sprintf(_("Error saving timeslice: %s"), $e->getMessage()), 'horde.error');
            return false;
        }
        $GLOBALS['notification']->push(_("Successfully saved timeslice."), 'horde.success');

        return true;
    }

    /**
     * Check reply status of any resources and report back. Used as a check
     * before saving an event to give the user feedback.
     *
     * The following arguments are expected:
     *   - r:  A comma separated string of resource identifiers.
     *   - s:  The event start time to check.
     *   - e:  The event end time to check.
     *   - u:  The event uid, if not a new event.
     *   - c:  The event's calendar.
     */
    public function checkResources()
    {
        if (empty($GLOBALS['conf']['resources']['enabled'])) {
            return array();
        }

        if ($this->vars->i) {
            $event = $this->_getDriver($this->vars->c)->getEvent($this->vars->i);
        } else {
            $event = Kronolith::getDriver()->getEvent();
        }
        // Overrite start/end times since we may be checking before we edit
        // an existing event with new times.
        $event->start = new Horde_Date($this->vars->s);
        $event->end = new Horde_Date($this->vars->e);
        $event->start->setTimezone(date_default_timezone_get());
        $event->end->setTimezone(date_default_timezone_get());
        $results = array();
        foreach (explode(',', $this->vars->r) as $id) {
            $resource = Kronolith::getDriver('Resource')->getResource($id);
            $results[$id] = $resource->getResponse($event);
        }

        return $results;
    }

    /**
     * Add a file to an event.
     *
     * The following arguments are expected:
     *   - i:  The event id.
     *   - c:  The calendar id.
     *
     *   The actual file data is returned in $_FILES and is handled in
     *   self::_addFileFromUpload()
     */
    public function addFile()
    {
        global $notification, $conf;

        $result = new stdClass;
        $result->success = 0;

        if (!isset($this->vars->i)) {
            $notification->push(_("Your attachment was not uploaded. Most likely, the file exceeded the maximum size allowed by the server configuration."), 'horde.warning');
        } else {
            try {
                $event = $this->_getDriver($this->vars->c)->getEvent($this->vars->i);
                if ($this->_canUploadFiles()) {
                    $max_files = $conf['documents']['count_limit'];
                    foreach ($this->_addFileFromUpload() as $f) {
                        if (!empty($conf['documents']['count_limit']) &&
                            count($event->listFiles()) >= $max_files) {
                            $notification->push(_("You have reached the maximum number of allowed files."), 'horde.notification');
                            break;
                        }
                        if ($f instanceof Kronolith_Exception) {
                            $notification->push($f, 'horde.error');
                        } else {
                            $event->addFile($f);
                            $result->success = 1;
                            $notification->push(_("The file was successfully uploaded."), 'horde.success');
                        }
                    }
                } elseif (empty($e)) {
                    $notification->push(_("Uploading files has been disabled on this server."), 'horde.error');
                }
            } catch (Kronolith_Exception $e) {
                $notification->push($e, 'horde.error');
            }
        }

        return $result;
    }

    /**
     * Removes a file from the specified event.
     *
     * The following arguments are expected:
     *   - source:  The type|calender source string.
     *   - key:     The event id.
     *   - name:    The filename to delete.
     */
    public function deleteFile()
    {
        global $notification;

        $result = new StdClass;
        $result->success = 0;

        try {
            $event = $this->_getDriver($this->vars->source)->getEvent($this->vars->key);
            if (!$event->hasPermission(Horde_Perms::EDIT)) {
                $notification->push(_("Permission Denied"), 'horde.error');
            } else {
                $event->deleteFile($this->vars->name);
                $result->success = 1;
                $notification->push(_("The file was successfully deleted."), 'horde.success');
            }
        } catch (Kronolith_Exception $e) {
            $notification->push($e, 'horde.error');
        }

        return $result;
    }

    /**
     * Check ability to upload files.
     *
     * @return integer  Maximum allowed size of file.
     */
    protected function _canUploadFiles()
    {
        global $browser, $conf;

        $size = $browser->allowFileUploads();
        return empty($conf['documents']['size_limit'])
            ? $size
            : min($size, $conf['documents']['size_limit']);
    }

    /**
     * Collect uploaded files.
     *
     * @return  array  An array of fileinfo hashes.
     */
    protected function _addFileFromUpload()
    {
        global $browser;

        try {
            $browser->wasFileUploaded('file_upload');
        } catch (Horde_Browser_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        $finfo = array();
        if (is_array($_FILES['file_upload']['size'])) {
            for ($i = 0; $i < count($_FILES['file_upload']['size']); ++$i) {
                $tmp = array();
                foreach ($_FILES['file_upload'] as $key => $val) {
                    $tmp[$key] = $val[$i];
                }
                $finfo[] = $tmp;
            }
        } else {
            $finfo[] = $_FILES['file_upload'];
        }

        return $finfo;
    }

    /**
     * Returns the driver object for a calendar.
     *
     * @param string $cal  A calendar string in the format "type|name".
     *
     * @return Kronolith_Driver|boolean  A driver instance or false on failure.
     */
    protected function _getDriver($cal)
    {
        list($driver, $calendar) = explode('|', $cal);
        if ($driver == 'internal' &&
            !Kronolith::hasPermission($calendar, Horde_Perms::SHOW)) {
            $GLOBALS['notification']->push(_("Permission Denied"), 'horde.error');
            return false;
        }
        try {
            $kronolith_driver = Kronolith::getDriver($driver, $calendar);
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
            return false;
        }
        if ($driver == 'remote') {
            $kronolith_driver->setParam('timeout', 15);
        }
        return $kronolith_driver;
    }

    /**
     * Saves an event and returns a signed result object including the saved
     * event.
     *
     * @param Kronolith_Event $event     An event object.
     * @param Kronolith_Event $original  If $event is an exception, this should
     *                                   be set to the original event.
     * @param object $attributes         The attributes sent by the client.
     *                                   Expected to contain cstart and cend.
     * @param boolean $saveOriginal      Commit any changes in $original to
     *                                   storage also.
     *
     * @return object  The result object.
     */
    protected function _saveEvent(Kronolith_Event $event,
                                  Kronolith_Event $original = null,
                                  $attributes = null,
                                  $saveOriginal = false)
    {
        if ($this->vars->targetcalendar) {
            $cal = $this->vars->targetcalendar;
        } elseif ($this->vars->cal) {
            $cal = $this->vars->cal;
        } else {
            $cal = $event->calendarType . '|' . $event->calendar;
        }
        $result = $this->_signedResponse($cal);
        $events = array();
        try {
            $event->save();
            if (!$this->vars->view_start || !$this->vars->view_end) {
              $result->events = array();
              return $result;
            }
            $end = new Horde_Date($this->vars->view_end);
            $end->hour = 23;
            $end->min = $end->sec = 59;
            Kronolith::addEvents(
                $events, $event,
                new Horde_Date($this->vars->view_start),
                $end, true, true);
            // If this is an exception, we re-add the original event also;
            // cstart and cend are the cacheStart and cacheEnd dates from the
            // client.
            if (!empty($original)) {
                Kronolith::addEvents(
                    $events, $original,
                    new Horde_Date($attributes->cstart),
                    new Horde_Date($attributes->cend),
                    true, true);
                if ($saveOriginal) {
                    $original->save();
                }
            }

            // If this event recurs, we must add any bound exceptions to the
            // results
            if ($event->recurs()) {
                $bound = $event->boundExceptions(false);
                foreach ($bound as $day => &$exceptions) {
                    foreach ($exceptions as &$exception) {
                        $exception = $exception->toJson();
                    }
                }
                Kronolith::mergeEvents($events, $bound);
            }
            $result->events = count($events) ? $events : array();
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
        }
        return $result;
    }

    /**
     * Creates a result object with the signature of the current request.
     *
     * @param string $calendar  A calendar id.
     *
     * @return object  The result object.
     */
    protected function _signedResponse($calendar)
    {
        $result = new stdClass;
        $result->cal = $calendar;
        $result->view = $this->vars->view;
        $result->sig = $this->vars->sig;
        return $result;
    }

    /**
     * Add an exception to the original event.
     *
     * @param Kronolith_Event $event  The recurring event.
     * @param object $attributes      The attributes passed from the client.
     *                                Expected to contain either rstart or rday.
     *
     * @return Kronolith_Event  The event representing the exception, with
     *                          the start/end times set the same as the original
     *                          occurence.
     */
    protected function _addException(Kronolith_Event $event, $attributes)
    {
        if ($attributes->rstart) {
            $rstart = new Horde_Date($attributes->rstart);
            $rstart->setTimezone($event->start->timezone);
        } else {
            $rstart = new Horde_Date($attributes->rday);
            $rstart->setTimezone($event->start->timezone);
            $rstart->hour = $event->start->hour;
            $rstart->min = $event->start->min;
        }
        $event->recurrence->addException($rstart->year, $rstart->month, $rstart->mday);
        $event->save();
    }

    /**
     * Creates a new event that represents an exception to a recurring event.
     *
     * @param Kronolith_Event $event  The original recurring event.
     * @param Kronolith_Event $copy   If present, contains a copy of $event, but
     *                                with changes from edited event form.
     * @param stdClass $attributes    The attributes passed from the client.
     *                                Expected to contain rstart and rend or
     *                                rday that represents the original
     *                                starting/ending date of the instance.
     *
     * @return Kronolith_Event  The event representing the exception
     */
    protected function _copyEvent(Kronolith_Event $event, Kronolith_Event $copy = null, $attributes = null)
    {
        if (empty($copy)) {
            $copy = clone($event);
        }

        if ($attributes->rstart) {
            $rstart = new Horde_Date($attributes->rstart);
            $rstart->setTimezone($event->start->timezone);
            $rend = new Horde_Date($attributes->rend);
            $rend->setTimezone($event->end->timezone);
        } else {
            $rstart = new Horde_Date($attributes->rday);
            $rstart->setTimezone($event->start->timezone);
            $rstart->hour = $event->start->hour;
            $rstart->min = $event->start->min;
            $rend = $rstart->add($event->getDuration);
            $rend->setTimezone($event->end->timezone);
            $rend->hour = $event->end->hour;
            $rend->min = $event->end->min;
        }
        $uid = $event->uid;
        $otime = $event->start->strftime('%T');

        // Create new event for the exception
        $nevent = $event->getDriver()->getEvent();
        $nevent->baseid = $uid;
        $nevent->exceptionoriginaldate = new Horde_Date($rstart->strftime('%Y-%m-%d') . 'T' . $otime);
        $nevent->exceptionoriginaldate->setTimezone($event->start->timezone);
        $nevent->creator = $event->creator;
        $nevent->title = $copy->title;
        $nevent->description = $copy->description;
        $nevent->location = $copy->location;
        $nevent->private = $copy->private;
        $nevent->url = $copy->url;
        $nevent->status = $copy->status;
        $nevent->attendees = $copy->attendees;
        $nevent->setResources($copy->getResources());
        $nevent->start = $rstart;
        $nevent->end = $rend;
        $nevent->initialized = true;

        return $nevent;
    }

}
