<?php
/**
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

if (Kronolith::showAjaxView()) {
    Horde::applicationUrl('', true)->redirect();
}

do {
    if (Horde_Util::getFormData('cancel')) {
        break;
    }

    list($targetType, $targetcalendar) = explode('_', Horde_Util::getFormData('targetcalendar'), 2);
    if (strpos($targetcalendar, ':')) {
        list($calendar_id, $user) = explode(':', $targetcalendar, 2);
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
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        if ($perms->hasAppPermission('max_events') !== true &&
            $perms->hasAppPermission('max_events') <= Kronolith::countEvents()) {
            try {
                $message = Horde::callHook('perms_denied', array('kronolith:max_events'));
            } catch (Horde_Exception_HookNotSet $e) {
                $message = @htmlspecialchars(sprintf(_("You are not allowed to create more than %d events."), $perms->hasAppPermission('max_events')), ENT_COMPAT, $GLOBALS['registry']->getCharset());
            }
            $GLOBALS['notification']->push($message, 'horde.error', array('content.raw'));
            break;
        }

        $event = Kronolith::getDriver($targetType, $calendar_id)->getEvent();
        $event->readForm();
        try {
            $event->save();
            Kronolith::notifyOfResourceRejection($event);
            if (Horde_Util::getFormData('sendupdates', false)) {
                try {
                    $event = Kronolith::getDriver()->getEvent($result);
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
    $url = Horde::applicationUrl($prefs->getValue('defaultview') . '.php', true)
        ->add(array('month' => Horde_Util::getFormData('month'),
                    'year' => Horde_Util::getFormData('year')));
}

// Make sure URL is unique.
$url->unique()->redirect();
