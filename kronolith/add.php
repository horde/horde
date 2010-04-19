<?php
/**
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

if (!Horde_Util::getFormData('cancel')) {
    $targetcalendar = Horde_Util::getFormData('targetcalendar');
    if (strpos($targetcalendar, ':')) {
        list($calendar_id, $user) = explode(':', $targetcalendar, 2);
    } else {
        $calendar_id = $targetcalendar;
        $user = Horde_Auth::getAuth();
    }
    try {
        $share = Kronolith::getInternalCalendar($calendar_id);
        if ($user != Horde_Auth::getAuth() &&
            !$share->hasPermission(Horde_Auth::getAuth(), Kronolith::PERMS_DELEGATE, Horde_Auth::getAuth())) {
            $notification->push(sprintf(_("You do not have permission to delegate events to %s."), Kronolith::getUserName($user)), 'horde.warning');
        } elseif ($user == Horde_Auth::getAuth() &&
                  !$share->hasPermission(Horde_Auth::getAuth(), Horde_Perms::EDIT, Horde_Auth::getAuth())) {
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
                // @todo: there is no getUserInfo() in Horde_Exception
                $userinfo = $e->getUserInfo();
                if (is_array($userinfo)) {
                    $userinfo = implode(', ', $userinfo);
                }
                $message = $e->getMessage() . ($userinfo ? ' : ' . $userinfo : '');

                $notification->push(sprintf(_("There was an error adding the event: %s"), $message), 'horde.error');
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
