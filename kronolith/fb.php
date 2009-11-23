<?php
/**
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */

$kronolith_authentication = 'none';
$kronolith_session_control = 'none';
require_once dirname(__FILE__) . '/lib/base.php';

// We want to always generate UTF-8 iCalendar data.
Horde_Nls::setCharset('UTF-8');

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

$cache = Horde_Cache::factory($conf['cache']['driver'], Horde::getDriverConfig('cache', $conf['cache']['driver']));
$key = 'kronolith.fb.' . ($user ? 'u.' . $user : 'c.' . $cal);
$fb = $cache->get($key, 360);
if (!$fb) {
    if ($user) {
        $prefs = Horde_Prefs::singleton($conf['prefs']['driver'], 'kronolith', $user, '', null, false);
        $prefs->retrieve();
        Horde_Nls::setTimeZone();
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

    $fb = Kronolith_FreeBusy::generate(explode('|', $cal), null, null, false, $user);
    if (is_a($fb, 'PEAR_Error')) {
        Horde::logMessage($fb, __FILE__, __LINE__, PEAR_LOG_ERR);
        exit;
    }
    $cache->set($key, $fb);
}

$browser->downloadHeaders(($user ? $user : $cal) . '.vfb',
                          'text/calendar; charset=' . Horde_Nls::getCharset(),
                          true,
                          strlen($fb));
echo $fb;
