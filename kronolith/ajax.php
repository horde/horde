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
 * @package Kronolith
 */

function getDriver($cal)
{
    list($driver, $calendar) = explode('|', $cal);
    switch ($driver) {
    case 'internal':
        if (!array_key_exists($calendar,
                              Kronolith::listCalendars(false, PERMS_READ))) {
            $GLOBALS['notification']->push(_("Permission Denied"), 'horde.error');
            return false;
        }
        $driver = '';
        break;
    case 'external':
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

// Need to load Util:: to give us access to Util::getPathInfo().
$kronolith_dir = dirname(__FILE__);
if (!defined('HORDE_BASE')) {
    /* Temporary fix - if horde does not live directly under the kronolith
     * directory, the HORDE_BASE constant should be defined in
     * kronolith/lib/base.local.php. */
    if (file_exists($kronolith_dir . '/lib/base.local.php')) {
        include $kronolith_dir . '/lib/base.local.php';
    } else {
        define('HORDE_BASE', $kronolith_dir . '/..');
    }
}
require_once HORDE_BASE . '/lib/core.php';
$action = basename(Util::getPathInfo());
if (empty($action)) {
    // This is the only case where we really don't return anything, since
    // the frontend can be presumed not to make this request on purpose.
    // Other missing data cases we return a response of boolean false.
    exit;
}

// The following actions do not need write access to the session and
// should be opened read-only for performance reasons.
if (in_array($action, array())) {
    $session_control = 'readonly';
}

$session_timeout = 'json';
require_once $kronolith_dir . '/lib/base.php';

// Process common request variables.
$cacheid = Util::getPost('cacheid');

// Open an output buffer to ensure that we catch errors that might break JSON
// encoding.
ob_start();

try {
    $notify = true;
    $result = false;
    
    switch ($action) {
    case 'ListEvents':
        $start = new Horde_Date(Util::getFormData('start'));
        $end   = new Horde_Date(Util::getFormData('end'));
        if (!($kronolith_driver = getDriver($cal = Util::getFormData('cal')))) {
            $result = true;
            break;
        }
        $events = $kronolith_driver->listEvents($start, $end, true, false, true);
        if (is_a($events, 'PEAR_Error')) {
            $notification->push($events, 'horde.error');
            $result = true;
            break;
        }
        $result = new stdClass;
        $result->cal = $cal;
        $result->view = Util::getFormData('view');
        $result->sig = $start->dateString() . $end->dateString();
        if (count($events)) {
            $result->events = $events;
        }
        break;
    
    case 'GetEvent':
        if (!($kronolith_driver = getDriver($cal = Util::getFormData('cal')))) {
            $result = true;
            break;
        }
        if (is_null($id = Util::getFormData('id'))) {
            $result = true;
            break;
        }
        $event = $kronolith_driver->getEvent($id);
        if (is_a($event, 'PEAR_Error')) {
            $notification->push($event, 'horde.error');
            $result = true;
            break;
        }
        if (!$event) {
            $notification->push(_("The requested event was not found."), 'horde.error');
            $result = true;
            break;
        }
        $result = new stdClass;
        $result->event = $event->toJSON(true, $prefs->getValue('twentyFour') ? 'H:i' : 'h:i A');
        break;
    
    case 'SaveEvent':
        $cal = Util::getFormData('cal');
        if (!($kronolith_driver = getDriver($cal))) {
            $result = true;
            break;
        }
        $event = $kronolith_driver->getEvent(Util::getFormData('id'));
        if (is_a($event, 'PEAR_Error')) {
            $notification->push($event, 'horde.error');
            $result = true;
            break;
        }
        if (!$event) {
            $notification->push(_("The requested event was not found."), 'horde.error');
            $result = true;
            break;
        }
        if (!$event->hasPermission(PERMS_EDIT)) {
            $notification->push(_("You do not have permission to edit this event."), 'horde.warning');
            $result = true;
            break;
        }
        $event->readForm();
        $result = $event->save();
        if (is_a($result, 'PEAR_Error')) {
            $notification->push($result, 'horde.error');
        }
        $start = new Horde_Date(Util::getFormData('view_start'));
        $end   = new Horde_Date(Util::getFormData('view_end'));
        Kronolith::addEvents($events, $event, $start, $end, true, true);
        $result = new stdClass;
        $result->cal = $cal;
        $result->view = Util::getFormData('view');
        $result->sig = $start->dateString() . $end->dateString();
        if (count($events)) {
            $result->events = $events;
        }
        break;
    
    case 'UpdateEvent':
        if (!($kronolith_driver = getDriver($cal = Util::getFormData('cal')))) {
            break;
        }
        if (is_null($id = Util::getFormData('id'))) {
            $result = true;
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
            $result = true;
            break;
        }
        $attributes = Horde_Serialize::unserialize(Util::getFormData('att'), Horde_Serialize::JSON);
        foreach ($attributes as $attribute => $value) {
            switch ($attribute) {
            case 'start_date':
                $start = new Horde_Date($value);
                $event->start->year = $start->year;
                $event->start->month = $start->month;
                $event->start->mday = $start->mday;
                $event->end = $event->start->add(array('min' => $event->durMin));
                break;
            }
        }
        $result = $event->save();
        if (is_a($result, 'PEAR_Error')) {
            $notification->push($result, 'horde.error');
        }
        break;
    
    case 'DeleteEvent':
        if (!($kronolith_driver = getDriver($cal = Util::getFormData('cal')))) {
            $result = true;
            break;
        }
        if (is_null($id = Util::getFormData('id'))) {
            $result = true;
            break;
        }
        $event = $kronolith_driver->getEvent($id);
        if (is_a($event, 'PEAR_Error')) {
            $notification->push($event, 'horde.error');
            $result = true;
            break;
        }
        if (!$event) {
            $notification->push(_("The requested event was not found."), 'horde.error');
            $result = true;
            break;
        }
        if (!$event->hasPermission(PERMS_DELETE)) {
            $notification->push(_("You do not have permission to delete this event."), 'horde.warning');
            $result = true;
            break;
        }
        $deleted = $kronolith_driver->deleteEvent($event->getId());
        if (is_a($deleted, 'PEAR_Error')) {
            $notification->push($deleted, 'horde.error');
            $result = true;
            break;
        }
        $result = new stdClass;
        $result->deleted = true;
        break;
    
    case 'SaveCalPref':
        $result = true;
        break;
    
    case 'ChunkContent':
        $chunk = basename(Util::getPost('chunk'));
        if (!empty($chunk)) {
            $result = new stdClass;
            $result->chunk = Util::bufferOutput('include', KRONOLITH_TEMPLATES . '/chunks/' . $chunk . '.php');
        }
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
Horde::sendHTTPResponse(Horde::prepareResponse($result, $notify ? $GLOBALS['kronolith_notify'] : null), 'json');
