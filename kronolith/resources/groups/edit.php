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
if (!$registry->isAdmin()) {
    Horde::url($prefs->getValue('defaultview') . '.php', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
try {
    $group = Kronolith::getDriver('Resource')->getResource($vars->get('c'));
    if (!$group->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
        $notification->push(_("You are not allowed to change this resource."), 'horde.error');
        Horde::url('resources/groups/', true)->redirect();
    }
} catch (Exception $e) {
    $notification->push($e, 'horde.error');
    Horde::url('resources/groups/', true)->redirect();
}
$form = new Kronolith_Form_EditResourceGroup($vars, $group);

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

    Horde::url('resources/groups/', true)->redirect();
}

$vars->set('name', $group->get('name'));
$vars->set('description', $group->get('description'));
$vars->set('members', $group->get('members'));

$menu = Kronolith::menu();
$page_output->header(array(
    'title' => $form->getTitle()
));
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
echo $menu;
$notification->notify(array('listeners' => 'status'));
echo $form->renderActive($form->getRenderer(), $vars, Horde::url('resources/groups/edit.php'), 'post');
$page_output->footer();
