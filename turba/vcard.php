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
    Horde::applicationUrl($prefs->getValue('initial_page'), true)->redirect();
}

$driver = Turba_Driver::singleton($source);

/* Set the contact from the key requested. */
$key = Horde_Util::getFormData('key');
$object = $driver->getObject($key);
if (is_a($object, 'PEAR_Error')) {
    $notification->push($object->getMessage(), 'horde.error');
    Horde::applicationUrl($prefs->getValue('initial_page'), true)->redirect();
}

/* Check permissions on this contact. */
if (!$object->hasPermission(Horde_Perms::READ)) {
    $notification->push(_("You do not have permission to view this object."), 'horde.error');
    Horde::applicationUrl($prefs->getValue('initial_page'), true)->redirect();
}

$filename = str_replace(' ', '_', $object->getValue('name'));
if (!$filename) {
    $filename = _("contact");
}

$injector->getInstance('Horde_Data')->getData('Vcard')->exportFile($filename . '.vcf', array($driver->tovCard($object, '2.1', null, true)), $GLOBALS['registry']->getCharset());
