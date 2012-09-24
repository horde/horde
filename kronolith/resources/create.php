<?php
/**
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
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

// Exit if this isn't an authenticated, administrative user
$default = Horde::url($prefs->getValue('defaultview') . '.php', true);
if (!$registry->isAdmin()) {
    $default->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$form = new Kronolith_Form_CreateResource($vars);

// Execute if the form is valid.
if ($form->validate($vars)) {
    try {
        $form->execute();
        $notification->push(sprintf(_("The calendar \"%s\" has been created."), $vars->get('name')), 'horde.success');
        $default->redirect();
    } catch (Exception $e) {
        $notification->push($e);
    }
}

$page_output->header(array(
    'title' => $form->getTitle()
));
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
$notification->notify(array('listeners' => 'status'));
echo $form->renderActive($form->getRenderer(), $vars, Horde::url('resources/create.php'), 'post');
$page_output->footer();
