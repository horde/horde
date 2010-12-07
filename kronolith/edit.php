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

function _save(&$event)
{
    try {
        $event->save();
        if (Horde_Util::getFormData('sendupdates', false)) {
            Kronolith::sendITipNotifications($event, $GLOBALS['notification'], Kronolith::ITIP_REQUEST);
        }
    } catch (Exception $e) {
        $GLOBALS['notification']->push(sprintf(_("There was an error editing the event: %s"), $e->getMessage()), 'horde.error');
    }

    Kronolith::notifyOfResourceRejection($event);
}

function _check_max()
{
    $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
    if ($perms->hasAppPermission('max_events') !== true &&
        $perms->hasAppPermission('max_events') <= Kronolith::countEvents()) {
        Horde::permissionDeniedError(
            'kronolith',
            'max_events',
            sprintf(_("You are not allowed to create more than %d events."), $perms->hasAppPermission('max_events'))
        );
        return false;
    }
    return true;
}

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->redirect();
}

do {
    if ($exception = Horde_Util::getFormData('del_exception')) {
        /* Deleting recurrence exceptions. */
        list($type, $calendar) = explode('_', Horde_Util::getFormData('calendar'), 2);
        try {
            $kronolith_driver = Kronolith::getDriver($type, $calendar);
            switch ($type) {
            case 'internal':
                $kronolith_calendar = $all_calendars[$calendar];
                break;
            case 'remote':
                $kronolith_calendar = $all_remote_calendars[$calendar];
                break;
            }

            $event = $kronolith_driver->getEvent(Horde_Util::getFormData('eventID'));
            if (!$kronolith_calendar->hasPermission(Horde_Perms::EDIT, $registry->getAuth(), $event->creator)) {
                $notification->push(_("You do not have permission to edit this event."), 'horde.warning');
                break;
            }
            $result = sscanf($exception, '%04d%02d%02d', $year, $month, $day);
            if ($result == 3 && $event->recurs()) {
                $event->recurrence->deleteException($year, $month, $day);
                _save($event);
            }
        } catch (Exception $e) {
            $notification->push(sprintf(_("There was an error accessing the calendar: %s"), $e->getMessage()), 'horde.error');
        }
        break;
    }

    if (Horde_Util::getFormData('cancel')) {
        break;
    }

    list($sourceType, $source) = explode('_', Horde_Util::getFormData('existingcalendar'), 2);
    list($targetType, $targetcalendar) = explode('_', Horde_Util::getFormData('targetcalendar'), 2);
    if (strpos($targetcalendar, '\\')) {
        list($target, $user) = explode('\\', $targetcalendar, 2);
    } else {
        $target = $targetcalendar;
        $user = $GLOBALS['registry']->getAuth();
    }

    try {
        $event = false;
        if (($edit_recur = Horde_Util::getFormData('edit_recur')) &&
            $edit_recur != 'all' && $edit_recur != 'copy' &&
            ($targetType != 'internal' || _check_max())) {
            /* Edit a recurring exception. */

            /* Get event details. */
            $kronolith_driver = Kronolith::getDriver($sourceType, $source);
            switch ($sourceType) {
            case 'internal':
                $kronolith_calendar = $all_calendars[$source];
                break;
            case 'remote':
                $kronolith_calendar = $all_remote_calendars[$source];
                break;
            }
            $event = $kronolith_driver->getEvent(Horde_Util::getFormData('eventID'));
            if (!$event->hasPermission(Horde_Perms::EDIT)) {
                $notification->push(_("You do not have permission to edit this event."), 'horde.warning');
                break;
            }

            $exception = new Horde_Date(Horde_Util::getFormData('recur_ex'));

            switch ($edit_recur) {
            case 'current':
                /* Add exception. */
                $event->recurrence->addException($exception->year,
                                                 $exception->month,
                                                 $exception->mday);
                $event->save();
                $uid = $event->uid;
                $originaltime = $event->start->strftime('%T');

                /* Create one-time event. */
                $event = $kronolith_driver->getEvent();
                $event->readForm();
                $event->baseid = $uid;
                $event->exceptionoriginaldate = new Horde_Date($exception->strftime('%Y-%m-%d') . 'T' . $originaltime . $exception->strftime('%P'));

                break;

            case 'future':
                /* Set recurrence end. */
                $exception->mday--;
                if ($event->end->compareDate($exception) > 0 &&
                    $event->hasPermission(Horde_Perms::DELETE)) {
                    try {
                        $kronolith_driver->deleteEvent($event->id);
                    } catch (Exception $e) {
                        $notification->push($e, 'horde.error');
                    }
                } else {
                    $event->recurrence->setRecurEnd($exception);
                    $event->save();
                }

                /* Create new event. */
                $event = $kronolith_driver->getEvent();
                $event->readForm();

                break;
            }

            $event->uid = null;
            _save($event);
            break;
        }

        /* Permission checks on the target calendar . */
        switch ($targetType) {
        case 'internal':
            $kronolith_calendar = $all_calendars[$target];
            break;
        case 'remote':
            $kronolith_calendar = $all_remote_calendars[$target];
            break;
        default:
            break 2;
        }
        if ($user == $GLOBALS['registry']->getAuth() &&
            !$kronolith_calendar->hasPermission(Horde_Perms::EDIT)) {
            $notification->push(_("You do not have permission to edit this event."), 'horde.warning');
            break;
        }
        if ($user != $GLOBALS['registry']->getAuth() &&
            !$kronolith_calendar->hasPermission(Kronolith::PERMS_DELEGATE)) {
            $notification->push(sprintf(_("You do not have permission to delegate events to %s."), Kronolith::getUserName($user)), 'horde.warning');
            break;
        }

        if (Horde_Util::getFormData('saveAsNew') || $edit_recur == 'copy') {
            /* Creating a copy of the event. */
            if ($targetType == 'internal' && !_check_max()) {
                break;
            }
            $kronolith_driver = Kronolith::getDriver($targetType, $target);
            $event = $kronolith_driver->getEvent();
        } else {
            /* Regular saving of event. */
            $eventId = Horde_Util::getFormData('eventID');
            $kronolith_driver = Kronolith::getDriver($sourceType, $source);
            $event = $kronolith_driver->getEvent($eventId);

            if ($target != $source) {
                /* Moving the event to a different calendar. Only delete the
                 * event from the source calendar if this user has permissions
                 * to do so. */
                if (!$event->hasPermission(Horde_Perms::DELETE)) {
                    $notification->push(_("You do not have permission to move this event."), 'horde.warning');
                } else {
                    if ($sourceType == 'internal' &&
                        $targetType == 'internal') {
                        try {
                            // TODO: abstract this out.
                            $kronolith_driver->move($eventId, $target);
                            $kronolith_driver->open($target);
                            $event = $kronolith_driver->getEvent($eventId);
                        } catch (Exception $e) {
                            $notification->push(sprintf(_("There was an error moving the event: %s"), $e->getMessage()), 'horde.error');
                        }
                    } else {
                        $kronolith_driver->deleteEvent($eventId);
                        $kronolith_driver = Kronolith::getDriver($targetType, $target);
                        $event = $kronolith_driver->getEvent();
                    }
                }
            }
        }

        if ($event) {
            $event->readForm();
            _save($event);
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

/* Make sure URL is unique. */
$url->unique()->redirect();
