<?php
/**
 * Turba delete.php.
 *
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Turba
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('turba');

$source = Horde_Util::getFormData('source');
$key = Horde_Util::getFormData('key');
$driver = $injector->getInstance('Turba_Driver')->getDriver($source);

if ($conf['documents']['type'] != 'none') {
    $object = $driver->getObject($key);
    if ($object instanceof PEAR_Error) {
        $notification->push($object->getMessage(), 'horde.error');
        Horde::applicationUrl($prefs->getValue('initial_page'), true)->redirect();
    }

    $deleted = $object->deleteFiles();
    if ($deleted instanceof PEAR_Error) {
        $notification->push($deleted, 'horde.error');
        Horde::applicationUrl($prefs->getValue('initial_page'), true)->redirect();
    }
}

$result = $driver->delete($key);
if (!($result instanceof PEAR_Error)) {
    $url = ($url = Horde_Util::getFormData('url'))
        ? new Horde_Url($url)
        : Horde::applicationUrl($prefs->getValue('initial_page'), true);
    $url->redirect();
}

$notification->push(sprintf(_("There was an error deleting this contact: %s"), $result->getMessage()), 'horde.error');
$title = _("Deletion failed");
require TURBA_TEMPLATES . '/common-header.inc';
require TURBA_TEMPLATES . '/menu.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
