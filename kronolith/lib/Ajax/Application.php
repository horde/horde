<?php
/**
 * Defines the AJAX interface for Kronolith.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Gonçalo Queirós <mail@goncaloqueiros.net>
 * @package Kronolith
 */
class Kronolith_Ajax_Application extends Horde_Ajax_Application_Base
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
    public function __construct($app, $action = null)
    {
        parent::__construct($app, $action);
        $this->_defaultDomain = empty($GLOBALS['conf']['storage']['default_domain']) ? null : $GLOBALS['conf']['storage']['default_domain'];
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
            $events = $kronolith_driver->listEvents($start, $end, true, false, true);
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
            $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
            if ($perms->hasAppPermission('max_events') !== true &&
                $perms->hasAppPermission('max_events') <= Kronolith::countEvents()) {
                try {
                    $message = Horde::callHook('perms_denied', array('kronolith:max_events'));
                } catch (Horde_Exception_HookNotSet $e) {
                    $message = @htmlspecialchars(sprintf(_("You are not allowed to create more than %d events."), $perms->hasAppPermission('max_events')), ENT_COMPAT, Horde_Nls::getCharset());
                }
                $GLOBALS['notification']->push($message, 'horde.error', array('content.raw'));
                return $result;
            }
        }

        if ($this->_vars->event &&
            $this->_vars->cal &&
            $this->_vars->cal != $this->_vars->targetcalendar) {
            if (strpos($kronolith_driver->calendar, ':')) {
                list($target, $user) = explode(':', $kronolith_driver->calendar, 2);
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

        $event->readForm();

        $result = $this->_saveEvent($event);
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
     * TODO
     */
    public function updateEvent()
    {
        $result = $this->_signedResponse($this->_vars->cal);

        if (!($kronolith_driver = $this->_getDriver($this->_vars->cal)) ||
            !isset($this->_vars->id)) {
            return $result;
        }

        try {
            $event = $kronolith_driver->getEvent($this->_vars->id);
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
            return $result;
        }
        if (!$event) {
            $GLOBALS['notification']->push(_("The requested event was not found."), 'horde.error');
            return $result;
        } elseif (!$event->hasPermission(Horde_Perms::EDIT)) {
            $GLOBALS['notification']->push(_("You do not have permission to edit this event."), 'horde.warning');
            return $result;
        }

        $attributes = Horde_Serialize::unserialize($this->_vars->att, Horde_Serialize::JSON);
        foreach ($attributes as $attribute => $value) {
            switch ($attribute) {
            case 'start_date':
                $start = new Horde_Date($value);
                $event->start->year = $start->year;
                $event->start->month = $start->month;
                $event->start->mday = $start->mday;
                $event->end = $event->start->add(array('min' => $event->durMin));
                break;

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
                $event->start->mday += $value;
                $event->end->mday += $value;
                if ($event->recurs()) {
                    $event->recurrence->start->mday += $value;
                }
                break;

            case 'offMins':
                $event->start->min += $value;
                $event->end->min += $value;
                if ($event->recurs()) {
                    $event->recurrence->start->min += $value;
                }
                break;
            }
        }

        // @todo: What about iTip notifications?
        return $this->_saveEvent($event);
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

        $due = trim($task['due_date'] . ' ' . $task['due_time']);
        if (!empty($due)) {
            // strptime() is locale dependent, i.e. %p is not always matching
            // AM/PM. Set the locale to C to workaround this, but grab the
            // locale's D_FMT before that.
            $date_format = Horde_Nls::getLangInfo(D_FMT);
            $old_locale = setlocale(LC_TIME, 0);
            setlocale(LC_TIME, 'C');
            $format = $date_format . ' ' . ($GLOBALS['prefs']->getValue('twentyFour') ? '%H:%M' : '%I:%M %p');

            // Try exact format match first.
            if ($date_arr = strptime($due, $format)) {
                $due = new Horde_Date(
                    array('year'  => $date_arr['tm_year'] + 1900,
                          'month' => $date_arr['tm_mon'] + 1,
                          'mday'  => $date_arr['tm_mday'],
                          'hour'  => $date_arr['tm_hour'],
                          'min'   => $date_arr['tm_min'],
                          'sec'   => $date_arr['tm_sec']));
            } else {
                $due = new Horde_Date($due);
            }
            setlocale(LC_TIME, $old_locale);
            $task['due'] = $due->timestamp();
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

        $result = $this->_signedResponse('tasklists|tasks/' . $task['tasklist']);
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
            $tagger = Kronolith::getTagger();
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
                    $result->perms = Kronolith::permissionToJson($calendar->getPermission());
                } catch (Exception $e) {
                    $GLOBALS['notification']->push($e, 'horde.error');
                    return $result;
                }
                $GLOBALS['notification']->push(sprintf(_("The calendar \"%s\" has been created."), $info['name']), 'horde.success');
                $result->calendar = $calendar->getName();
                $tagger->tag($result->calendar, $this->_vars->tags, $calendar->get('owner'), 'calendar');
                break;
            }

            // Update a calendar.
            try {
                $calendar = $GLOBALS['kronolith_shares']->getShare($calendar_id);
                $original_name = $calendar->get('name');
                Kronolith::updateShare($calendar, $info);
                Kronolith::readPermsForm($calendar);
                $result->perms = Kronolith::permissionToJson($calendar->getPermission());
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                return $result;

            }
            $tagger->replaceTags($calendar->getName(), $this->_vars->tags, $calendar->get('owner'), 'calendar');

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
                    $tasklist = $GLOBALS['registry']->tasks->addTasklist($calendar['name'], $calendar['description'], $calendar['color']);
                    Kronolith::readPermsForm($tasklist);
                    $result->perms = Kronolith::permissionToJson($tasklist->getPermission());
                } catch (Exception $e) {
                    $GLOBALS['notification']->push($e, 'horde.error');
                    return $result;
                }
                $GLOBALS['notification']->push(sprintf(_("The task list \"%s\" has been created."), $calendar['name']), 'horde.success');
                $result->calendar = $tasklist;
                break;
            }

            // Update a task list.
            $calendar_id = substr($calendar_id, 6);
            try {
                $GLOBALS['registry']->tasks->updateTasklist($calendar_id, $calendar);
                $tasklists = $GLOBALS['registry']->tasks->listTasklists(true, Horde_Perms::EDIT);
                Kronolith::readPermsForm($tasklists[$calendar_id]);
                $result->perms = Kronolith::permissionToJson($tasklists[$calendar_id]->getPermission());
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                return $result;
            }
            if ($tasklists[$calendar_id]->get('name') != $calendar['name']) {
                $GLOBALS['notification']->push(sprintf(_("The task list \"%s\" has been renamed to \"%s\"."), $tasklists[$calendar_id]->get('name'), $calendar['name']), 'horde.success');
            } else {
                $GLOBALS['notification']->push(sprintf(_("The task list \"%s\" has been saved."), $tasklists[$calendar_id]->get('name')), 'horde.success');
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
                $result->calendar = $calendar['url'];
            }
            break;
        }

        $result->saved = true;
        $result->color = Kronolith::foregroundColor($calendar);

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
        $result->calendar = array(
            'name' => (!$calendar->get('owner') ? '' : '[' . $GLOBALS['registry']->convertUsername($calendar->get('owner'), false) . '] ') . $calendar->get('name'),
            'desc' => $calendar->get('desc'),
            'owner' => false,
            'fg' => Kronolith::foregroundColor($calendar),
            'bg' => Kronolith::backgroundColor($calendar),
            'show' => false,
            'perms' => Kronolith::permissionToJson($calendar->getPermission()),
            'edit' => $calendar->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT),
            'tg' => array_values($tagger->getTags($calendar->getName(), 'calendar')));
        return $result;
    }

    /**
     * TODO
     */
    public function getRemoteInfo()
    {
        $params = array('timeout' => 15);
        if ($user = $this->_vars->username) {
            $params['user'] = $user;
            $params['password'] = $this->_vars->password;
        }
        if (!empty($GLOBALS['conf']['http']['proxy']['proxy_host'])) {
            $params['proxy'] = $GLOBALS['conf']['http']['proxy'];
        }

        $result = new stdClass;
        try {
            $driver = Kronolith_Driver::factory('Ical', $params);
            $driver->open($this->_vars->url);
            $ical = $driver->getRemoteCalendar(false);
            $result->success = true;
            $name = $ical->getAttribute('X-WR-CALNAME');
            if (!($name instanceof PEAR_Error)) {
                $result->name = $name;
            }
            $desc = $ical->getAttribute('X-WR-CALDESC');
            if (!($desc instanceof PEAR_Error)) {
                $result->desc = $desc;
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
            !array_key_exists($calendar,
                              Kronolith::listCalendars(false, Horde_Perms::SHOW))) {
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
     * @param Kronlith_Event $event  An event object.
     *
     * @return object  The result object.
     */
    protected function _saveEvent($event)
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
            Kronolith::addEvents($events, $event,
                                 new Horde_Date($this->_vars->view_start),
                                 $end, true, true);
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

}
