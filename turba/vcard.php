<?php
/**
 * Turba vcard.php.
 *
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('turba');

$source = Horde_Util::getFormData('source');
if (!isset($cfgSources[$source])) {
    $notification->push(_("The contact you requested does not exist."), 'horde.error');
    Horde::url($prefs->getValue('initial_page'), true)->redirect();
}

$driver = $injector->getInstance('Turba_Factory_Driver')->create($source);

/* Set the contact from the key requested. */
try {
    $object = $driver->getObject(Horde_Util::getFormData('key'));
} catch (Turba_Exception $e) {
    $notification->push($e, 'horde.error');
    Horde::url($prefs->getValue('initial_page'), true)->redirect();
}

/* Check permissions on this contact. */
if (!$object->hasPermission(Horde_Perms::READ)) {
    $notification->push(_("You do not have permission to view this object."), 'horde.error');
    Horde::url($prefs->getValue('initial_page'), true)->redirect();
}

$filename = str_replace(' ', '_', $object->getValue('name'));
if (!$filename) {
    $filename = _("contact");
}

$injector->getInstance('Horde_Core_Factory_Data')->create('Vcard')->exportFile($filename . '.vcf', array($driver->tovCard($object, '2.1', null, true)));
