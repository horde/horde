<?php
/**
 * Copyright 2004-2007 Code Fusion  <http://www.codefusion.co.za/>
 * Copyright 2004-2007 Stuart Binge <s.binge@codefusion.co.za>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/base.php';
require_once 'Horde/Identity.php';

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
$allResources = Kronolith::listResources();

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

    if (!empty($newAttendees)) {
        $parser = new Mail_RFC822;
        foreach (Horde_Mime_Address::explode($newAttendees) as $newAttendee) {
            // Parse the address without validation to see what we can get out of
            // it. We allow email addresses (john@example.com), email address with
            // user information (John Doe <john@example.com>), and plain names
            // (John Doe).
            $newAttendeeParsed = $parser->parseAddressList($newAttendee, '', false,
                                                           false);

            // If we can't even get a mailbox out of the address, then it is
            // likely unuseable. Reject it entirely.
            if (is_a($newAttendeeParsed, 'PEAR_Error') ||
                !isset($newAttendeeParsed[0]) ||
                !isset($newAttendeeParsed[0]->mailbox)) {
                $notification->push(
                    sprintf(_("Unable to recognize \"%s\" as an email address."),
                            $newAttendee),
                    'horde.error');
                continue;
            }

            // Loop through any addresses we found.
            foreach ($newAttendeeParsed as $newAttendeeParsedPart) {
                // If there is only a mailbox part, then it is just a local name.
                if (empty($newAttendeeParsedPart->host)) {
                    $attendees[] = array(
                        'attendance' => Kronolith::PART_REQUIRED,
                        'response'   => Kronolith::RESPONSE_NONE,
                        'name'       => $newAttendee,
                    );
                    continue;
                }

                // Build a full email address again and validate it.
                $name = empty($newAttendeeParsedPart->personal)
                    ? ''
                    : $newAttendeeParsedPart->personal;

                try {
                    $newAttendeeParsedPartNew = Horde_Mime::encodeAddress(Horde_Mime_Address::writeAddress($newAttendeeParsedPart->mailbox, $newAttendeeParsedPart->host, $name));
                    $newAttendeeParsedPartValidated = $parser->parseAddressList($newAttendeeParsedPartNew, '', null, true);

                    $email = $newAttendeeParsedPart->mailbox . '@'
                        . $newAttendeeParsedPart->host;
                    // Avoid overwriting existing attendees with the default
                    // values.
                    if (!isset($attendees[$email]))
                        $attendees[$email] = array(
                            'attendance' => Kronolith::PART_REQUIRED,
                            'response'   => Kronolith::RESPONSE_NONE,
                            'name'       => $name,
                        );
                } catch (Horde_Mime_Exception $e) {
                    $notification->push($e, 'horde.error');
                }
            }
        }

        $_SESSION['kronolith']['attendees'] = $attendees;
    }

    // Any new resources?
    if (!empty($newResource)) {
        $resource = Kronolith::getDriver('Resource')->getResource($newResource);

        $resources[$newResource] = array(
            'attendance' => Kronolith::PART_IGNORE,
            'response'   => Kronolith::RESPONSE_NONE,
            'name'       => $resource->name,
        );

        $_SESSION['kronolith']['resources'] = $resources;
    }

    if (Horde_Util::getFormData('addNewClose')) {
        Horde_Util::closeWindowJS();
        exit;
    }
    break;

case 'edit':
    // Edit the specified attendee.
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

case 'changeatt':
    // Change the attendance status of an attendee
    list($partval, $partname) = explode(' ', $actionValue, 2);
    if (isset($attendees[$partname])) {
        $attendees[$partname]['attendance'] = $partval;
        $_SESSION['kronolith']['attendees'] = $attendees;
    }
    break;

case 'changeresp':
    // Change the response status of an attendee
    list($partval, $partname) = explode(' ', $actionValue, 2);
    if (isset($attendees[$partname])) {
        $attendees[$partname]['response'] = $partval;
        $_SESSION['kronolith']['attendees'] = $attendees;
    }
    break;

case 'dismiss':
    // Close the attendee window.
    if ($browser->hasFeature('javascript')) {
        Horde_Util::closeWindowJS();
        exit;
    }

    $url = Horde_Util::getFormData('url');
    if (!empty($url)) {
        $location = Horde::applicationUrl($url, true);
    } else {
        $date = new Horde_Date(Horde_Util::getFormData('date'));
        $url = Horde_Util::addParameter($prefs->getValue('defaultview') . '.php', 'date',  $date->dateString());
        $location = Horde::applicationUrl($url, true);
    }

    // Make sure URL is unique.
    $location = Horde_Util::addParameter($location, 'unique', hash('md5', microtime()));
    header('Location: ' . $location);
    exit;

case 'clear':
    // Remove all the attendees.
    $attendees = array();
    $_SESSION['kronolith']['attendees'] = $attendees;
    break;
}

// Get the current Free/Busy view; default to the 'day' view if none specified.
$view = Horde_Util::getFormData('view', 'Day');

// Pre-format our delete image/link.
$delimg = Horde::img('delete.png', _("Remove Attendee"), null,
                     $registry->getImageDir('horde'));

$ident = &Identity::singleton();
$identities = $ident->getAll('id');
$vars = Horde_Variables::getDefaultVariables();
$tabs = new Horde_UI_Tabs(null, $vars);
$tabs->addTab(_("Day"), 'javascript:switchView(\'Day\')', 'Day');
$tabs->addTab(_("Work Week"), 'javascript:switchView(\'Workweek\')', 'Workweek');
$tabs->addTab(_("Week"), 'javascript:switchView(\'Week\')', 'Week');
$tabs->addTab(_("Month"), 'javascript:switchView(\'Month\')', 'Month');

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
        $cal = Horde_Auth::getAuth();
    }
    $cal = array($cal);
}
$vfb = Kronolith_FreeBusy::generate($cal, null, null, true, Horde_Auth::getAuth());
if (!is_a($vfb, 'PEAR_Error')) {
    $attendee_view->addRequiredMember($vfb);
} else {
    $notification->push(
        sprintf(_("Error retrieving your free/busy information: %s"),
                $vfb->getMessage()));
}

// Add the Free/Busy information for each attendee.
foreach ($attendees as $email => $status) {
    if (strpos($email, '@') !== false &&
        ($status['attendance'] == Kronolith::PART_REQUIRED ||
         $status['attendance'] == Kronolith::PART_OPTIONAL)) {
        $vfb = Kronolith_Freebusy::get($email);
        if (!is_a($vfb, 'PEAR_Error')) {
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
        } else {
            $notification->push(
                sprintf(_("Error retrieving free/busy information for %s: %s"),
                        $email, $vfb->getMessage()));
        }
    }
}

// Add Free/Busy for resources
if (count($resources)) {
    $driver = Kronolith::getDriver('Resource');
    foreach ($resources as $r_id => $resource) {
        $r = $driver->getResource($r_id);
        $vfb = $r->getFreeBusy(null, null, true);
        $attendee_view->addResourceMember($vfb);
    }
}
$date = sprintf("%02d%02d%02d000000", Horde_Util::getFormData('year'), Horde_Util::getFormData('month'), Horde_Util::getFormData('mday'));
$date = new Horde_Date($date);
$vfb_html = $attendee_view->render($date);

// Add the ContactAutoCompleter
$cac = Horde_Ajax_Imple::factory(array('kronolith', 'ContactAutoCompleter'), array('triggerId' => 'newAttendees'));
$cac->attach();

$title = _("Edit attendees");
require KRONOLITH_TEMPLATES . '/common-header.inc';
$notification->notify(array('listeners' => 'status'));
require KRONOLITH_TEMPLATES . '/attendees/attendees.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
