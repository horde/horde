<?php
/**
 * Performs the AJAX-requested action.
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Gonçalo Queirós <mail@goncaloqueiros.net>
 * @package Kronolith
 */

function getDriver($cal)
{
    list($driver, $calendar) = explode('|', $cal);
    switch ($driver) {
    case 'internal':
        if (!array_key_exists($calendar,
                              Kronolith::listCalendars(false, PERMS_SHOW))) {
            $GLOBALS['notification']->push(_("Permission Denied"), 'horde.error');
            return false;
        }
        $driver = '';
        break;
    case 'external':
    case 'tasklists':
        $driver = 'Horde';
        break;
    case 'remote':
        $driver = 'Ical';
        break;
    case 'holiday':
        $driver = 'Holidays';
        break;
    default:
        $GLOBALS['notification']->push('No calendar driver specified', 'horde.error');
        break;
    }

    $kronolith_driver = Kronolith::getDriver($driver, $calendar);

    switch ($driver) {
    case 'Ical':
        $kronolith_driver->setParam('timeout', 15);
        break;
    }

    return $kronolith_driver;
}

function saveEvent($event)
{
    $result = $event->save();
    if (is_a($result, 'PEAR_Error')) {
        $GLOBALS['notification']->push($result, 'horde.error');
        return true;
    }
    $start = new Horde_Date(Horde_Util::getFormData('view_start'));
    $end   = new Horde_Date(Horde_Util::getFormData('view_end'));
    $end->hour = 23;
    $end->min = $end->sec = 59;
    Kronolith::addEvents($events, $event, $start, $end, true, true);
    $result = new stdClass;
    $result->cal = $event->getCalendarType() . '|' . $event->getCalendar();
    $result->view = Horde_Util::getFormData('view');
    $result->sig = $start->dateString() . $end->dateString();
    if (count($events)) {
        $result->events = $events;
    }
    return $result;
}

// Need to load Horde_Util:: to give us access to Horde_Util::getPathInfo().
require_once dirname(__FILE__) . '/lib/base.load.php';
require_once HORDE_BASE . '/lib/core.php';
$action = basename(Horde_Util::getPathInfo());
if (empty($action)) {
    // This is the only case where we really don't return anything, since
    // the frontend can be presumed not to make this request on purpose.
    // Other missing data cases we return a response of boolean false.
    exit;
}

// The following actions do not need write access to the session and
// should be opened read-only for performance reasons.
if (in_array($action, array())) {
    $kronolith_session_control = 'readonly';
}

$kronolith_session_timeout = 'json';
require_once KRONOLITH_BASE . '/lib/base.php';

// Process common request variables.
$cacheid = Horde_Util::getPost('cacheid');

// Open an output buffer to ensure that we catch errors that might break JSON
// encoding.
ob_start();

