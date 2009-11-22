<?php
/**
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */

require_once dirname(__FILE__) . '/lib/base.php';

if (Kronolith_Resource::isResourceCalendar($c = Horde_Util::getFormData('calendar'))) {
    $driver = 'Resource';
} else {
    $driver = null;
}

$kronolith_driver = Kronolith::getDriver($driver, $c);
if ($eventID = Horde_Util::getFormData('eventID')) {
    $event = $kronolith_driver->getEvent($eventID);
    if (is_a($event, 'PEAR_Error')) {
        if (($url = Horde_Util::getFormData('url')) === null) {
            $url = Horde::applicationUrl($prefs->getValue('defaultview') . '.php', true);
        }
        header('Location: ' . $url);
        exit;
    }
    if ($driver != 'Resource') {
        $share = &$kronolith_shares->getShare($event->getCalendar());
        if (!$share->hasPermission(Horde_Auth::getAuth(), Horde_Perms::DELETE, $event->getCreatorID())) {
            $notification->push(_("You do not have permission to delete this event."), 'horde.warning');
        } else {
            $have_perms = true;
        }
    } else {
        if (!Horde_Auth::isAdmin()) {
            $notification->push(_("You do not have permission to delete this event."), 'horde.warning');
        } else {
            $have_perms = true;
        }
    }

    if (!empty($have_perms)) {
        $notification_type = Kronolith::ITIP_CANCEL;
        $instance = null;
        if (Horde_Util::getFormData('future')) {
            $recurEnd = new Horde_Date(array('hour' => 0, 'min' => 0, 'sec' => 0,
                                             'month' => Horde_Util::getFormData('month', date('n')),
                                             'mday' => Horde_Util::getFormData('mday', date('j')) - 1,
                                             'year' => Horde_Util::getFormData('year', date('Y'))));
            if ($event->end->compareDate($recurEnd) > 0) {
                $result = $kronolith_driver->deleteEvent($event->getId());
                if (is_a($result, 'PEAR_Error')) {
                    $notification->push($result, 'horde.error');
                }
            } else {
                $event->recurrence->setRecurEnd($recurEnd);
                $event->save();
            }
            $notification_type = Kronolith::ITIP_REQUEST;
        } elseif (Horde_Util::getFormData('current')) {
            $event->recurrence->addException(Horde_Util::getFormData('year'),
                                             Horde_Util::getFormData('month'),
                                             Horde_Util::getFormData('mday'));
            $event->save();
            $instance = new Horde_Date(array('year' => Horde_Util::getFormData('year'),
                                             'month' => Horde_Util::getFormData('month'),
                                             'mday' => Horde_Util::getFormData('mday')));
        }

        if (!$event->recurs() ||
            Horde_Util::getFormData('all') ||
            !$event->recurrence->hasActiveRecurrence()) {
            $result = $kronolith_driver->deleteEvent($event->getId());
            if (is_a($result, 'PEAR_Error')) {
                $notification->push($result, 'horde.error');
            }
        }

        if (Horde_Util::getFormData('sendupdates', false)) {
            Kronolith::sendITipNotifications($event, $notification, $notification_type, $instance);
        }
    }
}

if ($url = Horde_Util::getFormData('url')) {
    $location = $url;
} else {
    $url = Horde_Util::addParameter($prefs->getValue('defaultview') . '.php',
                              'date', Horde_Util::getFormData('date', date('Ymd')));
    $location = Horde::applicationUrl($url, true);
}

header('Location: ' . $location);
