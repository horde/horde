<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/../../lib/Application.php';
Horde_Registry::appInit('kronolith');

// Exit if this isn't an authenticated administrative user.
$default = Horde::url($prefs->getValue('defaultview') . '.php', true);
if (!$registry->isAdmin()) {
    $default->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
try {
    $resource = Kronolith::getDriver('Resource')->getResource($vars->get('c'));
    if (!$resource->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
        $notification->push(_("You are not allowed to delete this resource group."), 'horde.error');
        $default->redirect();
    }
} catch (Exception $e) {
    $notification->push($e);
    $default->redirect();
}

$form = new Kronolith_Form_DeleteResourceGroup($vars, $resource);

// Execute if the form is valid (must pass with POST variables only).
if ($form->validate(new Horde_Variables($_POST))) {
    try {
        $form->execute();
        $notification->push(sprintf(_("The resource group \"%s\" has been deleted."), $resource->get('name')), 'horde.success');
    } catch (Exception $e) {
        $notification->push($e);
    }

    $default->redirect();
}

$page_output->header(array(
    'title' => $form->getTitle()
));
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
$notification->notify(array('listeners' => 'status'));
echo $form->renderActive($form->getRenderer(), $vars, Horde::url('resources/groups/delete.php'), 'post');
$page_output->footer();
