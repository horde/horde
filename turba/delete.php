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

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('turba');

$vars = Horde_Variables::getDefaultVariables();
$driver = $injector->getInstance('Turba_Factory_Driver')->create($vars->source);

try {
    $object = $driver->getObject($vars->key);
    $object->deleteFiles();
} catch (Turba_Exception $e) {
    $notification->push($e, 'horde.error');
    Horde::url($prefs->getValue('initial_page'), true)->redirect();
}

try {
    $driver->delete($vars->key);
    $notification->push(sprintf(_("Deleted contact: %s"), $object->getValue('name')), 'horde.success');
    $url = strlen($vars->url)
        ? new Horde_Url($vars->url)
        : Horde::url($prefs->getValue('initial_page'), true);
    $url->redirect();
} catch (Turba_Exception $e) {
    $notification->push(sprintf(_("There was an error deleting this contact: %s"), $e->getMessage()), 'horde.error');
}

$page_output->header(array(
    'title' => _("Deletion failed")
));
$notification->notify(array('listeners' => 'status'));
$page_output->footer();
