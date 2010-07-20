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
        try {
            $message = Horde::callHook('perms_denied', array('kronolith:max_events'));
        } catch (Horde_Exception_HookNotSet $e) {
            $message = @htmlspecialchars(sprintf(_("You are not allowed to create more than %d events."), $perms->hasAppPermission('max_events')), ENT_COMPAT, $GLOBALS['registry']->getCharset());
        }
        $GLOBALS['notification']->push($message, 'horde.error', array('content.raw'));
        return false;
    }
    return true;
}

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

if (Kronolith::showAjaxView()) {
    header('Location: ' . Horde::applicationUrl('', true));
    exit;
}

$kronolith_driver = Kronolith::getDriver();

if ($exception = Horde_Util::getFormData('del_exception')) {
    $calendar = Horde_Util::getFormData('calendar');
    try {
        $share = Kronolith::getInternalCalendar($calendar);
        $kronolith_driver->open($calendar);
        $event = $kronolith_driver->getEvent(Horde_Util::getFormData('eventID'));
        $result = sscanf($exception, '%04d%02d%02d', $year, $month, $day);
        if ($result == 3 && $event->recurs()) {
            $event->recurrence->deleteException($year, $month, $day);
            _save($event);
        }
    } catch (Exception $e) {
        $notification->push(sprintf(_("There was an error accessing the calendar: %s"), $e->getMessage()), 'horde.error');
    }
} elseif (!Horde_Util::getFormData('cancel')) {
    $source = Horde_Util::getFormData('existingcalendar');
    $targetcalendar = Horde_Util::getFormData('targetcalendar');
    if (strpos($targetcalendar, ':')) {
        list($target, $user) = explode(':', $targetcalendar, 2);
    } else {
        $target = $targetcalendar;
        $user = $GLOBALS['registry']->getAuth();
    }
    try {
        $share = Kronolith::getInternalCalendar($target);
        $event = false;
        if (($edit_recur = Horde_Util::getFormData('edit_recur')) &&
            $edit_recur != 'all' && $edit_recur != 'copy' &&
            _check_max()) {

            /* Get event details. */
            $kronolith_driver->open($source);
            $event = $kronolith_driver->getEvent(Horde_Util::getFormData('eventID'));
            $recur_ex = Horde_Util::getFormData('recur_ex');
            $exception = new Horde_Date($recur_ex);

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
                $kronolith_driver->open($target);
                $event = $kronolith_driver->getEvent();
                $event->readForm();
                $event->recurrence->setRecurType(Horde_Date_Recurrence::RECUR_NONE);
                $event->baseid = $uid;
                $event->exceptionoriginaldate = new Horde_Date($exception->strftime('%Y-%m-%d') . 'T' . $originaltime . $exception->strftime('%P'));

                break;

            case 'future':
                /* Set recurrence end. */
                $exception->mday--;
                if ($event->end->compareDate($exception) > 0) {
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
                $kronolith_driver->open($target);
                $event = $kronolith_driver->getEvent();
                $event->readForm();

                break;
            }

            $event->uid = null;
            _save($event);
            $event = null;
        } elseif (Horde_Util::getFormData('saveAsNew') ||
                  $edit_recur == 'copy') {
            if (_check_max()) {
                $kronolith_driver->open($target);
                $event = $kronolith_driver->getEvent();
            }
        } else {
            $event_load_from = $source;

            if ($target != $source) {
                // Only delete the event from the source calendar if this user
                // has permissions to do so.
                try {
                    $sourceShare = Kronolith::getInternalCalendar($source);
                    if ($sourceShare->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE) &&
                        (($user == $GLOBALS['registry']->getAuth() &&
                          $share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) ||
                         ($user != $GLOBALS['registry']->getAuth() &&
                          $share->hasPermission($GLOBALS['registry']->getAuth(), Kronolith::PERMS_DELEGATE)))) {
                        $kronolith_driver->open($source);
                        try {
                            $kronolith_driver->move(Horde_Util::getFormData('eventID'), $target);
                            $event_load_from = $target;
                        } catch (Exception $e) {
                            $notification->push(sprintf(_("There was an error moving the event: %s"), $e->getMessage()), 'horde.error');
                        }
                    }
                } catch (Exception $e) {
                }
            }

            $kronolith_driver->open($event_load_from);
            $event = $kronolith_driver->getEvent(Horde_Util::getFormData('eventID'));
        }

        if ($event) {
            if (isset($sourceShare)
                && !$sourceShare->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
                $notification->push(_("You do not have permission to move this event."), 'horde.warning');
            } elseif ($user != $GLOBALS['registry']->getAuth() &&
                      !$share->hasPermission($GLOBALS['registry']->getAuth(), Kronolith::PERMS_DELEGATE, $event->creator)) {
                $notification->push(sprintf(_("You do not have permission to delegate events to %s."), Kronolith::getUserName($user)), 'horde.warning');
            } elseif ($user == $GLOBALS['registry']->getAuth() &&
                      !$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT, $event->creator)) {
                $notification->push(_("You do not have permission to edit this event."), 'horde.warning');
            } else {
                $event->readForm();
                _save($event);
            }
        }
    } catch (Exception $e) {
        $notification->push(sprintf(_("There was an error accessing the calendar: %s"), $e->getMessage()), 'horde.error');
    }
}

$url = Horde_Util::getFormData('url');
if (!empty($url)) {
    $url = new Horde_Url($url, true);
} else {
    $url = Horde::applicationUrl($prefs->getValue('defaultview') . '.php', true)
        ->add(array('month' => Horde_Util::getFormData('month'),
                    'year' => Horde_Util::getFormData('year')));
}

// Make sure URL is unique.
header('Location: ' . $url->add('unique', hash('md5', microtime())));
