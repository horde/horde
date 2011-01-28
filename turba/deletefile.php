<?php
/**
 * Turba deletefile.php.
 *
 * Copyright 2000-2011 The Horde Project (http://www.horde.org/)
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
    Horde::url($prefs->getValue('initial_page'), true)->redirect();
}

$driver = $injector->getInstance('Turba_Factory_Driver')->create($source);

try {
    $contact = $driver->getObject(Horde_Util::getPost('key'));
} catch (Turba_Exception $e) {
    $notification->push($e, 'horde.error');
    Horde::url($prefs->getValue('initial_page'), true)->redirect();
}

if (!$contact->isEditable()) {
    $notification->push(_("Permission denied"), 'horde.error');
    Horde::url($prefs->getValue('initial_page'), true)->redirect();
}

$file = Horde_Util::getPost('file');

try {
    $contact->deleteFile($file);
    $notification->push(sprintf(_("The file \"%s\" has been deleted."), $file), 'horde.success');
} catch (Turba_Exception $e) {
    $notification->push($e, 'horde.error');
}

$contact->url('Contact', true)->redirect();
