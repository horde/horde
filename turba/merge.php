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
$driver = $injector->getInstance('Turba_Driver')->getDriver($source);

if ($url = Horde_Util::getFormData('url')) {
    $url = new Horde_Url($url, true)->unique();
}

$contact = $driver->getObject($mergeInto);
if (is_a($contact, 'PEAR_Error')) {
    $notification->push($contact);
    $url->redirect();
}
$toMerge = $driver->getObject($key);
if (is_a($toMerge, 'PEAR_Error')) {
    $notification->push($toMerge);
    $url->redirect();
}

$contact->merge($toMerge);
if (is_a($result = $contact->store(), 'PEAR_Error')) {
    $notification->push($result);
    $url->redirect();
}
if (is_a($result = $driver->delete($key), 'PEAR_Error')) {
    $notification->push($result);
    $url->redirect();
}

$notification->push(_("Successfully merged two contacts."), 'horde.success');
$url->redirect();
