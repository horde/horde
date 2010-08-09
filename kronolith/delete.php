<?php
/**
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

if (Kronolith::showAjaxView()) {
    Horde::applicationUrl('', true)->redirect();
}

if (Kronolith_Resource::isResourceCalendar($c = Horde_Util::getFormData('calendar'))) {
    $driver = 'Resource';
} else {
    $driver = Horde_Util::getFormData('type');
}

$kronolith_driver = Kronolith::getDriver($driver, $c);
if ($eventID = Horde_Util::getFormData('eventID')) {
    try {
        $event = $kronolith_driver->getEvent($eventID);
    } catch(Exception $e) {
        if (($url = Horde_Util::getFormData('url')) === null) {
            $url = Horde::applicationUrl($prefs->getValue('defaultview') . '.php', true);
        } else {
            $url = new Horde_Url($url);
        }
        $url->redirect();
    }
    if ($driver != 'Resource') {
        if ($driver == 'remote') {
            /* The remote server is doing the permission checks for us. */
            $have_perms = true;
        } else {
            $share = $kronolith_shares->getShare($event->calendar);
            if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE, $event->creator)) {
                $notification->push(_("You do not have permission to delete this event."), 'horde.warning');
            } else {
                $have_perms = true;
            }
        }
    } else {
        if (!$registry->isAdmin()) {
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
                try {
                    $kronolith_driver->deleteEvent($event->id);
                } catch (Exception $e) {
                    $notification->push($e, 'horde.error');
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
            try {
                $kronolith_driver->deleteEvent($event->id);
            } catch (Exception $e) {
                $notification->push($e, 'horde.error');
            }
        }

        if (Horde_Util::getFormData('sendupdates', false)) {
            Kronolith::sendITipNotifications($event, $notification, $notification_type, $instance);
        }
    }
}

$url = Horde_Util::getFormData('url');
if (!empty($url)) {
    $url = new Horde_Url($url, true);
} else {
    $date = new Horde_Date(Horde_Util::getFormData('date'));
    $url = Horde::applicationUrl($prefs->getValue('defaultview') . '.php', true)
        ->add('date', Horde_Util::getFormData('date', date('Ymd')));
}

// Make sure URL is unique.
$url->unique()->redirect();
