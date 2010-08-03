<?php
/**
 * Turba delete.php.
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
$key = Horde_Util::getFormData('key');
$driver = Turba_Driver::singleton($source);

if ($conf['documents']['type'] != 'none') {
    $object = $driver->getObject($key);
    if (is_a($object, 'PEAR_Error')) {
        $notification->push($object->getMessage(), 'horde.error');
        Horde::applicationUrl($prefs->getValue('initial_page'), true)->redirect();
    }
    if (is_a($deleted = $object->deleteFiles(), 'PEAR_Error')) {
        $notification->push($deleted, 'horde.error');
        Horde::applicationUrl($prefs->getValue('initial_page'), true)->redirect();
    }
}

if (!is_a($result = $driver->delete($key), 'PEAR_Error')) {
    header('Location: ' . Horde_Util::getFormData('url', Horde::url($prefs->getValue('initial_page'), true)));
    exit;
}

$notification->push(sprintf(_("There was an error deleting this contact: %s"), $result->getMessage()), 'horde.error');
$title = _("Deletion failed");
require TURBA_TEMPLATES . '/common-header.inc';
require TURBA_TEMPLATES . '/menu.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
