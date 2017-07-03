<?php
/**
 * Copyright 2004-2007 Code Fusion  <http://www.codefusion.co.za/>
 * Copyright 2004-2007 Stuart Binge <s.binge@codefusion.co.za>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->redirect();
}

// Get the current attendees array from the session cache.
$attendees = $session->get('kronolith', 'attendees');
if (!$attendees) {
    $attendees = new Kronolith_Attendee_List();
}
$resources = $session->get('kronolith', 'resources', Horde_Session::TYPE_ARRAY);
$editAttendee = null;

// Get the current Free/Busy view; default to the 'day' view if none specified.
$view = Horde_Util::getFormData('view', 'Day');

// Get the date information.
$start = new Horde_Date(
    Horde_Util::getFormData('startdate'), date_default_timezone_get()
);
switch ($view) {
case 'Day':
    $end = clone $start;
    $end->mday++;
    break;
case 'Workweek':
case 'Week':
    $diff = $start->dayOfWeek()
        - ($view == 'Workweek' ? 1 : $prefs->getValue('week_start_monday'));
    if ($diff < 0) {
        $diff += 7;
    }
    $start->mday -= $diff;
    $end = clone $start;
    $end->mday += $view == 'Workweek' ? 5 : 7;
    break;
case 'Month':
    $start->mday = 1;
    $end = clone $start;
    $end->month++;
    break;
}

// Get the action ID and value. This specifies what action the user initiated.
$actionID = Horde_Util::getFormData('actionID');
if (Horde_Util::getFormData('clearAll')) {
    $actionID =  'clear';
}
$actionValue = Horde_Util::getFormData('actionValue');
// Perform the specified action, if there is one.
switch ($actionID) {
case 'add':
    // Add new attendees and/or resources. Multiple attendees can be seperated
    // on a single line by whitespace and/or commas. Resources are added one
    // at a time (at least for now).
    $newUser = trim(Horde_Util::getFormData('newUser'));
    $newAttendees = trim(Horde_Util::getFormData('newAttendees'));
    $newResource = trim(Horde_Util::getFormData('resourceselect'));

    if (!is_null($newUser)) {
        if (!$user = Kronolith::validateUserAttendee($newUser)) {
            $notification->push(sprintf(_("The user \"%s\" does not exist."), $newUser), 'horde.error');
        } else {
            $attendees->add($user);
        }
    }
    $newAttendees = Kronolith_Attendee_List::parse($newAttendees, $notification);
    $session->set('kronolith', 'attendees', $attendees->add($newAttendees));

    // Any new resources?
    if (!empty($newResource)) {
        /* Get the requested resource */
        $resource = Kronolith::getDriver('Resource')->getResource($newResource);

        /* Do our best to see what the response will be. Note that this
         * response is only guarenteed once the event is saved. */
        $event = Kronolith::getDriver()->getEvent();
        $event->start = $start;
        $event->end = $end;
        $event->start->setTimezone(date_default_timezone_get());
        $event->end->setTimezone(date_default_timezone_get());
        $response = $resource->getResponse($event);
        $resources[$resource->getId()] = array(
            'attendance' => Kronolith::PART_REQUIRED,
            'response'   => $response,
            'name'       => $resource->get('name'),
        );

        $session->set('kronolith', 'resources', $resources);
    }

    if (Horde_Util::getFormData('addNewClose')) {
        echo Horde::wrapInlineScript(array('window.close();'));
        exit;
    }
    break;

case 'edit':
    // Edit the specified attendee.
    if (isset($attendees[$actionValue])) {
        $editAttendee = strval($attendees[$actionValue]);
        unset($attendees[$actionValue]);
        $session->set('kronolith', 'attendees', $attendees);
    }
    break;

case 'remove':
    // Remove the specified attendee.
    if (isset($attendees[$actionValue])) {
        unset($attendees[$actionValue]);
        $session->set('kronolith', 'attendees', $attendees);
    }
    break;

case 'removeResource':
    // Remove the specified resource
    if (isset($resources[$actionValue])) {
        unset($resources[$actionValue]);
        $session->set('kronolith', 'resources', $resources);
    }
    break;

case 'changeResourceResp':
    //@TODO: What else to do here? Disallow if responsetype is auto?
    list($partval, $partname) = explode(' ', $actionValue, 2);
    if (isset($resources[$partname])) {
        $resources[$partname]['response'] = $partval;
        $session->set('kronolith', 'resources', $resources);
    }
    break;

case 'changeatt':
    // Change the attendance status of an attendee
    list($partval, $partname) = explode(' ', $actionValue, 2);
    if (isset($attendees[$partname])) {
        $attendees[$partname]->role = $partval;
        $session->set('kronolith', 'attendees', $attendees);
    }
    break;

