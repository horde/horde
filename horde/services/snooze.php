<?php
/**
 * Copyright 2007-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('nologintasks' => true));

$alarm = $injector->getInstance('Horde_Alarm');
$id = Horde_Util::getPost('alarm');
$snooze = Horde_Util::getPost('snooze');

if ($id && $snooze) {
    try {
        $alarm->snooze($id, $registry->getAuth(), (int)$snooze);
    } catch (Horde_Alarm_Exception $e) {
        header('HTTP/1.0 500 ' . $e->getMessage());
    }
} else {
    header('HTTP/1.0 400 Bad Request');
}
