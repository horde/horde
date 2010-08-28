<?php
/**
 * Turba deletefile.php.
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

if ($conf['documents']['type'] == 'none') {
    exit;
}

$source = Horde_Util::getPost('source');
if ($source === null || !isset($cfgSources[$source])) {
    $notification->push(_("Not found"), 'horde.error');
    Horde::applicationUrl($prefs->getValue('initial_page'), true)->redirect();
}

$driver = $injector->getInstance('Turba_Driver')->getDriver($source);
$contact = $driver->getObject(Horde_Util::getPost('key'));
if (is_a($contact, 'PEAR_Error')) {
    $notification->push($contact, 'horde.error');
    Horde::applicationUrl($prefs->getValue('initial_page'), true)->redirect();
}

if (!$contact->isEditable()) {
    $notification->push(_("Permission denied"), 'horde.error');
    Horde::applicationUrl($prefs->getValue('initial_page'), true)->redirect();
}

$file = Horde_Util::getPost('file');
$result = $contact->deleteFile($file);
if (is_a($result, 'PEAR_Error')) {
    $notification->push($result, 'horde.error');
} else {
    $notification->push(sprintf(_("The file \"%s\" has been deleted."), $file), 'horde.success');
}
$contact->url('Contact', true)->redirect();