case 'changeResourceAtt':
    // Change attendance status of a resource
    list($partval, $partname) = explode(' ', $actionValue, 2);
    if (isset($resources[$partname])) {
        $resources[$partname]['attendance'] = $partval;
        $session->set('kronolith', 'resources', $resources);
    }
    break;

case 'changeresp':
    // Change the response status of an attendee
    list($partval, $partname) = explode(' ', $actionValue, 2);
    if (isset($attendees[$partname])) {
        $attendees[$partname]->response = $partval;
        $session->set('kronolith', 'attendees', $attendees);
    }
    break;

case 'dismiss':
    // Close the attendee window.
    if ($browser->hasFeature('javascript')) {
        echo Horde::wrapInlineScript(array('window.close();'));
        exit;
    }

    if ($url = Horde::verifySignedUrl(Horde_Util::getFormData('url'))) {
        $url = new Horde_Url($url, true);
    } else {
        $url = Horde::url($prefs->getValue('defaultview') . '.php', true)
            ->add('date', $start->dateString());
    }

    // Make sure URL is unique.
    $url->unique()->redirect();

case 'clear':
    // Remove all the attendees and resources.
    $session->remove('kronolith', 'attendees');
    $session->remove('kronolith', 'resources');
    break;
}

// Pre-format our delete image/link.
$delimg = Horde::img('delete.png', _("Remove Attendee"));

$ident = $injector->getInstance('Horde_Core_Factory_Identity')->create();
$identities = $ident->getAll('id');

$fbView = Kronolith_FreeBusy_View::singleton($view);
$fbOpts = array('start' => $start->datestamp(), 'end' => $end->datestamp());

try {
    $vfb = Kronolith_FreeBusy::getForUser(
        $GLOBALS['registry']->getAuth(), $fbOpts
    );
    $fbView->addRequiredMember($vfb);
} catch (Exception $e) {
    $notification->push(sprintf(_("Error retrieving your free/busy information: %s"), $e->getMessage()));
}

// Add the Free/Busy information for each attendee.
foreach ($session->get('kronolith', 'attendees') as $attendee) {
    if ($attendee->role != Kronolith::PART_REQUIRED &&
        $attendee->role != Kronolith::PART_OPTIONAL) {
        continue;
    }
    try {
        if ($attendee->user) {
            $vfb = Kronolith_Freebusy::getForUser($attendee->user, $fbOpts);
        } elseif (is_null($attendee->addressObject->host)) {
            $vfb = new Horde_Icalendar_Vfreebusy();
        } else {
            $vfb = Kronolith_FreeBusy::get($attendee->email);
        }
    } catch (Exception $e) {
        $notification->push(
            sprintf(
                _("Error retrieving free/busy information for %s: %s"),
                $attendee,
                $e->getMessage()
            )
        );
        $vfb = new Horde_Icalendar_Vfreebusy();
    }
    try {
        $organizer = $vfb->getAttribute('ORGANIZER');
    } catch (Horde_Icalendar_Exception $e) {
        $organizer = null;
    }
    if (empty($organizer)) {
        if (strlen($attendee->name)) {
            $name = array('CN' => $attendee->name);
        } else {
            $name = array();
        }
        $vfb->setAttribute(
            'ORGANIZER', 'mailto:' . $attendee->email, $name, false
        );
    }
    if ($attendee->role == Kronolith::PART_REQUIRED) {
        $fbView->addRequiredMember($vfb);
    } else {
        $fbView->addOptionalMember($vfb);
    }
}

// Add Free/Busy for resources
if (count($resources)) {
    $driver = Kronolith::getDriver('Resource');
    foreach ($resources as $r_id => $resource) {
        try {
            $r = $driver->getResource($r_id);
        } catch (Horde_Exception_NotFound $e) {
            continue;
        }
        try {
            $vfb = $r->getFreeBusy(null, null, true);
            if ($resource['attendance'] == Kronolith::PART_REQUIRED) {
                $fbView->addRequiredResourceMember($vfb);
            } else {
                $fbView->addOptionalResourceMember($vfb);
            }
        } catch (Horde_Exception $e) {
            $notification->push(
                sprintf(_("Error retrieving free/busy information for %s: %s"),
                    $r_id, $e->getMessage()));
        }
    }
}

$title = _("Edit attendees");

$attendeesView = new Kronolith_View_Attendees(array(
    'fbView' => $fbView,
    'start' => $start,
    'end' => $end,
));
$attendeesView->assign(compact('editAttendee', 'title'));

$injector->getInstance('Horde_Core_Factory_Imple')->create('Kronolith_Ajax_Imple_ContactAutoCompleter', array(
    'id' => 'newAttendees'
));

$page_output->sidebar = $page_output->topbar = false;
$page_output->header(array(
    'title' => $title
));
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
$notification->notify(array('listeners' => 'status'));
echo $attendeesView->render('attendees');
$page_output->footer();
