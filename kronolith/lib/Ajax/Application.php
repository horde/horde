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
     * Returns a notification handler object to use to output any
     * notification messages triggered by the action.
     *
     * @return Horde_Notification_Handler_Base   The notification handler.
     */
    public function notificationHandler()
    {
        return $GLOBALS['injector']->getInstance('Horde_Notification_Listener');
    }

    /**
     * TODO
     */
    public function ListEvents($vars)
    {
        $start = new Horde_Date($vars->start);
        $end   = new Horde_Date($vars->end);
        $result = new stdClass;
        $result->cal = $vars->cal;
        $result->view = $vars->view;
        $result->sig = $start->dateString() . $end->dateString();
        if (!($kronolith_driver = $this->_getDriver($vars->cal))) {
            return $result;
        }
        $events = $kronolith_driver->listEvents($start, $end, true, false, true);
        if (count($events)) {
            $result->events = $events;
        }
        return $result;
    }

    /**
     * TODO
     */
    public function GetEvent($vars)
    {
        if (!($kronolith_driver = $this->_getDriver($vars->cal)) ||
            !isset($vars->id)) {
            return false;
        }

        $event = $kronolith_driver->getEvent($vars->id, $vars->date);
        if (!$event) {
            $GLOBALS['notification']->push(_("The requested event was not found."), 'horde.error');
            return false;
        }

        $result = new stdClass;
        $result->event = $event->toJson(null, true, $GLOBALS['prefs']->getValue('twentyFour') ? 'H:i' : 'h:i A');

        return $result;
    }

    /**
     * TODO
     */
    public function SaveEvent($vars)
    {
        if (!($kronolith_driver = $this->_getDriver($vars->targetcalendar))) {
            return false;
        }

        $event = $kronolith_driver->getEvent($vars->id);
        if (!$event) {
            $GLOBALS['notification']->push(_("The requested event was not found."), 'horde.error');
            return false;
        } elseif (!$event->hasPermission(Horde_Perms::EDIT)) {
            $notification->push(_("You do not have permission to edit this event."), 'horde.warning');
            return false;
        }

        $event->readForm();
        $result = $this->_saveEvent($event);
        if (($result !== true) && $vars->sendupdates) {
            Kronolith::sendITipNotifications($event, $GLOBALS['notification'], Kronolith::ITIP_REQUEST);
        }

        return $result;
    }

    /**
     * TODO
     */
    public function QuickSaveEvent($vars)
    {
        try {
            $event = Kronolith::quickAdd($vars->text, Kronolith::getDefaultCalendar(Horde_Perms::EDIT));
            return $this->_saveEvent($event);
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
            return false;
        }
    }

    /**
     * TODO
     */
    public function UpdateEvent($vars)
    {
        if (!($kronolith_driver = $this->_getDriver($vars->cal)) ||
            !isset($vars->id)) {
            return false;
        }

        $event = $kronolith_driver->getEvent($vars->id);
        if (!$event) {
            $GLOBALS['notification']->push(_("The requested event was not found."), 'horde.error');
            return false;
        } elseif (!$event->hasPermission(Horde_Perms::EDIT)) {
            $GLOBALS['notification']->push(_("You do not have permission to edit this event."), 'horde.warning');
            return false;
        }

        $attributes = Horde_Serialize::unserialize($vars->att, Horde_Serialize::JSON);
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
                break;

            case 'offMins':
                $event->start->min += $value;
                $event->end->min += $value;
                break;
            }
        }

        return $this->_saveEvent($event);
    }

    /**
     * TODO
     */
    public function DeleteEvent($vars)
    {
        if (!($kronolith_driver = $this->_getDriver($vars->cal)) ||
            !isset($vars->id)) {
            return false;
        }

        $event = $kronolith_driver->getEvent($vars->id);
        if (!$event) {
            $GLOBALS['notification']->push(_("The requested event was not found."), 'horde.error');
            return false;
        } elseif (!$event->hasPermission(Horde_Perms::DELETE)) {
            $GLOBALS['notification']->push(_("You do not have permission to delete this event."), 'horde.warning');
            return false;
        }

        $deleted = $kronolith_driver->deleteEvent($event->id);

        if ($vars->sendupdates) {
            Kronolith::sendITipNotifications($event, $GLOBALS['notification'], Kronolith::ITIP_CANCEL);
        }

        $result = new stdClass;
        $result->deleted = true;

        return $result;
    }

    /**
     * TODO
     */
    public function SearchEvents($vars)
    {
        $query = Horde_Serialize::unserialize($vars->query, Horde_Serialize::JSON);
        if (!isset($query->start)) {
            $query->start = new Horde_Date($_SERVER['REQUEST_TIME']);
        }
        if (!isset($query->end)) {
            $query->end = null;
        }

        $cals = Horde_Serialize::unserialize($vars->cals, Horde_Serialize::JSON);
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
        }

        $result = new stdClass;
        $result->view = 'search';
        $result->query = $vars->query;
        if ($events) {
            $result->events = $events;
        }

        return $result;
    }

    /**
     * TODO
     */
    public function ListTasks($vars)
    {
        if (!$GLOBALS['registry']->hasMethod('tasks/listTasks')) {
            return false;
        }

        try {
            $tasks = $GLOBALS['registry']->tasks->listTasks(null, null, null, $vars->list, $vars->type == 'incomplete' ? 'future_incomplete' : $vars->type, true);
        } catch (Exception $e)
            $GLOBALS['notification']->push($e, 'horde.error');
            return false;
        }

        $result = new stdClass;
        $result->list = $vars->list;
        $result->type = $vars->type;
        $result->sig = $vars->sig;
        if (count($tasks)) {
            $result->tasks = $tasks;
        }

        return $result;
    }

    /**
     * TODO
     */
    public function GetTask($vars)
    {
        if (!$GLOBALS['registry']->hasMethod('tasks/getTask') ||
            !isset($vars->id) ||
            !isset($vars->list)) {
            return false;
        }

        try {
            $task = $GLOBALS['registry']->tasks->getTask($vars->list, $vars->id);
            if (!$task) {
                $GLOBALS['notification']->push(_("The requested task was not found."), 'horde.error');
                return false;
            }
        } catch (Exception $e)
            $GLOBALS['notification']->push($e, 'horde.error');
            return false;
        }

        $result = new stdClass;
        $result->task = $task->toJson(true, $GLOBALS['prefs']->getValue('twentyFour') ? 'H:i' : 'h:i A');

        return $result;
    }

    /**
     * TODO
     */
    public function SaveTask($vars)
    {
        if (!$GLOBALS['registry']->hasMethod('tasks/updateTask') ||
            !$GLOBALS['registry']->hasMethod('tasks/addTask')) {
            return false;
        }

        $id = $vars->task_id;
        $list = $vars->old_tasklist;
        $task = $vars->task;

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

        $task['alarm'] = $task['alarm']['on']
            ? $task['alarm']['value'] * $task['alarm']['unit']
            : 0;

        try {
            $result = ($id && $list)
                ? $GLOBALS['registry']->tasks->updateTask($list, $id, $task)
                : $GLOBALS['registry']->tasks->addTask($task);
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
            return false;
        }

        if (!$id) {
            $id = $result[0];
        }
        try {
            $task = $GLOBALS['registry']->tasks->getTask($task['tasklist'], $id);
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
            return false;
        }

        $result = new stdClass;
        $result->type = $task->completed ? 'complete' : 'incomplete';
        $result->list = $task->tasklist;
        $result->sig = $vars->sig;
        $result->tasks = array($id => $task->toJson(false, $GLOBALS['prefs']->getValue('twentyFour') ? 'H:i' : 'h:i A'));

        return $result;
    }

    /**
     * TODO
     */
    public function DeleteTask($vars)
    {
        if (!$GLOBALS['registry']->hasMethod('tasks/deleteTask') ||
            !isset($vars->id) ||
            !isset($vars->list)) {
            return false;
        }

        try {
            $GLOBALS['registry']->tasks->deleteTask($vars->list, $vars->id);
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
            return false;
        }

        $result = new stdClass;
        $result->deleted = true;

        return $result;
    }

    /**
     * TODO
     */
    public function ToggleCompletion($vars)
    {
        if (!$GLOBALS['registry']->hasMethod('tasks/toggleCompletion')) {
            return false;
        }

        try {
            $GLOBALS['registry']->tasks->toggleCompletion($vars->id, $vars->list);
        } catch (Exception $e)
            $GLOBALS['notification']->push($e, 'horde.error');
            return false;
        }

        $result = new stdClass;
        $result->toggled = true;

        return $result;
    }

    /**
     * TODO
     */
    public function ListTopTags($vars)
    {
        $tagger = new Kronolith_Tagger();
        $result = new stdClass;
        $result->tags = array();
        $tags = $tagger->getCloud(Horde_Auth::getAuth(), 10);
        foreach ($tags as $tag) {
            $result->tags[] = $tag['tag_name'];
        }
        return $result;
    }

    /**
     * TODO
     */
    public function GetFreeBusy($vars)
    {
        try {
            $fb = Kronolith_FreeBusy::get($vars->email, true);
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e->getMessage(), 'horde.warning');
            return false;
        }
        $result = new stdClass;
        $result->fb = $fb;
        return $result;
    }

    /**
     * TODO
     */
    public function SearchCalendars($vars)
    {
        $result = new stdClass;
        $result->events = 'Searched for calendars: ' . $vars->title;
        return $result;
    }

    /**
     * TODO
     */
    public function SaveCalendar($vars)
    {
        $calendar_id = $vars->calendar;
        $result = new stdClass;

        switch ($vars->type) {
        case 'internal':
            $info = array();
            foreach (array('name', 'color', 'description', 'tags') as $key) {
                $info[$key] = $vars->$key;
            }

            // Create a calendar.
            if (!$calendar_id) {
                if (!Horde_Auth::getAuth() ||
                    $GLOBALS['prefs']->isLocked('default_share')) {
                    return false;
                }
                try {
                    $calendar = Kronolith::addShare($info);
                } catch (Exception $e) {
                    $GLOBALS['notification']->push($e, 'horde.error');
                    return false;
                }
                $GLOBALS['notification']->push(sprintf(_("The calendar \"%s\" has been created."), $info['name']), 'horde.success');
                $result->calendar = $calendar->getName();
                break;
            }

            // Update a calendar.
            try {
                $calendar = $GLOBALS['kronolith_shares']->getShare($calendar_id);
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                return false;
            }
            $original_name = $calendar->get('name');
            try {
                Kronolith::updateShare($calendar, $info);
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                return false;

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
                $calendar[$key] = $vars->$key;
            }

            // Create a task list.
            if (!$calendar_id) {
                if (!Horde_Auth::getAuth() ||
                    $GLOBALS['prefs']->isLocked('default_share')) {
                    return false;
                }
                try {
                    $tasklist = $GLOBALS['registry']->tasks->addTasklist($calendar['name'], $calendar['description'], $calendar['color']);
                } catch (Exception $e) {
                    $GLOBALS['notification']->push($e, 'horde.error');
                    return false;
                }
                $GLOBALS['notification']->push(sprintf(_("The task list \"%s\" has been created."), $calendar['name']), 'horde.success');
                $result->calendar = $tasklist;
                break;
            }

            // Update a task list.
            $calendar_id = substr($calendar_id, 6);
            $tasklists = $GLOBALS['registry']->tasks->listTasklists(true, Horde_Perms::EDIT);
            if (!isset($tasklists[$calendar_id])) {
                $GLOBALS['notification']->push(_("You are not allowed to change this task list."), 'horde.error');
                return false;
            }
            try {
                $GLOBALS['registry']->tasks->updateTasklist($calendar_id, $calendar);
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                return false;
            }
            if ($tasklists[$calendar_id]->get('name') != $calendar['name']) {
                $GLOBALS['notification']->push(sprintf(_("The task list \"%s\" has been renamed to \"%s\"."), $tasklists[$calendar_id]->get('name'), $calendar['name']), 'horde.success');
            } else {
                $GLOBALS['notification']->push(sprintf(_("The task list \"%s\" has been saved."), $tasklists[$calendar_id]->get('name')), 'horde.success');
            }
            break;

        case 'remote':
            $calendar = array();
            foreach (array('name', 'description', 'url', 'color', 'username', 'password') as $key) {
                $calendar[$key] = $vars->$key;
            }
            try {
                Kronolith::subscribeRemoteCalendar($calendar);
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                return false;
            }
            if ($calendar_id) {
                $GLOBALS['notification']->push(sprintf(_("The calendar \"%s\" has been saved."), $calendar['name']), 'horde.success');
            } else {
                $GLOBALS['notification']->push(sprintf(_("You have been subscribed to \"%s\" (%s)."), $calendar['name'], $calendar['url']), 'horde.success');
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
    public function DeleteCalendar($vars)
    {
        $calendar_id = $vars->calendar;

        switch ($vars->type) {
        case 'internal':
            try {
                $calendar = $GLOBALS['kronolith_shares']->getShare($calendar_id);
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                return false;
            }
            try {
                Kronolith::deleteShare($calendar);
            } catch (Exception $e) {
                $GLOBALS['notification']->push(sprintf(_("Unable to delete \"%s\": %s"), $calendar->get('name'), $e->getMessage()), 'horde.error');
                return false;
            }
            $GLOBALS['notification']->push(sprintf(_("The calendar \"%s\" has been deleted."), $calendar->get('name')), 'horde.success');
            break;

        case 'tasklists':
            $calendar_id = substr($calendar_id, 6);
            $tasklists = $GLOBALS['registry']->tasks->listTasklists(true);
            if (!isset($tasklists[$calendar_id])) {
                $GLOBALS['notification']->push(_("You are not allowed to delete this task list."), 'horde.error');
                return false;
            }
            try {
                $GLOBALS['registry']->tasks->deleteTasklist($calendar_id);
            } catch (Exception $e) {
                $GLOBALS['notification']->push(sprintf(_("Unable to delete \"%s\": %s"), $tasklists[$calendar_id]->get('name'), $e->getMessage()), 'horde.error');
                return false;
            }
            $GLOBALS['notification']->push(sprintf(_("The task list \"%s\" has been deleted."), $tasklists[$calendar_id]->get('name')), 'horde.success');
            break;

        case 'remote':
            try {
                $deleted = Kronolith::unsubscribeRemoteCalendar($calendar_id);
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                return false;
            }
            $GLOBALS['notification']->push(sprintf(_("You have been unsubscribed from \"%s\" (%s)."), $deleted['name'], $deleted['url']), 'horde.success');
            break;
        }

        $result = new stdClass;
        $result->deleted = true;

        return $result;
    }

    /**
     * TODO
     */
    public function GetRemoteInfo($vars)
    {
        $params = array('timeout' => 15);
        if ($user = $vars->username) {
            $params['user'] = $user;
            $params['password'] = $vars->password;
        }
        if (!empty($GLOBALS['conf']['http']['proxy']['proxy_host'])) {
            $params['proxy'] = $GLOBALS['conf']['http']['proxy'];
        }
        $driver = Kronolith_Driver::factory('Ical', $params);
        $driver->open($vars->url);
        try {
            $ical = $driver->getRemoteCalendar(false);
        } catch (Kronolith_Exception $e) {
            if ($e->getCode() == 401) {
                $result = new stdClass;
                $result->auth = true;
                return $result;
            }
            throw $e;
        }
        $result = new stdClass;
        $result->success = true;
        $name = $ical->getAttribute('X-WR-CALNAME');
        if (!($name instanceof PEAR_Error)) {
            $result->name = $name;
        }
        $desc = $ical->getAttribute('X-WR-CALDESC');
        if (!($desc instanceof PEAR_Error)) {
            $result->desc = $desc;
        }

        return $result;
    }

    /**
     * TODO
     */
    public function SaveCalPref($vars)
    {
        return false;
    }

    public function ChunkContent()
    {
        $chunk = basename(Horde_Util::getPost('chunk'));
        $result = new stdClass;
        if (!empty($chunk)) {
            $result->chunk = Horde_Util::bufferOutput('include', KRONOLITH_TEMPLATES . '/chunks/' . $chunk . '.php');
        }

        return $result;
    }


    /**
     * TODO
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
     * TODO
     */
    protected function _saveEvent($event)
    {
        try {
            $result = $event->save();
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
            return true;
        }
        $start = new Horde_Date(Horde_Util::getFormData('view_start'));
        $end   = new Horde_Date(Horde_Util::getFormData('view_end'));
        $end->hour = 23;
        $end->min = $end->sec = 59;
        Kronolith::addEvents($events, $event, $start, $end, true, true);
        $result = new stdClass;
        $result->cal = $event->calendarType . '|' . $event->calendar;
        $result->view = Horde_Util::getFormData('view');
        $result->sig = $start->dateString() . $end->dateString();
        if (count($events)) {
            $result->events = $events;
        }
        return $result;
    }

}
