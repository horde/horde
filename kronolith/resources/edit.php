<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('kronolith');

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->redirect();
}

// Exit if this isn't an authenticated administrative user.
$default = Horde::url($prefs->getValue('defaultview') . '.php', true);
if (!$registry->isAdmin()) {
    $default->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
try {
    $resource = Kronolith::getDriver('Resource')->getResource($vars->get('c'));
    if (!$resource->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
        $notification->push(_("You are not allowed to change this resource."), 'horde.error');
        $default->redirect();
    }
} catch (Exception $e) {
    $notification->push($e);
    $default->redirect();
}
$form = new Kronolith_Form_EditResource($vars, $resource);

// Execute if the form is valid.
if ($form->validate($vars)) {
    $original_name = $resource->get('name');
    try {
        $result = $form->execute();
        if ($result->get('name') != $original_name) {
            $notification->push(sprintf(_("The resource \"%s\" has been renamed to \"%s\"."), $original_name, $resource->get('name')), 'horde.success');
        } else {
            $notification->push(sprintf(_("The resource \"%s\" has been saved."), $original_name), 'horde.success');
        }
        $default->redirect();
    } catch (Exception $e) {
        $notification->push($e);
    }
}

$vars->set('name', $resource->get('name'));
$vars->set('email', $resource->get('email'));
$vars->set('description', $resource->get('description'));
$vars->set('category', Kronolith::getDriver('Resource')->getGroupMemberships($resource->getId()));
$vars->set('responsetype', $resource->get('response_type'));

$page_output->header(array(
    'title' => $form->getTitle()
));
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
$notification->notify(array('listeners' => 'status'));
echo $form->renderActive($form->getRenderer(), $vars, Horde::url('resources/edit.php'), 'post');
$page_output->footer();
