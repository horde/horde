<?php
/**
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('kronolith');

require_once KRONOLITH_BASE . '/lib/Forms/DeleteResourceGroup.php';

// Exit if this isn't an authenticated administrative user.
if (!$registry->isAdmin()) {
    Horde::applicationUrl($prefs->getValue('defaultview') . '.php', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
try {
    $resource = Kronolith::getDriver('Resource')->getResource($vars->get('c'));
    if (!$resource->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
        $notification->push(_("You are not allowed to delete this resource group."), 'horde.error');
        Horde::applicationUrl('resources/groups/', true)->redirect();
    }
} catch (Exception $e) {
    $notification->push($e, 'horde.error');
    Horde::applicationUrl('resources/groups/', true)->redirect();
}

$form = new Kronolith_DeleteResourceGroupForm($vars, $resource);

// Execute if the form is valid (must pass with POST variables only).
if ($form->validate(new Horde_Variables($_POST))) {
    try {
        $result = $form->execute();
        $notification->push(sprintf(_("The resource group \"%s\" has been deleted."), $resource->get('name')), 'horde.success');
    } catch (Exception $e) {
        $notification->push($e, 'horde.error');
    }

    Horde::applicationUrl('resources/groups/', true)->redirect();
}

$title = $form->getTitle();
require KRONOLITH_TEMPLATES . '/common-header.inc';
require KRONOLITH_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, 'delete.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
