<?php
/**
 * Copyright 2004-2007 Code Fusion  <http://www.codefusion.co.za/>
 * Copyright 2004-2007 Stuart Binge <s.binge@codefusion.co.za>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

if (Kronolith::showAjaxView()) {
    header('Location: ' . Horde::applicationUrl('', true));
    exit;
}

// Get the current attendees array from the session cache.
$attendees = (isset($_SESSION['kronolith']['attendees']) &&
              is_array($_SESSION['kronolith']['attendees']))
    ? $_SESSION['kronolith']['attendees']
    : array();
$editAttendee = null;

$resources = (isset($_SESSION['kronolith']['resources']) &&
              is_array($_SESSION['kronolith']['resources']))
    ? $_SESSION['kronolith']['resources']
    : array();

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
    $newAttendees = trim(Horde_Util::getFormData('newAttendees'));
    $newResource = trim(Horde_Util::getFormData('resourceselect'));

    $newAttendees = Kronolith::parseAttendees($newAttendees);
    if ($newAttendees) {
        $_SESSION['kronolith']['attendees'] = $attendees + $newAttendees;
    }

    // Any new resources?
    if (!empty($newResource)) {
        /* Get the requested resource */
        $resource = Kronolith::getDriver('Resource')->getResource($newResource);

        /* Do our best to see what the response will be. Note that this response
         * is only guarenteed once the event is saved. */
        $date = new Horde_Date(Horde_Util::getFormData('date'));
        $end = new Horde_Date(Horde_Util::getFormData('enddate'));
        $response = $resource->getResponse(array('start' => $date, 'end' => $end));
        $resources[$resource->getId()] = array(
            'attendance' => Kronolith::PART_REQUIRED,
            'response'   => $response,
            'name'       => $resource->get('name'),
        );

        $_SESSION['kronolith']['resources'] = $resources;
    }

    if (Horde_Util::getFormData('addNewClose')) {
        echo Horde::wrapInlineScript(array('window.close();'));
        exit;
    }
    break;

case 'edit':
    // Edit the specified attendee.
    $actionValue = Horde_String::lower($actionValue);
    if (isset($attendees[$actionValue])) {
        if (empty($attendees[$actionValue]['name'])) {
            $editAttendee = $actionValue;
        } else {
            $editAttendee = Horde_Mime_Address::trimAddress(
                $attendees[$actionValue]['name']
                . (strpos($actionValue, '@') === false
                   ? ''
                   : ' <' . $actionValue . '>'));
        }
        unset($attendees[$actionValue]);
        $_SESSION['kronolith']['attendees'] = $attendees;
    }
    break;

case 'remove':
    // Remove the specified attendee.
    $actionValue = Horde_String::lower($actionValue);
    if (isset($attendees[$actionValue])) {
        unset($attendees[$actionValue]);
        $_SESSION['kronolith']['attendees'] = $attendees;
    }
    break;

case 'removeResource':
    // Remove the specified resource
    if (isset($resources[$actionValue])) {
        unset($resources[$actionValue]);
        $_SESSION['kronolith']['resources'] = $resources;
    }
    break;

case 'changeResourceResp':
    //@TODO: What else to do here? Dissallow if responsetype is auto?
    list($partval, $partname) = explode(' ', $actionValue, 2);
    if (isset($resources[$partname])) {
        $resources[$partname]['response'] = $partval;
        $_SESSION['kronolith']['resources'] = $resources;
    }
    break;

case 'changeatt':
    // Change the attendance status of an attendee
    list($partval, $partname) = explode(' ', $actionValue, 2);
    $partname = Horde_String::lower($partname);
    if (isset($attendees[$partname])) {
        $attendees[$partname]['attendance'] = $partval;
        $_SESSION['kronolith']['attendees'] = $attendees;
    }
    break;

case 'changeResourceAtt':
    // Change attendance status of a resource
    list($partval, $partname) = explode(' ', $actionValue, 2);
    $partname = Horde_String::lower($partname);
    if (isset($resources[$partname])) {
        $resources[$partname]['attendance'] = $partval;
        $_SESSION['kronolith']['resources'] = $resources;
    }
    break;

case 'changeresp':
    // Change the response status of an attendee
    list($partval, $partname) = explode(' ', $actionValue, 2);
    $partname = Horde_String::lower($partname);
    if (isset($attendees[$partname])) {
        $attendees[$partname]['response'] = $partval;
        $_SESSION['kronolith']['attendees'] = $attendees;
    }
    break;

case 'dismiss':
    // Close the attendee window.
    if ($browser->hasFeature('javascript')) {
        echo Horde::wrapInlineScript(array('window.close();'));
        exit;
    }

    $url = Horde_Util::getFormData('url');
    if (!empty($url)) {
        $url = new Horde_Url($url, true);
    } else {
        $date = new Horde_Date(Horde_Util::getFormData('date'));
        $url = Horde::applicationUrl($prefs->getValue('defaultview') . '.php', true)
            ->add('date', $date->dateString());
    }

    // Make sure URL is unique.
    header('Location: ' . $url->unique());
    exit;

