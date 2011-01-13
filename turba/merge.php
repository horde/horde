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
$driver = $injector->getInstance('Turba_Injector_Factory_Driver')->create($source);

if ($url = Horde_Util::getFormData('url')) {
    $url = new Horde_Url($url, true);
    $url = $url->unique();
}

try {
    $contact = $driver->getObject($mergeInto);
    $toMerge = $driver->getObject($key);
    $contact->merge($toMerge);
    $contact->store();
    $driver->delete($key);

    $notification->push(_("Successfully merged two contacts."), 'horde.success');
} catch (Turba_Exception $e) {
    $notification->push($e);
}

$url->redirect();
