#!/usr/bin/env php
<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('kronolith', array('authentication' => 'none', 'cli' => true));

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

    if (!empty($GLOBALS['conf']['reminder']['server_name'])) {
        $GLOBALS['conf']['server']['name'] = $GLOBALS['conf']['reminder']['server_name'];
    }

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

    $runtime = new Horde_Date($runtime);
    $default_timezone = date_default_timezone_get();
    $kronolith_driver = Kronolith::getDriver();
    $view = new Horde_View(array('templatePath' => KRONOLITH_TEMPLATES . '/agenda', 'encoding' => $GLOBALS['registry']->getCharset()));
    new Horde_View_Helper_Text($view);

    // Loop through the users and generate an agenda for them
    foreach ($users as $user) {
        $prefs = Horde_Prefs::singleton($GLOBALS['conf']['prefs']['driver'],
                                        'kronolith', $user);
        $prefs->retrieve();
        $agenda_calendars = $prefs->getValue('daily_agenda');

        if (!$agenda_calendars) {
            continue;
        }

        // Try to find an email address for the user.
        $identity = $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity($user);
        $email = $identity->getDefaultFromAddress(true);

        // Check if user has a timezone pref, and set it. Otherwise, make
        // sure to use the server's default timezone.
        $tz = $prefs->getValue('timezone');
        date_default_timezone_set(empty($tz) ? $default_timezone : $tz);

        // If we found an email address, generate the agenda.
        switch ($agenda_calendars) {
        case 'owner':
            $calendars = $GLOBALS['kronolith_shares']->listShares($user, Horde_Perms::SHOW, $user);
            break;

        case 'read':
            $calendars = $GLOBALS['kronolith_shares']->listShares($user, Horde_Perms::SHOW, null);
            break;

        case 'show':
        default:
            $calendars = array();
            $shown_calendars = unserialize($prefs->getValue('display_cals'));
            $cals = $GLOBALS['kronolith_shares']->listShares($user, Horde_Perms::SHOW, null);
            foreach ($cals as $calId => $cal) {
                if (in_array($calId, $shown_calendars)) {
                    $calendars[$calId] = $cal;
                }
            }
        }

        // Get a list of events for today
        $eventlist = array();
        foreach ($calendars as $calId => $calendar) {
            $kronolith_driver->open($calId);
            $events = $kronolith_driver->listEvents($runtime, $runtime);
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
        $lang = $prefs->getValue('language');
        $twentyFour = $prefs->getValue('twentyFour');
        $dateFormat = $prefs->getValue('date_format');

        $view->pad = max(Horde_String::length(_("All day")) + 2, $twentyFour ? 6 : 8);
        $view->date = $runtime->strftime($dateFormat);
        $view->timeformat = $twentyFour  ? 'H:i' : 'h:ia';
        $view->events = $eventlist;

        $GLOBALS['registry']->setLanguageEnvironment($lang);
        $mime_mail = new Horde_Mime_Mail(
            array('subject' => sprintf(_("Your daily agenda for %s"), $view->date),
                  'to' => $email,
                  'from' => $GLOBALS['conf']['reminder']['from_addr'],
                  'charset' => $GLOBALS['registry']->getCharset()));
        $mime_mail->addHeader('User-Agent', 'Kronolith ' . $GLOBALS['registry']->getVersion());
        try {
            $mime_mail->addRecipients($email);
        } catch (Horde_Mime_Exception $e) {}

        $multipart = new Horde_Mime_Part();
        $multipart->setType('multipart/alternative');
        $bodyText = new Horde_Mime_Part();
        $bodyText->setType('text/plain');
        $bodyText->setCharset($GLOBALS['registry']->getCharset());
        $bodyText->setContents($view->render('notification.plain.php'));
        $multipart->addPart($bodyText);
        $bodyHtml = new Horde_Mime_Part();
        $bodyHtml->setType('text/html');
        $bodyHtml->setCharset($GLOBALS['registry']->getCharset());
        $bodyHtml->setContents($view->render('notification.html.php'));
        $multipart->addPart($bodyHtml);
        $mime_mail->setBasePart($multipart);

        Horde::logMessage(sprintf('Sending daily agenda to %s', $email), 'DEBUG');
        try {
            $mime_mail->send($GLOBALS['injector']->getInstance('Horde_Mail'), false, false);
        } catch (Horde_Mime_Exception $e) {}
    }
}
