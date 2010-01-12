<?php
/**
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */

$kronolith_authentication = 'none';
require_once dirname(__FILE__) . '/lib/base.php';

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
    if (empty($uid)) {
        $event = Kronolith::getDriver(null, $cal)->getEvent($id);
    } else {
        $event = Kronolith::getDriver()->getByUID($uid);
    }
    if (is_a($event, 'PEAR_Error')) {
        $notification->push($event, 'horde.error');
        $title = '';
    } elseif (!$event->hasAttendee($user)) {
        $notification->push(_("You are not an attendee of the specified event."), 'horde.error');
        $title = $event->getTitle();
    } else {
        $event->addAttendee($user, Kronolith::PART_IGNORE, $action);
        $result = $event->save();
        if (is_a($result, 'PEAR_Error')) {
            $notification->push($result, 'horde.error');
        } elseif (!empty($msg)) {
            $notification->push($msg, 'horde.success');
        }
        $title = $event->getTitle();
    }
}

require KRONOLITH_TEMPLATES . '/common-header.inc';

?>
<div id="menu"><h1>&nbsp;<?php echo htmlspecialchars($title) ?></h1></div>
<?php

$notification->notify(array('listeners' => 'status'));
require $registry->get('templates', 'horde') . '/common-footer.inc';
