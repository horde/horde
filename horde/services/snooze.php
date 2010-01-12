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
new Horde_Application(array('nologintasks' => true));

$alarm = Horde_Alarm::factory();
$id = Horde_Util::getPost('alarm');
$snooze = Horde_Util::getPost('snooze');

if ($id && $snooze) {
    if (is_a($result = $alarm->snooze($id, Horde_Auth::getAuth(), (int)$snooze), 'PEAR_Error')) {
        header('HTTP/1.0 500 ' . $result->getMessage());
    }
} else {
    header('HTTP/1.0 400 Bad Request');
}
