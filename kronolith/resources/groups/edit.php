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

require_once KRONOLITH_BASE . '/lib/Forms/EditResourceGroup.php';

// Exit if this isn't an authenticated administrative user.
if (!Horde_Auth::isAdmin()) {
    header('Location: ' . Horde::applicationUrl($prefs->getValue('defaultview') . '.php', true));
    exit;
}

$vars = Horde_Variables::getDefaultVariables();
try {
    $group = Kronolith::getDriver('Resource')->getResource($vars->get('c'));
    if (!$group->hasPermission(Horde_Auth::getAuth(), Horde_Perms::EDIT)) {
        $notification->push(_("You are not allowed to change this resource."), 'horde.error');
        header('Location: ' . Horde::applicationUrl('resources/groups/', true));
        exit;
    }
} catch (Exception $e) {
    $notification->push($e, 'horde.error');
    header('Location: ' . Horde::applicationUrl('resources/groups/', true));
    exit;
}
$form = new Kronolith_EditResourceGroupForm($vars, $group);

// Execute if the form is valid.
if ($form->validate($vars)) {
    $original_name = $group->get('name');
    try {
        $result = $form->execute();
        if ($result->get('name') != $original_name) {
            $notification->push(sprintf(_("The resource group \"%s\" has been renamed to \"%s\"."), $original_name, $group->get('name')), 'horde.success');
        } else {
            $notification->push(sprintf(_("The resource group \"%s\" has been saved."), $original_name), 'horde.success');
        }
    } catch (Exception $e) {
        $notification->push($e, 'horde.error');
    }

    header('Location: ' . Horde::applicationUrl('resources/groups/', true));
    exit;
}

$vars->set('name', $group->get('name'));
$vars->set('description', $group->get('description'));
$vars->set('members', $group->get('members'));

$title = $form->getTitle();
require KRONOLITH_TEMPLATES . '/common-header.inc';
require KRONOLITH_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, 'edit.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
