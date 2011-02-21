#!/usr/bin/env php
<?php
/**
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('kronolith', array('cli' => true));

send_agendas();

/**
 */
function send_agendas()
{
    if (isset($_SERVER['REQUEST_TIME'])) {
        $runtime = $_SERVER['REQUEST_TIME'];
    } else {
        $runtime = time();
    }

    $calendars = $GLOBALS['kronolith_shares']->listAllShares();

    // If there are no calendars to check, we're done.
    if (!count($calendars)) {
        return;
    }

    if (empty($GLOBALS['conf']['reminder']['server_name']) ||
        empty($GLOBALS['conf']['reminder']['from_addr'])) {
        die('You must configure the server_name and from_addr settings for reminders in the calendar configuration.');
    }

    $_SERVER['SERVER_NAME'] = $GLOBALS['conf']['server']['name'] = $GLOBALS['conf']['reminder']['server_name'];

    // Retrieve a list of users associated with each calendar, and
    // thus a list of users who have used kronolith and
    // potentially have an agenda preference set.
    $users = array();
    foreach (array_keys($calendars) as $calendarId) {
        try {
            $calendar = $GLOBALS['kronolith_shares']->getShare($calendarId);
        } catch (Exception $e) {
            continue;
        }
        $users = array_merge($users, $calendar->listUsers(Horde_Perms::READ));
    }

    // Remove duplicates.
    $users = array_unique($users);

    // Generate image mime part first and only once, because we need
    // the Content-ID.
    $image = Kronolith::getImagePart('big_agenda.png');

    $runtime = new Horde_Date($runtime);
    $default_timezone = date_default_timezone_get();
    $kronolith_driver = Kronolith::getDriver();
    $view = new Horde_View(array('templatePath' => KRONOLITH_TEMPLATES . '/agenda', 'encoding' => 'UTF-8'));
    new Horde_View_Helper_Text($view);
    $view->imageId = $image->getContentId();
    $view->date = $runtime;
    if (!$GLOBALS['prefs']->isLocked('daily_agenda')) {
        $view->prefsUrl = Horde::url(Horde::getServiceLink('prefs', 'kronolith'), true)->remove(session_name());
    }

    // Loop through the users and generate an agenda for them
    foreach ($users as $user) {
        $prefs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Prefs')->create('kronolith', array(
            'user' => $user
        ));
        $agenda_calendars = $prefs->getValue('daily_agenda');

        if (!$agenda_calendars) {
            continue;
        }

        // Try to find an email address for the user.
        $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($user);
        $email = $identity->getDefaultFromAddress(true);

        // Check if user has a timezone pref, and set it. Otherwise, make
        // sure to use the server's default timezone.
        $tz = $prefs->getValue('timezone');
        date_default_timezone_set(empty($tz) ? $default_timezone : $tz);

        // If we found an email address, generate the agenda.
        switch ($agenda_calendars) {
        case 'owner':
            $calendars = $GLOBALS['kronolith_shares']->listShares(
                $user,
                array('perm' => Horde_Perms::SHOW,
                      'attributes' => $user));
            $calendars = array_keys($calendars);
            break;

        case 'read':
            $calendars = $GLOBALS['kronolith_shares']->listShares(
                $user,
                array('perm' => Horde_Perms::SHOW));
            $calendars = array_keys($calendars);
            break;

        case 'show':
        default:
            $calendars = array();
            $shown_calendars = unserialize($prefs->getValue('display_cals'));
            $cals = $GLOBALS['kronolith_shares']->listShares(
                $user,
                array('perm' => Horde_Perms::SHOW));
            foreach (array_keys($cals) as $calId) {
                if (in_array($calId, $shown_calendars)) {
                    $calendars[] = $calId;
                }
            }
        }

        // Get a list of events for today
        $eventlist = array();
        foreach ($calendars as $calId) {
            $kronolith_driver->open($calId);
            $events = $kronolith_driver->listEvents($runtime, $runtime, true);
            foreach ($events as $dayevents) {
                foreach ($dayevents as $event) {
                    // The event list contains events starting at 12am.
                    if ($event->start->compareDate($runtime)) {
                        continue;
                    }
                    $eventlist[$event->start->strftime('%Y%m%d%H%M%S')] = $event;
                }
            }
        }

        if (!count($eventlist)) {
            continue;
        }

        // If there are any events, generate and send the email.
        ksort($eventlist);
        $GLOBALS['registry']->setLanguageEnvironment($prefs->getValue('language'));
        $twentyFour = $prefs->getValue('twentyFour');

        $view->pad = max(Horde_String::length(_("All day")) + 2, $twentyFour ? 6 : 8);
        $view->dateFormat = $prefs->getValue('date_format');
        $view->timeformat = $twentyFour  ? 'H:i' : 'h:ia';
        $view->user = $user;
        $view->events = $eventlist;

        $mime_mail = new Horde_Mime_Mail(
            array('subject' => sprintf(_("Your daily agenda for %s"), $runtime->strftime($view->dateFormat)),
                  'to' => $email,
                  'from' => $GLOBALS['conf']['reminder']['from_addr'],
                  'charset' => 'UTF-8'));
        $mime_mail->addHeader('User-Agent', 'Kronolith ' . $GLOBALS['registry']->getVersion());
        $mime_mail->addHeader('Auto-Submitted', 'auto-generated');
        try {
            $mime_mail->addRecipients($email);
        } catch (Horde_Mime_Exception $e) {}

        $mime_mail->setBasePart(Kronolith::buildMimeMessage($view, 'notification', $image));

        Horde::logMessage(sprintf('Sending daily agenda to %s', $email), 'DEBUG');
        try {
            $mime_mail->send($GLOBALS['injector']->getInstance('Horde_Mail'), false, false);
        } catch (Horde_Mime_Exception $e) {
            die($e);
        }
    }
}
