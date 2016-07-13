<?php
/**
 * Copyright 1999-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('kronolith', array('authentication' => 'none', 'session_control' => 'none'));

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
    try {
        if ($user) {
            $fb = Kronolith_FreeBusy::getForUser($user)->exportvCalendar();
        } else {
            $fb = Kronolith_FreeBusy::generate(explode('|', $cal), null, null, false, $user);
        }
    } catch (Exception $e) {
        Horde::log($e, 'ERR');
        exit;
    }
    $cache->set($key, $fb);
}

$browser->downloadHeaders(($user ? $user : $cal) . '.vfb',
                          'text/calendar; charset=' . 'UTF-8',
                          true,
                          strlen($fb));
echo $fb;
