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

// Exit if this isn't an authenticated user.
$default = Horde::url($prefs->getValue('defaultview') . '.php', true);
if (!$GLOBALS['registry']->getAuth()) {
    $default->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$calendar_id = $vars->get('c');

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->setAnchor('calendar:internal|' . $calendar_id)->redirect();
}

try {
    $calendar = $injector->getInstance('Kronolith_Shares')->getShare($calendar_id);
} catch (Exception $e) {
    $notification->push($e);
    $default->redirect();
}
if ($calendar->get('owner') != $GLOBALS['registry']->getAuth() &&
    (!is_null($calendar->get('owner')) || !$registry->isAdmin())) {
    $notification->push(_("You are not allowed to delete this calendar."), 'horde.error');
    $default->redirect();
}
$form = new Kronolith_Form_DeleteCalendar($vars, $calendar);

// Execute if the form is valid (must pass with POST variables only).
if ($form->validate(new Horde_Variables($_POST))) {
    try {
        $form->execute();
        $notification->push(sprintf(_("The calendar \"%s\" has been deleted."), $calendar->get('name')), 'horde.success');
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
echo $form->renderActive($form->getRenderer(), $vars, Horde::url('calendars/delete.php'), 'post');
$page_output->footer();
