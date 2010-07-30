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
    header('Location: ' . Horde::applicationUrl('', true));
    exit;
}

if (!Horde_Util::getFormData('cancel')) {
    $targetcalendar = Horde_Util::getFormData('targetcalendar');
    if (strpos($targetcalendar, ':')) {
        list($calendar_id, $user) = explode(':', $targetcalendar, 2);
    } else {
        $calendar_id = $targetcalendar;
        $user = $GLOBALS['registry']->getAuth();
    }
    try {
        $share = Kronolith::getInternalCalendar($calendar_id);
        if ($user != $GLOBALS['registry']->getAuth() &&
            !$share->hasPermission($GLOBALS['registry']->getAuth(), Kronolith::PERMS_DELEGATE, $GLOBALS['registry']->getAuth())) {
            $notification->push(sprintf(_("You do not have permission to delegate events to %s."), Kronolith::getUserName($user)), 'horde.warning');
        } elseif ($user == $GLOBALS['registry']->getAuth() &&
                  !$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT, $GLOBALS['registry']->getAuth())) {
            $notification->push(sprintf(_("You do not have permission to add events to %s."), $share->get('name')), 'horde.warning');
        } elseif ($GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_events') === true ||
                  $GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_events') > Kronolith::countEvents()) {
            $event = Kronolith::getDriver(null, $calendar_id)->getEvent();
            $event->readForm();
            try {
                $result = $event->save();
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
header('Location: ' . $url->unique());
