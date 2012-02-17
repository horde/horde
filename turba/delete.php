<?php
/**
 * Turba delete.php.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('turba');

$source = Horde_Util::getFormData('source');
$key = Horde_Util::getFormData('key');
$driver = $injector->getInstance('Turba_Factory_Driver')->create($source);

try {
    $object = $driver->getObject($key);
    $object->deleteFiles();
} catch (Turba_Exception $e) {
    $notification->push($e, 'horde.error');
    Horde::url($prefs->getValue('initial_page'), true)->redirect();
}

try {
    $driver->delete($key);
    $url = ($url = Horde_Util::getFormData('url'))
        ? new Horde_Url($url)
        : Horde::url($prefs->getValue('initial_page'), true);
    $url->redirect();
} catch (Turba_Exception $e) {
    $notification->push(sprintf(_("There was an error deleting this contact: %s"), $e->getMessage()), 'horde.error');
}

$title = _("Deletion failed");
require $registry->get('templates', 'horde') . '/common-header.inc';
require TURBA_TEMPLATES . '/menu.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
