<?php
/**
 * Defines the AJAX interface for Kronolith.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Gonçalo Queirós <mail@goncaloqueiros.net>
 * @package Kronolith
 */
class Kronolith_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     * Determines if notification information is sent in response.
     *
     * @var boolean
     */
    public $notify = true;

    /**
     * Constructor.
     *
     * @param string $app     The application name.
     * @param string $action  The AJAX action to perform.
     */
    public function __construct($app, $vars, $action = null)
    {
        parent::__construct($app, $vars, $action);
        $this->_defaultDomain = empty($GLOBALS['conf']['storage']['default_domain']) ? null : $GLOBALS['conf']['storage']['default_domain'];
    }

    /**
     * Just polls for alarm messages and keeps session fresh for now.
     */
    public function poll()
    {
        return false;
    }

    /**
     * TODO
     */
    public function listEvents()
    {
        $start = new Horde_Date($this->_vars->start);
        $end   = new Horde_Date($this->_vars->end);
        $result = $this->_signedResponse($this->_vars->cal);
        if (!($kronolith_driver = $this->_getDriver($this->_vars->cal))) {
            return $result;
        }
        try {
            session_write_close();
            $events = $kronolith_driver->listEvents($start, $end, true, false, true);
            session_start();
            if (count($events)) {
                $result->events = $events;
            }
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
        }
        return $result;
    }

    /**
     * TODO
     */
    public function getEvent()
    {
        $result = new stdClass;

        if (!($kronolith_driver = $this->_getDriver($this->_vars->cal)) ||
            !isset($this->_vars->id)) {
            return $result;
        }

        try {
            $event = $kronolith_driver->getEvent($this->_vars->id, $this->_vars->date);
            $result->event = $event->toJson(null, true, $GLOBALS['prefs']->getValue('twentyFour') ? 'H:i' : 'h:i A');
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
    public function saveEvent()
    {
        $result = $this->_signedResponse($this->_vars->targetcalendar);

        if (!($kronolith_driver = $this->_getDriver($this->_vars->targetcalendar))) {
            return $result;
        }

        if ($this->_vars->as_new) {
            unset($this->_vars->event);
        }
        if (!$this->_vars->event) {
            $perms = $GLOBALS['injector']->getInstance('Horde_Core_Perms');
            if ($perms->hasAppPermission('max_events') !== true &&
                $perms->hasAppPermission('max_events') <= Kronolith::countEvents()) {
                Horde::permissionDeniedError(
                    'kronolith',
                    'max_events',
                    sprintf(_("You are not allowed to create more than %d events."), $perms->hasAppPermission('max_events'))
                );
                return $result;
            }
        }

        if ($this->_vars->event &&
            $this->_vars->cal &&
            $this->_vars->cal != $this->_vars->targetcalendar) {
            if (strpos($kronolith_driver->calendar, '\\')) {
                list($target, $user) = explode('\\', $kronolith_driver->calendar, 2);
            } else {
                $target = $kronolith_driver->calendar;
                $user = $GLOBALS['registry']->getAuth();
            }
            $kronolith_driver = $this->_getDriver($this->_vars->cal);
            // Only delete the event from the source calendar if this user has
            // permissions to do so.
            try {
                $sourceShare = Kronolith::getInternalCalendar($kronolith_driver->calendar);
                $share = Kronolith::getInternalCalendar($target);
                if ($sourceShare->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE) &&
                    (($user == $GLOBALS['registry']->getAuth() &&
                      $share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) ||
                     ($user != $GLOBALS['registry']->getAuth() &&
                      $share->hasPermission($GLOBALS['registry']->getAuth(), Kronolith::PERMS_DELEGATE)))) {
                    $kronolith_driver->move($this->_vars->event, $target);
                    $kronolith_driver = $this->_getDriver($this->_vars->targetcalendar);
                }
            } catch (Exception $e) {
                $GLOBALS['notification']->push(sprintf(_("There was an error moving the event: %s"), $e->getMessage()), 'horde.error');
                return $result;
            }
        }

        if ($this->_vars->as_new) {
            $event = $kronolith_driver->getEvent();
        } else {
            try {
                $event = $kronolith_driver->getEvent($this->_vars->event);
            } catch (Horde_Exception_NotFound $e) {
                $GLOBALS['notification']->push(_("The requested event was not found."), 'horde.error');
                return $result;
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e);
                return $result;
            }
        }

        if (!$event->hasPermission(Horde_Perms::EDIT)) {
            $GLOBALS['notification']->push(_("You do not have permission to edit this event."), 'horde.warning');
            return $result;
        }

        try {
            $event->readForm();
            $result = $this->_saveEvent($event);
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e);
            return $result;
        }
        if (($result !== true) && $this->_vars->sendupdates) {
            Kronolith::sendITipNotifications($event, $GLOBALS['notification'], Kronolith::ITIP_REQUEST);
        }
        Kronolith::notifyOfResourceRejection($event);

        return $result;
    }

    /**
     * TODO
     */
    public function quickSaveEvent()
    {
        $cal = explode('|', $this->_vars->cal, 2);
        try {
            $event = Kronolith::quickAdd($this->_vars->text, $cal[1]);
            return $this->_saveEvent($event);
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
            return $this->_signedResponse($this->_vars->cal);
        }
    }

    /**
     * Update event details as a result of a drag/drop operation.
     */
    public function updateEvent()
    {
        $result = $this->_signedResponse($this->_vars->cal);

        if (!($kronolith_driver = $this->_getDriver($this->_vars->cal)) ||
            !isset($this->_vars->id)) {
            return $result;
        }

        try {
            $oevent = $kronolith_driver->getEvent($this->_vars->id);
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

        $attributes = Horde_Serialize::unserialize($this->_vars->att, Horde_Serialize::JSON);

        // If this is a recurring event, need to create an exception.
        if ($oevent->recurs()) {
            $event = $this->_createExceptionEvent($oevent, $attributes);
        } else {
            $event = clone($oevent);
        }

        foreach ($attributes as $attribute => $value) {
            switch ($attribute) {
            case 'start':
                $event->start = new Horde_Date($value);
                break;

            case 'end':
                $event->end = new Horde_Date($value);
                if ($event->end->hour == 23 &&
                    $event->end->min == 59 &&
                    $event->end->sec == 59) {
                    $event->end->mday++;
                    $event->end->hour = $event->end->min = $event->end->sec = 0;
                }
                break;

            case 'offDays':
                $event->start->mday = $event->start->mday + $value;
                $event->end->mday = $event->end->mday + $value;
                break;

            case 'offMins':
                $event->start->min += $value;
                $event->end->min += $value;
                break;
            }
        }

        // @todo: What about iTip notifications?
        return $this->_saveEvent($event, ($oevent->recurs() ? $oevent : null), $attributes);
    }

    /**
     * TODO
     */
    public function deleteEvent()
    {
        $result = new stdClass;

        if (!($kronolith_driver = $this->_getDriver($this->_vars->cal)) ||
            !isset($this->_vars->id)) {
            return $result;
        }

        try {
            $event = $kronolith_driver->getEvent($this->_vars->id);
            if (!$event->hasPermission(Horde_Perms::DELETE)) {
                $GLOBALS['notification']->push(_("You do not have permission to delete this event."), 'horde.warning');
                return $result;
            }
            if ($event->recurs()) {
                $result->uid = $event->uid;
            }
            $deleted = $kronolith_driver->deleteEvent($event->id);
            if ($this->_vars->sendupdates) {
                Kronolith::sendITipNotifications($event, $GLOBALS['notification'], Kronolith::ITIP_CANCEL);
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
        $query = Horde_Serialize::unserialize($this->_vars->query, Horde_Serialize::JSON);
        if (!isset($query->start)) {
            $query->start = new Horde_Date($_SERVER['REQUEST_TIME']);
        }
        if (!isset($query->end)) {
            $query->end = null;
        }
        switch ($this->_vars->time) {
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
        $cals = Horde_Serialize::unserialize($this->_vars->cals, Horde_Serialize::JSON);
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
        $result->query = $this->_vars->query;
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
        $result->list = $this->_vars->list;
        $result->type = $this->_vars->type;

        try {
            $tasks = $GLOBALS['registry']->tasks->listTasks(null, null, null, $this->_vars->list, $this->_vars->type == 'incomplete' ? 'future_incomplete' : $this->_vars->type, true);
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
            !isset($this->_vars->id) ||
            !isset($this->_vars->list)) {
            return false;
        }

        $result = new stdClass;
        try {
            $task = $GLOBALS['registry']->tasks->getTask($this->_vars->list, $this->_vars->id);
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

        $id = $this->_vars->task_id;
        $list = $this->_vars->old_tasklist;
        $task = $this->_vars->task;
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
    public function deleteTask()
    {
        $result = new stdClass;

        if (!$GLOBALS['registry']->hasMethod('tasks/deleteTask') ||
            !isset($this->_vars->id) ||
            !isset($this->_vars->list)) {
            return $result;
        }

        try {
            $GLOBALS['registry']->tasks->deleteTask($this->_vars->list, $this->_vars->id);
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
            $GLOBALS['registry']->tasks->toggleCompletion($this->_vars->id, $this->_vars->list);
            $result->toggled = true;
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
        $this->notify = false;
        $tagger = new Kronolith_Tagger();
        $result = new stdClass;
        $result->tags = array();
        $tags = $tagger->getCloud($GLOBALS['registry']->getAuth(), 10);
        foreach ($tags as $tag) {
            $result->tags[] = $tag['tag_name'];
        }
        return $result;
    }

    /**
     * TODO
     */
    public function getFreeBusy()
    {
        $result = new stdClass;
        try {
            $result->fb = Kronolith_FreeBusy::get($this->_vars->email, true);
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e->getMessage(), 'horde.warning');
        }
        return $result;
    }

    /**
     * TODO
     */
    public function searchCalendars()
    {
        $result = new stdClass;
        $result->events = 'Searched for calendars: ' . $this->_vars->title;
        return $result;
    }

    /**
     * TODO
     */
    public function saveCalendar()
    {
        $calendar_id = $this->_vars->calendar;
        $result = new stdClass;

        switch ($this->_vars->type) {
        case 'internal':
            $info = array();
            foreach (array('name', 'color', 'description', 'tags') as $key) {
                $info[$key] = $this->_vars->$key;
            }

            // Create a calendar.
            if (!$calendar_id) {
                if (!$GLOBALS['registry']->getAuth() ||
                    $GLOBALS['prefs']->isLocked('default_share')) {
                    return $result;
                }
                try {
                    $calendar = Kronolith::addShare($info);
                    Kronolith::readPermsForm($calendar);
                    if ($calendar->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::SHOW)) {
                        $wrapper = new Kronolith_Calendar_Internal(array('share' => $calendar));
                        $result->saved = true;
                        $result->id = $calendar->getName();
                        $result->calendar = $wrapper->toHash();
                    }
                } catch (Exception $e) {
                    $GLOBALS['notification']->push($e, 'horde.error');
                    return $result;
                }
                $GLOBALS['notification']->push(sprintf(_("The calendar \"%s\" has been created."), $info['name']), 'horde.success');
                break;
            }

            // Update a calendar.
            try {
                $calendar = $GLOBALS['kronolith_shares']->getShare($calendar_id);
                $original_name = $calendar->get('name');
                $original_owner = $calendar->get('owner');
                Kronolith::updateShare($calendar, $info);
                Kronolith::readPermsForm($calendar);
                if ($calendar->get('owner') != $original_owner) {
                    $result->deleted = true;
                }
                if ($calendar->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::SHOW)) {
                    $wrapper = new Kronolith_Calendar_Internal(array('share' => $calendar));
                    $result->saved = true;
                    $result->calendar = $wrapper->toHash();
                }
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                return $result;

            }
            if ($calendar->get('name') != $original_name) {
                $GLOBALS['notification']->push(sprintf(_("The calendar \"%s\" has been renamed to \"%s\"."), $original_name, $calendar->get('name')), 'horde.success');
            } else {
                $GLOBALS['notification']->push(sprintf(_("The calendar \"%s\" has been saved."), $original_name), 'horde.success');
            }
            break;

        case 'tasklists':
            $calendar = array();
            foreach (array('name', 'color', 'description') as $key) {
                $calendar[$key] = $this->_vars->$key;
            }

            // Create a task list.
            if (!$calendar_id) {
                if (!$GLOBALS['registry']->getAuth() ||
                    $GLOBALS['prefs']->isLocked('default_share')) {
                    return $result;
                }
                try {
                    $tasklistId = $GLOBALS['registry']->tasks->addTasklist($calendar['name'], $calendar['description'], $calendar['color']);
                    $tasklists = $GLOBALS['registry']->tasks->listTasklists(true);
                    if (!isset($tasklists[$tasklistId])) {
                        $GLOBALS['notification']->push(_("Added task list not found."), 'horde.error');
                        return $result;
                    }
                    $tasklist = $tasklists[$tasklistId];
                    Kronolith::readPermsForm($tasklist);
                    if ($tasklist->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::SHOW)) {
                        $wrapper = new Kronolith_Calendar_External_Tasks(array('api' => 'tasks', 'name' => $tasklistId, 'share' => $tasklist));

                        // Update external calendars caches.
                        $all_external = $GLOBALS['session']->get('kronolith', 'all_external_calendars');
                        $all_external[] = array('a' => 'tasks', 'n' => $tasklistId, 'd' => $tasklist->get('name'));
                        $GLOBALS['session']->set('kronolith', 'all_external_calendars', $all_external);
                        $GLOBALS['display_external_calendars'][] = 'tasks/' . $tasklistId;
                        $GLOBALS['prefs']->setValue('display_external_cals', serialize($GLOBALS['display_external_calendars']));
                        $GLOBALS['all_external_calendars']['tasks/' . $tasklistId] = $wrapper;

                        $result->saved = true;
                        $result->id = 'tasks/' . $tasklistId;
                        $result->calendar = $wrapper->toHash();
                    }
                } catch (Exception $e) {
                    $GLOBALS['notification']->push($e, 'horde.error');
                    return $result;
                }
                $GLOBALS['notification']->push(sprintf(_("The task list \"%s\" has been created."), $calendar['name']), 'horde.success');
                break;
            }

            // Update a task list.
            $calendar_id = substr($calendar_id, 6);
            try {
                $GLOBALS['registry']->tasks->updateTasklist($calendar_id, $calendar);
                $tasklists = $GLOBALS['registry']->tasks->listTasklists(true, Horde_Perms::EDIT);
                $tasklist = $tasklists[$calendar_id];
                $original_owner = $tasklist->get('owner');
                Kronolith::readPermsForm($tasklist);
                if ($tasklist->get('owner') != $original_owner) {
                    $result->deleted = true;
                }
                if ($tasklist->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::SHOW)) {
                    $wrapper = new Kronolith_Calendar_External_Tasks(array('api' => 'tasks', 'name' => $calendar_id, 'share' => $tasklist));
                    $result->saved = true;
                    $result->calendar = $wrapper->toHash();
                }
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                return $result;
            }
            if ($tasklist->get('name') != $calendar['name']) {
                $GLOBALS['notification']->push(sprintf(_("The task list \"%s\" has been renamed to \"%s\"."), $tasklist->get('name'), $calendar['name']), 'horde.success');
            } else {
                $GLOBALS['notification']->push(sprintf(_("The task list \"%s\" has been saved."), $tasklist->get('name')), 'horde.success');
            }
            break;

        case 'remote':
            $calendar = array();
            foreach (array('name', 'desc', 'url', 'color', 'user', 'password') as $key) {
                $calendar[$key] = $this->_vars->$key;
            }
            try {
                Kronolith::subscribeRemoteCalendar($calendar, $calendar_id);
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                return $result;
            }
            if ($calendar_id) {
                $GLOBALS['notification']->push(sprintf(_("The calendar \"%s\" has been saved."), $calendar['name']), 'horde.success');
            } else {
                $GLOBALS['notification']->push(sprintf(_("You have been subscribed to \"%s\" (%s)."), $calendar['name'], $calendar['url']), 'horde.success');
                $result->id = $calendar['url'];
            }
            $wrapper = new Kronolith_Calendar_Remote($calendar);
            $result->saved = true;
            $result->calendar = $wrapper->toHash();
            break;
        }

        return $result;
    }

    /**
     * TODO
     */
    public function deleteCalendar()
    {
        $calendar_id = $this->_vars->calendar;
        $result = new stdClass;

        switch ($this->_vars->type) {
        case 'internal':
            try {
                $calendar = $GLOBALS['kronolith_shares']->getShare($calendar_id);
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
        if (!isset($GLOBALS['all_calendars'][$this->_vars->cal])) {
            $GLOBALS['notification']->push(_("You are not allowed to view this calendar."), 'horde.error');
            return $result;
        }
        $calendar = $GLOBALS['all_calendars'][$this->_vars->cal];
        $tagger = Kronolith::getTagger();
        $result->calendar = $calendar->toHash();
        return $result;
    }

    /**
     * TODO
     */
    public function getRemoteInfo()
    {
        $params = array('timeout' => 15);
        if ($user = $this->_vars->user) {
            $params['user'] = $user;
            $params['password'] = $this->_vars->password;
        }
        if (!empty($GLOBALS['conf']['http']['proxy']['proxy_host'])) {
            $params['proxy'] = $GLOBALS['conf']['http']['proxy'];
        }

        $result = new stdClass;
        try {
            $driver = $GLOBALS['injector']->getInstance('Kronolith_Factory_Driver')->create('Ical', $params);
            $driver->open($this->_vars->url);
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
     *
     * @return object  The result object.
     */
    protected function _saveEvent(Kronolith_Event $event,
                                  Kronolith_Event $original = null,
                                  $attributes = null)
    {
        if ($this->_vars->targetcalendar) {
            $cal = $this->_vars->targetcalendar;
        } elseif ($this->_vars->cal) {
            $cal = $this->_vars->cal;
        } else {
            $cal = $event->calendarType . '|' . $event->calendar;
        }
        $result = $this->_signedResponse($cal);
        if (!$this->_vars->view_start || !$this->_vars->view_end) {
            $result->events = array();
            return $result;
        }

        try {
            $event->save();
            $end = new Horde_Date($this->_vars->view_end);
            $end->hour = 23;
            $end->min = $end->sec = 59;
            $events = array();
            Kronolith::addEvents($events, $event,
                                 new Horde_Date($this->_vars->view_start),
                                 $end, true, true);


            /* If this is an exception, we re-add the original event as well
             * cstart and cend are the cacheStart and cacheEnd dates from the
             * client. */
            if (!empty($original)) {
                Kronolith::addEvents($events, $original,
                                     new Horde_Date($attributes->cstart),
                                     new Horde_Date($attributes->cend),
                                     true, true);
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
        $result->view = $this->_vars->view;
        $result->sig = $this->_vars->sig;
        return $result;
    }

    /**
     * Creates a new event that represents an exception to a recurring event.
     * Causes an exception to be added to the original event as well.
     *
     * @param Kronolith_Event $event  The recurring event.
     * @param object $attributes      The attributes passed from the client.
     *                                Expected to contain rstart and rend.
     *
     * @return Kronolith_Event  The event representing the exception, with
     *                          the start/end times set the same as the original
     *                          occurence.
     */
    protected function _createExceptionEvent(Kronolith_Event $event, $attributes)
    {
        // Add the exception to the original event
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

        $event->recurrence->addException($rstart->year, $rstart->month, $rstart->mday);
        $event->save();
        $uid = $event->uid;
        $otime = $event->start->strftime('%T');

        // Create new event for the exception
        $nevent = $event->getDriver()->getEvent();
        $nevent->baseid = $uid;
        $nevent->exceptionoriginaldate = new Horde_Date($rstart->strftime('%Y-%m-%d') . 'T' . $otime);
        $nevent->creator = $event->creator;
        $nevent->title = $event->title;
        $nevent->description = $event->description;
        $nevent->location = $event->location;
        $nevent->private = $event->private;
        $nevent->url = $event->url;
        $nevent->status = $event->status;
        $nevent->attendees = $event->attendees;
        //@TODO: add resources - need a Kronolith_Event::setResource() method.
        $nevent->start = $rstart;
        $nevent->end = $rend;
        $nevent->initialized = true;

        return $nevent;
    }
}
