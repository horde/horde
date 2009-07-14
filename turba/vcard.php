<?php
/**
 * Turba vcard.php.
 *
 * Copyright 2000-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/lib/base.php';

$source = Horde_Util::getFormData('source');
if (!isset($cfgSources[$source])) {
    $notification->push(_("The contact you requested does not exist."), 'horde.error');
    header('Location: ' . Horde::applicationUrl($prefs->getValue('initial_page'), true));
    exit;
}

$driver = Turba_Driver::singleton($source);

/* Set the contact from the key requested. */
$key = Horde_Util::getFormData('key');
$object = $driver->getObject($key);
if (is_a($object, 'PEAR_Error')) {
    $notification->push($object->getMessage(), 'horde.error');
    header('Location: ' . Horde::applicationUrl($prefs->getValue('initial_page'), true));
    exit;
}

/* Check permissions on this contact. */
if (!$object->hasPermission(PERMS_READ)) {
    $notification->push(_("You do not have permission to view this object."), 'horde.error');
    header('Location: ' . Horde::applicationUrl($prefs->getValue('initial_page'), true));
    exit;
}

$vcard = Horde_Data::singleton('vcard');
$filename = str_replace(' ', '_', $object->getValue('name'));
if (!$filename) {
    $filename = _("contact");
}
$vcard->exportFile($filename . '.vcf', array($driver->tovCard($object)), Horde_Nls::getCharset());
