<?php
/**
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/base.php';

if (!Util::getFormData('cancel')) {
    $targetcalendar = Util::getFormData('targetcalendar');
    if (strpos($targetcalendar, ':')) {
        list($calendar_id, $user) = explode(':', $targetcalendar, 2);
    } else {
        $calendar_id = $targetcalendar;
        $user = Auth::getAuth();
    }
    $share = &$kronolith_shares->getShare($calendar_id);
    if (is_a($share, 'PEAR_Error')) {
        $notification->push(sprintf(_("There was an error accessing the calendar: %s"), $share->getMessage()), 'horde.error');
    } elseif ($user != Auth::getAuth() &&
              !$share->hasPermission(Auth::getAuth(), PERMS_DELEGATE, Auth::getAuth())) {
        $notification->push(sprintf(_("You do not have permission to delegate events to %s."), Kronolith::getUserName($user)), 'horde.warning');
    } elseif ($user == Auth::getAuth() &&
              !$share->hasPermission(Auth::getAuth(), PERMS_EDIT, Auth::getAuth())) {
        $notification->push(sprintf(_("You do not have permission to add events to %s."), $share->get('name')), 'horde.warning');
    } elseif (Kronolith::hasPermission('max_events') === true ||
              Kronolith::hasPermission('max_events') > Kronolith::countEvents()) {
        $event = Kronolith::getDriver(null, $calendar_id)->getEvent();
        $event->readForm();
        $result = $event->save();
        if (is_a($result, 'PEAR_Error')) {
            $userinfo = $result->getUserInfo();
            if (is_array($userinfo)) {
                $userinfo = implode(', ', $userinfo);
            }
            $message = $result->getMessage() . ($userinfo ? ' : ' . $userinfo : '');

            $notification->push(sprintf(_("There was an error adding the event: %s"), $message), 'horde.error');
        } else {
            if (Util::getFormData('sendupdates', false)) {
                $event = Kronolith::getDriver()->getEvent($result);
                if (is_a($event, 'PEAR_Error')) {
                    $notification->push($event, 'horde.error');
                } else {
                    Kronolith::sendITipNotifications($event, $notification, Kronolith::ITIP_REQUEST);
                }
            }
        }
    }
}

if ($url = Util::getFormData('url')) {
    header('Location: ' . $url);
} else {
    $url = Util::addParameter($prefs->getValue('defaultview') . '.php',
                              array('month' => Util::getFormData('month'),
                                    'year' => Util::getFormData('year')));
    header('Location: ' . Horde::applicationUrl($url, true));
}
