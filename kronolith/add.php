<?php
/**
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->redirect();
}

do {
    if (Horde_Util::getFormData('cancel')) {
        break;
    }

    list($targetType, $targetcalendar) = explode('_', Horde_Util::getFormData('targetcalendar'), 2);
    if (strpos($targetcalendar, '\\')) {
        list($calendar_id, $user) = explode('\\', $targetcalendar, 2);
    } else {
        $calendar_id = $targetcalendar;
        $user = $GLOBALS['registry']->getAuth();
    }

    try {
        /* Permission checks on the target calendar . */
        switch ($targetType) {
        case 'internal':
            $kronolith_calendar = $all_calendars[$calendar_id];
            break;
        case 'remote':
            $kronolith_calendar = $all_remote_calendars[$calendar_id];
            break;
        case 'resource':
            $rid = Kronolith::getDriver('Resource')->getResourceIdByCalendar($calendar_id);
            $kronolith_calendar = new Kronolith_Calendar_Resource(
                array('resource' => Kronolith::getDriver('Resource')->getResource($rid)));
            break;
        default:
            break 2;
        }
        if ($user == $GLOBALS['registry']->getAuth() &&
            !$kronolith_calendar->hasPermission(Horde_Perms::EDIT)) {
            $notification->push(sprintf(_("You do not have permission to add events to %s."), $kronolith_calendar->name()), 'horde.warning');
            break;
        }
        if ($user != $GLOBALS['registry']->getAuth() &&
            !$kronolith_calendar->hasPermission(Kronolith::PERMS_DELEGATE)) {
            $notification->push(sprintf(_("You do not have permission to delegate events to %s."), Kronolith::getUserName($user)), 'horde.warning');
            break;
        }
        $perms = $GLOBALS['injector']->getInstance('Horde_Core_Perms');
        if ($perms->hasAppPermission('max_events') !== true &&
            $perms->hasAppPermission('max_events') <= Kronolith::countEvents()) {
            Horde::permissionDeniedError(
                'kronolith',
                'max_events',
                sprintf(_("You are not allowed to create more than %d events."), $perms->hasAppPermission('max_events'))
            );
            break;
        }

        $event = Kronolith::getDriver($targetType, $calendar_id)->getEvent();
        $event->readForm();
        try {
            $event->save();
            Kronolith::notifyOfResourceRejection($event);
            if (Horde_Util::getFormData('sendupdates', false)) {
                try {
                    Kronolith::sendITipNotifications($event, $notification, Kronolith::ITIP_REQUEST);
                } catch (Exception $e) {
                    $notification->push($e, 'horde.error');
                }
            }
        } catch (Exception $e) {
            $notification->push(sprintf(_("There was an error adding the event: %s"), $e->getMessage()), 'horde.error');
        }
    } catch (Exception $e) {
        $notification->push(sprintf(_("There was an error accessing the calendar: %s"), $e->getMessage()), 'horde.error');
    }
} while (false);

$url = Horde_Util::getFormData('url');
if (!empty($url)) {
    $url = new Horde_Url($url, true);
} else {
    $url = Horde::url($prefs->getValue('defaultview') . '.php', true)
        ->add(array('month' => Horde_Util::getFormData('month'),
                    'year' => Horde_Util::getFormData('year')));
}

// Make sure URL is unique.
$url->unique()->redirect();
