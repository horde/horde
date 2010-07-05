<?php
/**
 * Merges one contact into another.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('turba');

$source = Horde_Util::getFormData('source');
$key = Horde_Util::getFormData('key');
$mergeInto = Horde_Util::getFormData('merge_into');
$driver = Turba_Driver::singleton($source);

if ($url = Horde_Util::getFormData('url')) {
    $url = new Horde_Url($url, true);
    $url->add('unique', hash('md5', microtime()));
}

$contact = $driver->getObject($mergeInto);
if (is_a($contact, 'PEAR_Error')) {
    $notification->push($contact);
    header('Location: ' . $url);
    exit;
}
$toMerge = $driver->getObject($key);
if (is_a($toMerge, 'PEAR_Error')) {
    $notification->push($toMerge);
    header('Location: ' . $url);
    exit;
}

$contact->merge($toMerge);
if (is_a($result = $contact->store(), 'PEAR_Error')) {
    $notification->push($result);
    header('Location: ' . $url);
    exit;
}
if (is_a($result = $driver->delete($key), 'PEAR_Error')) {
    $notification->push($result);
    header('Location: ' . $url);
    exit;
}

$notification->push(_("Successfully merged two contacts."), 'horde.success');
header('Location: ' . $url);
exit;