case 'clear':
    // Remove all the attendees and resources.
    $_SESSION['kronolith']['attendees'] = $_SESSION['kronolith']['resources'] = array();
    break;
}

/* Get list of resources for select list, and remove those we already added */
$allResources = Kronolith::getDriver('Resource')->listResources();
foreach (array_keys($resources) as $id) {
    unset($allResources[$id]);
}

// Get the current Free/Busy view; default to the 'day' view if none specified.
$view = Horde_Util::getFormData('view', 'Day');

// Pre-format our delete image/link.
$delimg = Horde::img('delete.png', _("Remove Attendee"));

$ident = $injector->getInstance('Horde_Prefs_Identity')->getIdentity();
$identities = $ident->getAll('id');
$vars = Horde_Variables::getDefaultVariables();
$tabs = new Horde_Core_Ui_Tabs(null, $vars);
$tabs->addTab(_("Day"), new Horde_Url('javascript:switchView(\'Day\')'), 'Day');
$tabs->addTab(_("Work Week"), new Horde_Url('javascript:switchView(\'Workweek\')'), 'Workweek');
$tabs->addTab(_("Week"), new Horde_Url('javascript:switchView(\'Week\')'), 'Week');
$tabs->addTab(_("Month"), new Horde_Url('javascript:switchView(\'Month\')'), 'Month');

$attendee_view = &Kronolith_FreeBusy_View::singleton($view);

// Add the creator as a required attendee in the Free/Busy display
$cal = @unserialize($prefs->getValue('fb_cals'));
if (!is_array($cal)) {
    $cal = null;
}

// If the free/busy calendars preference is empty, default to the user's
// default_share preference, and if that's empty, to their username.
if (!$cal) {
    $cal = $prefs->getValue('default_share');
    if (!$cal) {
        $cal = $GLOBALS['registry']->getAuth();
    }
    $cal = array($cal);
}
try {
    $vfb = Kronolith_FreeBusy::generate($cal, null, null, true, $GLOBALS['registry']->getAuth());
    $attendee_view->addRequiredMember($vfb);
} catch (Exception $e) {
    $notification->push(sprintf(_("Error retrieving your free/busy information: %s"), $e->getMessage()));
}

// Add the Free/Busy information for each attendee.
foreach ($_SESSION['kronolith']['attendees'] as $email => $status) {
    if (strpos($email, '@') !== false &&
        ($status['attendance'] == Kronolith::PART_REQUIRED ||
         $status['attendance'] == Kronolith::PART_OPTIONAL)) {
        try {
            $vfb = Kronolith_Freebusy::get($email);
            $organizer = $vfb->getAttribute('ORGANIZER');
            if (empty($organizer)) {
                $vfb->setAttribute('ORGANIZER', 'mailto:' . $email, array(),
                                   false);
            }
            if ($status['attendance'] == Kronolith::PART_REQUIRED) {
                $attendee_view->addRequiredMember($vfb);
            } else {
                $attendee_view->addOptionalMember($vfb);
            }
        } catch (Exception $e) {
            $notification->push(
                sprintf(_("Error retrieving free/busy information for %s: %s"),
                        $email, $e->getMessage()));
        }
    }
}

// Add Free/Busy for resources
if (count($resources)) {
    $driver = Kronolith::getDriver('Resource');
    foreach ($resources as $r_id => $resource) {
        $r = $driver->getResource($r_id);
        try {
            $vfb = $r->getFreeBusy(null, null, true);
            if ($resource['attendance'] == Kronolith::PART_REQUIRED) {
                $attendee_view->addRequiredResourceMember($vfb);
            } else {
                $attendee_view->addOptionalResourceMember($vfb);
            }
        } catch (Horde_Exception $e) {
            $notification->push(
                sprintf(_("Error retrieving free/busy information for %s: %s"),
                    $r_id, $e->getMessage()));
        }
    }
}

$date = new Horde_Date(Horde_Util::getFormData('date', date('Ymd') . '000000'));
$end =  new Horde_Date(Horde_Util::getFormData('enddate', date('Ymd') . '000000'));

$vfb_html = $attendee_view->render($date);

// Add the ContactAutoCompleter
$injector->getInstance('Horde_Ajax_Imple')->getImple(array('kronolith', 'ContactAutoCompleter'), array(
    'triggerId' => 'newAttendees'
));

$title = _("Edit attendees");
require KRONOLITH_TEMPLATES . '/common-header.inc';
$notification->notify(array('listeners' => 'status'));
require KRONOLITH_TEMPLATES . '/attendees/attendees.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
