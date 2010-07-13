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
Horde_Registry::appInit('kronolith', array('authentication' => 'none', 'session_control' => 'none'));

// We want to always generate UTF-8 iCalendar data.
$registry->setCharset('UTF-8');

// Determine the username to show free/busy time for.
$cal = Horde_Util::getFormData('c');
$user = Horde_Util::getFormData('u');
if (!empty($cal)) {
    if (is_array($cal)) {
        $cal = implode('|', $cal);
    }
} elseif ($pathInfo = Horde_Util::getPathInfo()) {
    $user = basename($pathInfo);
}

$cache = $injector->getInstance('Horde_Cache');
$key = 'kronolith.fb.' . ($user ? 'u.' . $user : 'c.' . $cal);
$fb = $cache->get($key, 360);
if (!$fb) {
    if ($user) {
        $prefs = Horde_Prefs::singleton($conf['prefs']['driver'], 'kronolith', $user, '', null, false);
        $prefs->retrieve();
        $registry->setTimeZone();
        $cal = @unserialize($prefs->getValue('fb_cals'));
        if (is_array($cal)) {
            $cal = implode('|', $cal);
        }

        // If the free/busy calendars preference is empty, default to
        // the user's default_share preference, and if that's empty,
        // to their username.
        if (!$cal) {
            $cal = $prefs->getValue('default_share');
            if (!$cal) {
                $cal = $user;
            }
        }
    }

    try {
        $fb = Kronolith_FreeBusy::generate(explode('|', $cal), null, null, false, $user);
    } catch (Exception $e) {
        Horde::logMessage($e, 'ERR');
        exit;
    }
    $cache->set($key, $fb);
}

$browser->downloadHeaders(($user ? $user : $cal) . '.vfb',
                          'text/calendar; charset=' . $GLOBALS['registry']->getCharset(),
                          true,
                          strlen($fb));
echo $fb;