try {
    $result = true;

    switch ($action) {
    case 'ListEvents':
        $start = new Horde_Date(Horde_Util::getFormData('start'));
        $end   = new Horde_Date(Horde_Util::getFormData('end'));
        $cal   = Horde_Util::getFormData('cal');
        $result = new stdClass;
        $result->cal = $cal;
        $result->view = Horde_Util::getFormData('view');
        $result->sig = $start->dateString() . $end->dateString();
        if (!($kronolith_driver = getDriver($cal))) {
            break;
        }
        $events = $kronolith_driver->listEvents($start, $end, true, false, true);
        if (is_a($events, 'PEAR_Error')) {
            $notification->push($events, 'horde.error');
            break;
        }
        if (count($events)) {
            $result->events = $events;
        }
        break;

    case 'ListTasks':
        if (!$registry->hasMethod('tasks/listTasks')) {
            break;
        }

        $taskList = Horde_Util::getFormData('list');
        $taskType = Horde_Util::getFormData('taskType');
        $tasks = $registry->call('tasks/listTasks',
                                 array(null, null, null, $taskList, $taskType, true));
        if (is_a($tasks, 'PEAR_Error')) {
            $notification->push($tasks, 'horde.error');
            break;
        }

        $result = new stdClass;
        $result->taskList = $taskList;
        $result->taskType = $taskType;
        if (count($tasks)) {
            $result->tasks = $tasks;
        }
        break;

    case 'GetEvent':
        if (!($kronolith_driver = getDriver(Horde_Util::getFormData('cal')))) {
            break;
        }
        if (is_null($id = Horde_Util::getFormData('id'))) {
            break;
        }
        $event = $kronolith_driver->getEvent($id);
        if (is_a($event, 'PEAR_Error')) {
            $notification->push($event, 'horde.error');
            break;
        }
        if (!$event) {
            $notification->push(_("The requested event was not found."), 'horde.error');
            break;
        }
        $result = new stdClass;
        $result->event = $event->toJson(null, true, $prefs->getValue('twentyFour') ? 'H:i' : 'h:i A');
        break;

    case 'SaveEvent':
        if (!($kronolith_driver = getDriver(Horde_Util::getFormData('cal')))) {
            break;
        }
        $event = $kronolith_driver->getEvent(Horde_Util::getFormData('id'));
        if (is_a($event, 'PEAR_Error')) {
            $notification->push($event, 'horde.error');
            break;
        }
        if (!$event) {
            $notification->push(_("The requested event was not found."), 'horde.error');
            break;
        }
        if (!$event->hasPermission(PERMS_EDIT)) {
            $notification->push(_("You do not have permission to edit this event."), 'horde.warning');
            break;
        }
        $event->readForm();
        $result = saveEvent($event);
        break;

    case 'QuickSaveEvent':
        $kronolith_driver = Kronolith::getDriver();
        try {
            $event = Kronolith::quickAdd(Horde_Util::getFormData('text'),
                                         Kronolith::getDefaultCalendar(PERMS_EDIT));
            if (is_a($event, 'PEAR_Error')) {
                $notification->push($event, 'horde.error');
                break;
            }
            $result = saveEvent($event);
        } catch (Horde_Exception $e) {
            $notification->push($e);
        }
        break;

    case 'UpdateEvent':
        if (!($kronolith_driver = getDriver(Horde_Util::getFormData('cal')))) {
            break;
        }
        if (is_null($id = Horde_Util::getFormData('id'))) {
            break;
        }
        $event = $kronolith_driver->getEvent($id);
        if (is_a($event, 'PEAR_Error')) {
            $notification->push($event, 'horde.error');
            break;
        }
        if (!$event) {
            $notification->push(_("The requested event was not found."), 'horde.error');
            break;
        }
        if (!$event->hasPermission(PERMS_EDIT)) {
            $notification->push(_("You do not have permission to edit this event."), 'horde.warning');
            break;
        }
        $attributes = Horde_Serialize::unserialize(Horde_Util::getFormData('att'), Horde_Serialize::JSON);
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
        $result = saveEvent($event);
        break;

    case 'DeleteEvent':
        if (!($kronolith_driver = getDriver(Horde_Util::getFormData('cal')))) {
            break;
        }
        if (is_null($id = Horde_Util::getFormData('id'))) {
            break;
        }
        $event = $kronolith_driver->getEvent($id);
        if (is_a($event, 'PEAR_Error')) {
            $notification->push($event, 'horde.error');
            break;
        }
        if (!$event) {
            $notification->push(_("The requested event was not found."), 'horde.error');
            break;
        }
        if (!$event->hasPermission(PERMS_DELETE)) {
            $notification->push(_("You do not have permission to delete this event."), 'horde.warning');
            break;
        }
        $deleted = $kronolith_driver->deleteEvent($event->getId());
        if (is_a($deleted, 'PEAR_Error')) {
            $notification->push($deleted, 'horde.error');
            break;
        }
        $result = new stdClass;
        $result->deleted = true;
        break;

    case 'SearchEvents':
        $query = Horde_Serialize::unserialize(Horde_Util::getFormData('query'), Horde_Serialize::JSON);
        if (!isset($query->start)) {
            $query->start = new Horde_Date($_SERVER['REQUEST_TIME']);
        }
        if (!isset($query->end)) {
            $query->end = null;
        }
        $cals   = Horde_Serialize::unserialize(Horde_Util::getFormData('cals'), Horde_Serialize::JSON);
        $events = array();
        foreach ($cals as $cal) {
            if (!($kronolith_driver = getDriver($cal))) {
                break;
            }
            $result = $kronolith_driver->search($query, true);
            if (is_a($result, 'PEAR_Error')) {
                $notification->push($result, 'horde.error');
                break;
            }
            if ($result) {
                $events[$cal] = $result;
            }
        }
        $result = new stdClass;
        $result->view = 'search';
        $result->query = Horde_Util::getFormData('query');
        if ($events) {
            $result->events = $events;
        }
        break;

    case 'SearchCalendars':
        $result = new stdClass;
        $result->events = 'Searched for calendars: ' . Horde_Util::getFormData('title');
        break;

    case 'SaveCalPref':
        break;

    case 'ChunkContent':
        $chunk = basename(Horde_Util::getPost('chunk'));
        if (!empty($chunk)) {
            $result = new stdClass;
            $result->chunk = Horde_Util::bufferOutput('include', KRONOLITH_TEMPLATES . '/chunks/' . $chunk . '.php');
        }
        break;

    case 'ListTopTags':
        $tagger = new Kronolith_Tagger();
        $result = new stdClass;
        $result->tags = array();
        $tags = $tagger->getCloud(Horde_Auth::getAuth(), 10);
        foreach ($tags as $tag) {
            $result->tags[] = $tag['tag_name'];
        }
        break;

    case 'GetTask':
        if (!$registry->hasMethod('tasks/getTask')) {
            break;
        }
        if (is_null($id = Horde_Util::getFormData('id')) ||
            is_null($list = Horde_Util::getFormData('list'))) {
            break;
        }
        $task = $registry->tasks->getTask($list, $id);
        if (is_a($task, 'PEAR_Error')) {
            $notification->push($task, 'horde.error');
            break;
        }
        if (!$task) {
            $notification->push(_("The requested task was not found."), 'horde.error');
            break;
        }
        $result = new stdClass;
        $result->task = $task->toJson(true, $prefs->getValue('twentyFour') ? 'H:i' : 'h:i A');
        break;

    case 'ToggleCompletion':
        if (!$registry->hasMethod('tasks/toggleCompletion')) {
            break;
        }
        $taskList = Horde_Util::getFormData('taskList');
        $taskType = Horde_Util::getFormData('taskType');
        $taskId = Horde_Util::getFormData('taskId');
        $saved = $registry->call('tasks/toggleCompletion',
                                 array($taskId, $taskList));
        if (is_a($saved, 'PEAR_Error')) {
            $notification->push($saved, 'horde.error');
            break;
        }

        $result = new stdClass;
        $result->taskList = $taskList;
        $result->taskType = $taskType;
        $result->taskId = $taskId;
        $result->toggled = true;
        break;

    default:
        $notification->push('Unknown action ' . $action, 'horde.error');
        break;
    }
} catch (Exception $e) {
    $notification->push($e->getMessage(), 'horde.error');
}

// Clear the output buffer that we started above, and log any unexpected
// output at a DEBUG level.
$errors = ob_get_clean();
if ($errors) {
    Horde::logMessage('Kronolith: unexpected output: ' .
                      $errors, __FILE__, __LINE__, PEAR_LOG_DEBUG);
}

// Send the final result.
Horde::sendHTTPResponse(Horde::prepareResponse($result, $GLOBALS['kronolith_notify']), 'json');
