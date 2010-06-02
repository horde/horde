<?php
/**
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
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
