<?php
/**
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/base.php';
require_once KRONOLITH_BASE . '/lib/Forms/DeleteResourceGroup.php';

// Exit if this isn't an authenticated administrative user.
if (!Horde_Auth::isAdmin()) {
    header('Location: ' . Horde::applicationUrl($prefs->getValue('defaultview') . '.php', true));
    exit;
}

$vars = Horde_Variables::getDefaultVariables();
$d = Kronolith::getDriver('Resource');
$resource = $d->getResource($vars->get('c'));

if ($resource instanceof PEAR_Error) {
    $notification->push($resoruce, 'horde.error');
    header('Location: ' . Horde::applicationUrl('resources/groups/', true));
    exit;
} elseif (!$resource->hasPermission(Horde_Auth::getAuth(), Horde_Perms::DELETE)) {
    $notification->push(_("You are not allowed to delete this resource group."), 'horde.error');
    header('Location: ' . Horde::applicationUrl('resources/groups/', true));
    exit;
}

$form = new Kronolith_DeleteResourceGroupForm($vars, $resource);

// Execute if the form is valid (must pass with POST variables only).
if ($form->validate(new Horde_Variables($_POST))) {
    $result = $form->execute();
    if ($result instanceof PEAR_Error) {
        $notification->push($result, 'horde.error');
    } elseif ($result) {
        $notification->push(sprintf(_("The resource group \"%s\" has been deleted."), $resource->get('name')), 'horde.success');
    }

    header('Location: ' . Horde::applicationUrl('resources/groups/', true));
    exit;
}

$title = $form->getTitle();
require KRONOLITH_TEMPLATES . '/common-header.inc';
require KRONOLITH_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, 'delete.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
