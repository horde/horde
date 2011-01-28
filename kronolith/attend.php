<?php
/**
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('kronolith', array('authentication' => 'none'));

$cal = Horde_Util::getFormData('c');
$id = Horde_Util::getFormData('e');
$uid = Horde_Util::getFormData('i');
$user = Horde_Util::getFormData('u');

switch (Horde_Util::getFormData('a')) {
case 'accept':
    $action = Kronolith::RESPONSE_ACCEPTED;
    $msg = _("You have successfully accepted attendence to this event.");
    break;

case 'decline':
    $action = Kronolith::RESPONSE_DECLINED;
    $msg = _("You have successfully declined attendence to this event.");
    break;

case 'tentative':
    $action = Kronolith::RESPONSE_TENTATIVE;
    $msg = _("You have tentatively accepted attendence to this event.");
    break;

default:
    $action = Kronolith::RESPONSE_NONE;
    $msg = '';
    break;
}

if (((empty($cal) || empty($id)) && empty($uid)) || empty($user)) {
    $notification->push(_("The request was incomplete. Some parameters that are necessary to accept or decline an event are missing."), 'horde.error');
    $title = '';
} else {
    try {
        if (empty($uid)) {
            $event = Kronolith::getDriver(null, $cal)->getEvent($id);
        } else {
            $event = Kronolith::getDriver()->getByUID($uid);
        }
        if (!$event->hasAttendee($user)) {
            $notification->push(_("You are not an attendee of the specified event."), 'horde.error');
            $title = $event->getTitle();
        } else {
            $event->addAttendee($user, Kronolith::PART_IGNORE, $action);
            try {
                $event->save();
                if (!empty($msg)) {
                    $notification->push($msg, 'horde.success');
                }
            } catch (Exception $e) {
                $notification->push($e, 'horde.error');
            }
            $title = $event->getTitle();
        }
    } catch (Exception $e) {
        $notification->push($e, 'horde.error');
        $title = '';
    }
}

require $registry->get('templates', 'horde') . '/common-header.inc';
require KRONOLITH_TEMPLATES . '/javascript_defs.php';

?>
<div id="menu"><h1>&nbsp;<?php echo htmlspecialchars($title) ?></h1></div>
<?php

$notification->notify(array('listeners' => 'status'));
require $registry->get('templates', 'horde') . '/common-footer.inc';
